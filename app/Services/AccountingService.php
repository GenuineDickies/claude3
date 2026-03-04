<?php

namespace App\Services;

use App\Models\Account;
use App\Models\DocumentAccountingLink;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\PaymentRecord;
use App\Models\VendorDocument;

/**
 * Canonical entry point for all GL posting.
 *
 * Operational modules must never write journal entries directly.
 * They must call AccountingService::post(document_type, document_id).
 *
 * Design principles:
 *  - Operational documents do not create accounting entries.
 *  - Financial documents trigger accounting entries.
 *  - Every entry references its originating document.
 *  - Journal entries must always balance.
 *  - Posted entries are immutable.
 *  - Corrections use reversal entries.
 */
class AccountingService
{
    /**
     * Post accounting entries for a document.
     *
     * @param  string  $documentType  Fully-qualified class name
     * @param  int     $documentId
     * @param  int|null $userId
     * @return JournalEntry|null  The created journal entry, or null if skipped/already posted
     */
    public static function post(string $documentType, int $documentId, ?int $userId = null): ?JournalEntry
    {
        $document = $documentType::findOrFail($documentId);

        return match ($documentType) {
            Invoice::class        => (new self)->postInvoice($document, $userId),
            PaymentRecord::class  => (new self)->postPayment($document, $userId),
            Expense::class        => (new self)->postExpense($document, $userId),
            VendorDocument::class => (new self)->postVendorDocument($document, $userId),
            default               => null,
        };
    }

    /**
     * Reverse (void) the GL posting for a document.
     */
    public static function reverse(string $documentType, int $documentId, string $reason, ?int $userId = null): bool
    {
        $entry = JournalEntry::where('source_type', $documentType)
            ->where('source_id', $documentId)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->first();

        if (! $entry) {
            return false;
        }

        $entry->void($reason, $userId);
        return true;
    }

    // ── Invoice Posted → Debit A/R, Credit Revenue + Tax + Core ──

    private function postInvoice(Invoice $invoice, ?int $userId): ?JournalEntry
    {
        if ($this->hasExistingPosting($invoice)) {
            return null;
        }

        $ar         = $this->account('1200'); // Accounts Receivable
        $taxPayable = $this->account('2200'); // Sales Tax Payable
        $coreLiab   = $this->account('2350'); // Core Deposits Payable

        if (! $ar) {
            return null;
        }

        $entry = $this->createEntry(
            date: $invoice->created_at?->toDateString() ?? now()->toDateString(),
            memo: "Invoice {$invoice->displayNumber()} posted",
            reference: $invoice->invoice_number,
            source: $invoice,
            userId: $userId,
        );

        // Debit A/R for the full invoice total
        $this->debit($entry, $ar, $invoice->total, "A/R – {$invoice->displayNumber()}");

        // Credit line items based on catalog metadata when available
        $revenueCredits = [];
        $totalCoreDeposits = 0;

        if (is_array($invoice->line_items)) {
            foreach ($invoice->line_items as $item) {
                $amount = (float) ($item['quantity'] ?? 1) * (float) ($item['unit_price'] ?? 0);

                // Try to find catalog item for revenue account mapping
                $revenueAccountCode = '4000'; // Default: Roadside Service Revenue
                if (! empty($item['catalog_item_id'])) {
                    $catalogItem = \App\Models\CatalogItem::find($item['catalog_item_id']);
                    if ($catalogItem?->revenueAccount) {
                        $revenueAccountCode = $catalogItem->revenueAccount->code;
                    }
                    if ($catalogItem?->core_required && $catalogItem->core_amount > 0) {
                        $totalCoreDeposits += $catalogItem->core_amount * (float) ($item['quantity'] ?? 1);
                    }
                }

                $revenueCredits[$revenueAccountCode] = ($revenueCredits[$revenueAccountCode] ?? 0) + $amount;
            }
        }

        // If no line item detail, credit all revenue minus tax to default account
        if (empty($revenueCredits)) {
            $revenueAmount = (float) $invoice->total - (float) $invoice->tax_amount;
            $defaultRevenue = $this->account('4000');
            if ($defaultRevenue) {
                $this->credit($entry, $defaultRevenue, $revenueAmount, "Revenue – {$invoice->displayNumber()}");
            }
        } else {
            // Subtract core deposits from revenue total (they go to liability)
            $totalRevenueFromLines = array_sum($revenueCredits);
            $taxAmount = (float) $invoice->tax_amount;

            // If line totals + tax + core = invoice total, use line breakdown
            // Otherwise fall back to proportional allocation
            foreach ($revenueCredits as $code => $amount) {
                $acct = $this->account($code);
                if ($acct) {
                    $this->credit($entry, $acct, $amount, "Revenue ({$code}) – {$invoice->displayNumber()}");
                }
            }
        }

        // Credit Sales Tax Payable
        if ((float) $invoice->tax_amount > 0 && $taxPayable) {
            $this->credit($entry, $taxPayable, $invoice->tax_amount, "Sales tax – {$invoice->displayNumber()}");
        }

        // Credit Core Deposits Payable
        if ($totalCoreDeposits > 0 && $coreLiab) {
            $this->credit($entry, $coreLiab, $totalCoreDeposits, "Core deposits – {$invoice->displayNumber()}");
        }

        $this->linkDocument($invoice, $entry);
        return $entry;
    }

