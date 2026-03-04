<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\PaymentRecord;

class FinancialPostingService
{
    /**
     * Map expense categories to GL account codes.
     * Categories not listed here fall back to Other Expenses.
     */
    private const EXPENSE_ACCOUNT_MAP = [
        'fuel'           => PostingRules::EXPENSE_FUEL,
        'vehicle_repair' => PostingRules::EXPENSE_VEHICLE_REPAIR,
        'supplies'       => PostingRules::EXPENSE_SUPPLIES,
        'parts'          => PostingRules::COGS_PARTS_MATERIALS,
        'insurance'      => PostingRules::EXPENSE_INSURANCE,
        'licensing'      => PostingRules::EXPENSE_LICENSING,
        'tools'          => PostingRules::EXPENSE_TOOLS,
        'marketing'      => PostingRules::EXPENSE_MARKETING,
        'office'         => PostingRules::EXPENSE_OFFICE,
        'other'          => PostingRules::EXPENSE_OTHER,
    ];

    // ── Invoice sent → Debit A/R, Credit Revenue (+Tax) ──

    /**
     * Post a journal entry when an invoice is sent (finalized).
     */
    public function postInvoiceSent(Invoice $invoice, ?int $userId = null): ?JournalEntry
    {
        // Don't double-post
        if ($this->hasExistingPosting($invoice)) {
            return null;
        }

        $ar      = $this->account(PostingRules::ACCOUNTS_RECEIVABLE);
        $revenue = $this->account(PostingRules::REVENUE_DEFAULT);
        $taxPayable = $this->account(PostingRules::SALES_TAX_PAYABLE);

        if (! $ar || ! $revenue) {
            return null;
        }

        $entry = JournalEntry::create([
            'entry_number' => JournalEntry::generateEntryNumber(),
            'entry_date'   => $invoice->created_at?->toDateString() ?? now()->toDateString(),
            'memo'         => "Invoice {$invoice->displayNumber()} sent",
            'reference'    => $invoice->invoice_number,
            'source_type'  => Invoice::class,
            'source_id'    => $invoice->id,
            'status'       => JournalEntry::STATUS_POSTED,
            'created_by'   => $userId,
            'posted_by'    => $userId,
            'posted_at'    => now(),
        ]);

        // Debit A/R for the full invoice total
        $entry->lines()->create([
            'account_id'  => $ar->id,
            'debit'       => $invoice->total,
            'credit'      => 0,
            'description' => "A/R – {$invoice->displayNumber()}",
        ]);

        // Credit Revenue (subtotal before tax)
        $revenueAmount = (float) $invoice->total - (float) $invoice->tax_amount;
        $entry->lines()->create([
            'account_id'  => $revenue->id,
            'debit'       => 0,
            'credit'      => $revenueAmount,
            'description' => "Revenue – {$invoice->displayNumber()}",
        ]);

        // Credit Sales Tax Payable (if applicable)
        if ((float) $invoice->tax_amount > 0 && $taxPayable) {
            $entry->lines()->create([
                'account_id'  => $taxPayable->id,
                'debit'       => 0,
                'credit'      => $invoice->tax_amount,
                'description' => "Sales tax – {$invoice->displayNumber()}",
            ]);
        }

        return $entry;
    }

    // ── Invoice cancelled → reverse original posting ──

    /**
     * Reverse the GL posting when an invoice is cancelled.
     */
    public function reverseInvoice(Invoice $invoice, ?int $userId = null): bool
    {
        $existing = JournalEntry::where('source_type', Invoice::class)
            ->where('source_id', $invoice->id)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->first();

        if (! $existing) {
            return false;
        }

        $existing->void("Invoice {$invoice->displayNumber()} cancelled", $userId);

        return true;
    }

    // ── Payment recorded → Debit Cash, Credit A/R ──

    /**
     * Post a journal entry when a payment is recorded.
     */
    public function postPaymentReceived(PaymentRecord $payment, ?int $userId = null): ?JournalEntry
    {
        if ($this->hasExistingPosting($payment)) {
            return null;
        }

        $cash = $this->account(PostingRules::CASH);
        $ar   = $this->account(PostingRules::ACCOUNTS_RECEIVABLE);

        if (! $cash || ! $ar) {
            return null;
        }

        $entry = JournalEntry::create([
            'entry_number' => JournalEntry::generateEntryNumber(),
            'entry_date'   => $payment->collected_at?->toDateString() ?? now()->toDateString(),
            'memo'         => "Payment received – {$payment->methodLabel()} \${$payment->amount}",
            'reference'    => $payment->reference,
            'source_type'  => PaymentRecord::class,
            'source_id'    => $payment->id,
            'status'       => JournalEntry::STATUS_POSTED,
            'created_by'   => $userId,
            'posted_by'    => $userId,
            'posted_at'    => now(),
        ]);

        $entry->lines()->create([
            'account_id'  => $cash->id,
            'debit'       => $payment->amount,
            'credit'      => 0,
            'description' => "Cash in – {$payment->methodLabel()}",
        ]);

        $entry->lines()->create([
            'account_id'  => $ar->id,
            'debit'       => 0,
            'credit'      => $payment->amount,
            'description' => "A/R reduction – payment",
        ]);

        return $entry;
    }

    // ── Expense recorded → Debit Expense, Credit Cash ──

    /**
     * Post a journal entry when an expense is recorded.
     */
    public function postExpense(Expense $expense, ?int $userId = null): ?JournalEntry
    {
        if ($this->hasExistingPosting($expense)) {
            return null;
        }

        $expenseAccountCode = self::EXPENSE_ACCOUNT_MAP[$expense->category] ?? PostingRules::EXPENSE_OTHER;
        $expenseAccount = $this->account($expenseAccountCode);
        $cash = $this->account(PostingRules::CASH);

        if (! $expenseAccount || ! $cash) {
            return null;
        }

        $entry = JournalEntry::create([
            'entry_number' => JournalEntry::generateEntryNumber(),
            'entry_date'   => $expense->date?->toDateString() ?? now()->toDateString(),
            'memo'         => "Expense {$expense->expense_number} – {$expense->vendor}",
            'reference'    => $expense->expense_number,
            'source_type'  => Expense::class,
            'source_id'    => $expense->id,
            'status'       => JournalEntry::STATUS_POSTED,
            'created_by'   => $userId,
            'posted_by'    => $userId,
            'posted_at'    => now(),
        ]);

        $entry->lines()->create([
            'account_id'  => $expenseAccount->id,
            'debit'       => $expense->amount,
            'credit'      => 0,
            'description' => "{$expense->categoryLabel()} – {$expense->vendor}",
        ]);

        $entry->lines()->create([
            'account_id'  => $cash->id,
            'debit'       => 0,
            'credit'      => $expense->amount,
            'description' => "Cash out – {$expense->expense_number}",
        ]);

        return $entry;
    }

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
}