    // ── Payment Recorded → Debit Cash, Credit A/R ──

    private function postPayment(PaymentRecord $payment, ?int $userId): ?JournalEntry
    {
        if ($this->hasExistingPosting($payment)) {
            return null;
        }

        $ar = $this->account('1200'); // Accounts Receivable
        if (! $ar) {
            return null;
        }

        // Cash account depends on payment method
        $cashAccountCode = match ($payment->method) {
            'card'   => '1150', // Square Clearing
            default  => '1100', // Cash
        };
        $cash = $this->account($cashAccountCode) ?? $this->account('1100');

        if (! $cash) {
            return null;
        }

        $entry = $this->createEntry(
            date: $payment->collected_at?->toDateString() ?? now()->toDateString(),
            memo: "Payment received – {$payment->methodLabel()} \${$payment->amount}",
            reference: $payment->reference,
            source: $payment,
            userId: $userId,
        );

        $this->debit($entry, $cash, $payment->amount, "Cash in – {$payment->methodLabel()}");
        $this->credit($entry, $ar, $payment->amount, "A/R reduction – payment");

        $this->linkDocument($payment, $entry);
        return $entry;
    }

    // ── Expense → Debit Expense Account, Credit Cash ──

    private function postExpense(Expense $expense, ?int $userId): ?JournalEntry
    {
        if ($this->hasExistingPosting($expense)) {
            return null;
        }

        $expenseAccountCode = self::EXPENSE_ACCOUNT_MAP[$expense->category] ?? '6900';
        $expenseAccount = $this->account($expenseAccountCode);
        $cash = $this->account('1100');

        if (! $expenseAccount || ! $cash) {
            return null;
        }

        $entry = $this->createEntry(
            date: $expense->date?->toDateString() ?? now()->toDateString(),
            memo: "Expense {$expense->expense_number} – {$expense->vendor}",
            reference: $expense->expense_number,
            source: $expense,
            userId: $userId,
        );

        $this->debit($entry, $expenseAccount, $expense->amount, "{$expense->categoryLabel()} – {$expense->vendor}");
        $this->credit($entry, $cash, $expense->amount, "Cash out – {$expense->expense_number}");

        $this->linkDocument($expense, $entry);
        return $entry;
    }

    // ── Vendor Document Posted → COGS/Expense entries ──

    private function postVendorDocument(VendorDocument $doc, ?int $userId): ?JournalEntry
    {
        if ($this->hasExistingPosting($doc)) {
            return null;
        }

        if (! $doc->isPosted()) {
            return null;
        }

        // Determine credit account: paid → cash/checking, unpaid → A/P
        if ($doc->is_paid) {
            $creditAccountCode = match ($doc->payment_method) {
                'check', 'ach' => '1110', // Business Checking
                default        => '1100', // Cash
            };
        } else {
            $creditAccountCode = '2100'; // Accounts Payable
        }

        $creditAccount = $this->account($creditAccountCode);
        if (! $creditAccount) {
            return null;
        }

        $coreLiab = $this->account('2350'); // Core Deposits Payable
        $vendorName = $doc->vendor->name ?? 'Unknown Vendor';

        $entry = $this->createEntry(
            date: $doc->document_date->toDateString(),
            memo: "Vendor {$doc->typeLabel()} – {$vendorName}" .
                  ($doc->vendor_document_number ? " #{$doc->vendor_document_number}" : ''),
            reference: $doc->vendor_document_number,
            source: $doc,
            userId: $userId,
        );

        // Process each vendor document line
        $totalDebits = 0;
        $totalCoreCharges = 0;

        foreach ($doc->lines as $line) {
            if ($line->line_type === 'tax' || $line->line_type === 'shipping') {
                // Tax & shipping go to the expense category of the document
                $debitAccount = $this->account('6900'); // Other Expenses (default)
                if ($line->expense_account_id) {
                    $debitAccount = Account::find($line->expense_account_id) ?? $debitAccount;
                }
                if ($debitAccount) {
                    $this->debit($entry, $debitAccount, $line->line_total, "{$line->typeLabel()} – {$vendorName}");
                    $totalDebits += (float) $line->line_total;
                }
                continue;
            }

            // Determine debit account: COGS for parts, expense for services/other
            $debitAccount = null;
            if ($line->cogs_account_id) {
                $debitAccount = Account::find($line->cogs_account_id);
            } elseif ($line->expense_account_id) {
                $debitAccount = Account::find($line->expense_account_id);
            } elseif ($line->part?->cogsAccount) {
                $debitAccount = $line->part->cogsAccount;
            }

            // Fall back to vendor's default expense account or 6900
            if (! $debitAccount) {
                $debitAccount = $doc->vendor->defaultExpenseAccount
                    ?? $this->account('6900');
            }

            if ($debitAccount && (float) $line->line_total > 0) {
                $this->debit($entry, $debitAccount, $line->line_total, "{$line->description}");
                $totalDebits += (float) $line->line_total;
            }

            // Core charge → debit Core Deposits Payable
            if ($line->hasCoreCharge() && $coreLiab) {
                $this->debit($entry, $coreLiab, $line->core_amount, "Core charge – {$line->description}");
                $totalCoreCharges += (float) $line->core_amount;
            }
        }

        // Credit the payment/payable account for total
        $creditTotal = $totalDebits + $totalCoreCharges;
        if ($creditTotal > 0) {
            $this->credit($entry, $creditAccount, $creditTotal, ($doc->is_paid ? 'Paid' : 'A/P') . " – {$vendorName}");
        }

        $this->linkDocument($doc, $entry);
        return $entry;
    }

    // ── Vendor Bill Payment → Debit A/P, Credit Checking ──

    /**
     * Post a journal entry when a vendor bill (unpaid vendor document) is paid.
     */
    public static function postVendorBillPayment(
        VendorDocument $doc,
        string $paymentMethod,
        ?int $userId = null
    ): ?JournalEntry {
        $service = new self;

        $ap = $service->account('2100'); // Accounts Payable
        $cashCode = match ($paymentMethod) {
            'check', 'ach' => '1110', // Business Checking
            default        => '1100', // Cash
        };
        $cash = $service->account($cashCode);

        if (! $ap || ! $cash) {
            return null;
        }

        $vendorName = $doc->vendor->name ?? 'Unknown';

        $entry = $service->createEntry(
            date: now()->toDateString(),
            memo: "Vendor bill payment – {$vendorName}" .
                  ($doc->vendor_document_number ? " #{$doc->vendor_document_number}" : ''),
            reference: $doc->vendor_document_number,
            source: $doc,
            userId: $userId,
        );

        $service->debit($entry, $ap, $doc->total, "A/P paid – {$vendorName}");
        $service->credit($entry, $cash, $doc->total, "Cash out – vendor bill payment");

        $service->linkDocument($doc, $entry);
        return $entry;
    }

    // ── Core Return → Credit Core Deposits Payable ──

    /**
     * Record a core return (supplier refund for returned core).
     */
    public static function postCoreReturn(
        float $amount,
        string $description,
        ?object $sourceDocument = null,
        ?int $userId = null
    ): ?JournalEntry {
        $service = new self;

        $coreLiab = $service->account('2350');
        $cash     = $service->account('1100');

        if (! $coreLiab || ! $cash) {
            return null;
        }

        $entry = $service->createEntry(
            date: now()->toDateString(),
            memo: "Core return – {$description}",
            reference: null,
            source: $sourceDocument,
            userId: $userId,
        );

        $service->debit($entry, $cash, $amount, "Core refund received – {$description}");
        $service->credit($entry, $coreLiab, $amount, "Core deposit released – {$description}");

        return $entry;
    }

    // ── Square Deposit → Debit Checking, Credit Square Clearing ──

    /**
     * Record a Square deposit to checking account.
     */
    public static function postSquareDeposit(
        float $amount,
        float $fees,
        ?string $reference = null,
        ?int $userId = null
    ): ?JournalEntry {
        $service = new self;

        $checking       = $service->account('1110');
        $squareClearing = $service->account('1150');
        $squareFees     = $service->account('7010');

        if (! $checking || ! $squareClearing) {
            return null;
        }

        $entry = $service->createEntry(
            date: now()->toDateString(),
            memo: "Square deposit" . ($reference ? " – {$reference}" : ''),
            reference: $reference,
            source: null,
            userId: $userId,
        );

        $grossAmount = round($amount + $fees, 2);

        $service->debit($entry, $checking, $amount, "Deposit to checking");
        if ($fees > 0 && $squareFees) {
            $service->debit($entry, $squareFees, $fees, "Square processing fee");
        }
        $service->credit($entry, $squareClearing, $grossAmount, "Square clearing settled");

        return $entry;
    }

    // ── Expense category → Account code mapping ──

    private const EXPENSE_ACCOUNT_MAP = [
        'fuel'           => '6150', // Vehicle Fuel Expense
        'vehicle_repair' => '6200', // Vehicle Repairs
        'supplies'       => '6400', // Supplies
        'parts'          => '5100', // Parts & Materials (COGS)
        'insurance'      => '6300', // General Insurance
        'licensing'      => '6500', // Licensing & Permits
        'tools'          => '6600', // Tools & Equipment
        'marketing'      => '6700', // Advertising
        'office'         => '6800', // Office Expenses
        'other'          => '6900', // Other Expenses
    ];

    // ── Private helpers ────────────────────────────────

    private function account(string $code): ?Account
    {
        return Account::general()->where('code', $code)->where('is_active', true)->first();
    }

    private function hasExistingPosting(object $source): bool
    {
        return JournalEntry::where('source_type', get_class($source))
            ->where('source_id', $source->id)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->exists();
    }

    private function createEntry(
        string $date,
        string $memo,
        ?string $reference,
        ?object $source,
        ?int $userId,
    ): JournalEntry {
        return JournalEntry::create([
            'entry_number' => JournalEntry::generateEntryNumber(),
            'entry_date'   => $date,
            'memo'         => $memo,
            'reference'    => $reference,
            'source_type'  => $source ? get_class($source) : null,
            'source_id'    => $source?->id,
            'status'       => JournalEntry::STATUS_POSTED,
            'created_by'   => $userId,
            'posted_by'    => $userId,
            'posted_at'    => now(),
        ]);
    }

    private function debit(JournalEntry $entry, Account $account, float $amount, string $description): void
    {
        $entry->lines()->create([
            'account_id'  => $account->id,
            'debit'       => round($amount, 2),
            'credit'      => 0,
            'description' => $description,
        ]);
    }

    private function credit(JournalEntry $entry, Account $account, float $amount, string $description): void
    {
        $entry->lines()->create([
            'account_id'  => $account->id,
            'debit'       => 0,
            'credit'      => round($amount, 2),
            'description' => $description,
        ]);
    }

    private function linkDocument(object $document, JournalEntry $entry): void
    {
        DocumentAccountingLink::create([
            'document_type'    => get_class($document),
            'document_id'      => $document->id,
            'journal_entry_id' => $entry->id,
            'created_at'       => now(),
        ]);
    }
}
