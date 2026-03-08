<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Document;
use App\Models\DocumentTransactionImport;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Services\PostingRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionImportController extends Controller
{
    /**
     * Map AI account codes to their debit/credit expense accounts for journal entries.
        * For expenses: debit the expense account, credit Checking (1000).
        * For income: debit Checking (1000), credit the revenue account.
     */
        private const CASH_ACCOUNT_CODE = PostingRules::CHECKING;

    /** List all documents that have parsed transaction imports. */
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');

        $stats = [
            'total_docs'      => Document::whereHas('transactionImports')->count(),
            'total_rows'      => DocumentTransactionImport::count(),
            'draft'           => DocumentTransactionImport::where('status', 'draft')->count(),
            'accepted'        => DocumentTransactionImport::where('status', 'accepted')->count(),
            'rejected'        => DocumentTransactionImport::where('status', 'rejected')->count(),
        ];

        $query = Document::whereHas('transactionImports')
            ->withCount([
                'transactionImports',
                'transactionImports as draft_count' => fn ($q) => $q->where('status', 'draft'),
                'transactionImports as accepted_count' => fn ($q) => $q->where('status', 'accepted'),
                'transactionImports as rejected_count' => fn ($q) => $q->where('status', 'rejected'),
            ])
            ->latest();

        $query = match ($filter) {
            'pending'  => $query->having('draft_count', '>', 0),
            'reviewed' => $query->having('draft_count', '=', 0),
            default    => $query,
        };

        $documents = $query->paginate(20)->withQueryString();

        return view('documents.transaction-imports-index', compact('documents', 'filter', 'stats'));
    }

    /** Show all parsed transactions for a specific document. */
    public function show(Document $document)
    {
        $imports = $document->transactionImports()->orderBy('transaction_date')->get();

        $stats = [
            'total'    => $imports->count(),
            'draft'    => $imports->where('status', 'draft')->count(),
            'accepted' => $imports->where('status', 'accepted')->count(),
            'rejected' => $imports->where('status', 'rejected')->count(),
            'sum'      => $imports->where('status', 'draft')->sum('amount'),
        ];

        // Category breakdown for the accounting summary panel
        $categoryBreakdown = $imports
            ->groupBy('category')
            ->map(fn ($rows) => [
                'count'   => $rows->count(),
                'total'   => $rows->sum('amount'),
                'label'   => Expense::CATEGORIES[$rows->first()->category] ?? ucfirst($rows->first()->category ?? 'Other'),
            ])
            ->sortByDesc('total')
            ->all();

        // Type breakdown (income vs expense vs transfer)
        $typeBreakdown = $imports
            ->groupBy('type')
            ->map(fn ($rows) => [
                'count' => $rows->count(),
                'total' => $rows->sum('amount'),
            ])
            ->all();

        $categories = Expense::CATEGORIES;
        $paymentMethods = Expense::PAYMENT_METHODS;
        $accounts = Account::import()->where('is_active', true)->orderBy('code')->get();

        return view('documents.transaction-imports-show', compact(
            'document', 'imports', 'stats', 'categories', 'paymentMethods', 'accounts',
            'categoryBreakdown', 'typeBreakdown',
        ));
    }

    /** Accept a single draft transaction → create Expense + Journal Entry. */
    public function accept(Request $request, DocumentTransactionImport $import)
    {
        if ($import->status !== DocumentTransactionImport::STATUS_DRAFT) {
            return back()->with('error', 'This transaction has already been reviewed.');
        }

        // Allow inline edits before accepting
        $validated = $request->validate([
            'description'    => 'nullable|string|max:500',
            'amount'         => 'nullable|numeric|min:0.01',
            'category'       => 'nullable|string|in:' . implode(',', array_keys(Expense::CATEGORIES)),
            'vendor'         => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|in:' . implode(',', array_keys(Expense::PAYMENT_METHODS)),
            'account_code'   => 'nullable|string|max:20',
        ]);

        // Merge any inline edits
        if (! empty(array_filter($validated))) {
            $import->update(array_filter($validated));
            $import->refresh();
        }

        DB::transaction(function () use ($import) {
            $this->createRecordsFromImport($import);
        });

        return back()->with('success', "Transaction accepted — Expense {$import->fresh()->createdExpense->expense_number} created.");
    }

    /** Reject a single draft transaction. */
    public function reject(DocumentTransactionImport $import)
    {
        if ($import->status !== DocumentTransactionImport::STATUS_DRAFT) {
            return back()->with('error', 'This transaction has already been reviewed.');
        }

        $import->update([
            'status'      => DocumentTransactionImport::STATUS_REJECTED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Transaction rejected.');
    }

    /** Bulk-accept all draft transactions for a document. */
    public function bulkAccept(Document $document)
    {
        $drafts = $document->transactionImports()
            ->where('status', DocumentTransactionImport::STATUS_DRAFT)
            ->get();

        if ($drafts->isEmpty()) {
            return back()->with('info', 'No draft transactions to accept.');
        }

        $accepted = 0;

        DB::transaction(function () use ($drafts, &$accepted) {
            foreach ($drafts as $import) {
                $this->createRecordsFromImport($import);
                $accepted++;
            }
        });

        return back()->with('success', "{$accepted} transaction(s) accepted and recorded.");
    }

    /** Bulk-reject all draft transactions for a document. */
    public function bulkReject(Document $document)
    {
        $count = $document->transactionImports()
            ->where('status', DocumentTransactionImport::STATUS_DRAFT)
            ->update([
                'status'      => DocumentTransactionImport::STATUS_REJECTED,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

        return back()->with('success', "{$count} transaction(s) rejected.");
    }

    /**
     * Create accounting records from an accepted import row.
     * Expenses → Expense + JournalEntry (debit expense, credit cash).
     * Income  → JournalEntry only (debit cash, credit revenue).
     * Transfer → JournalEntry only (debit destination, credit source).
     */
    private function createRecordsFromImport(DocumentTransactionImport $import): void
    {
        $cashAccount = $this->generalAccount(self::CASH_ACCOUNT_CODE);

        if ($import->type === DocumentTransactionImport::TYPE_TRANSFER) {
            // Transfers don't create an Expense — just journal the movement
            $sourceAccount = $cashAccount;
            $destCode = $import->account_code ?: PostingRules::SAVINGS;
            $destAccount = $this->generalAccount($destCode);

            if ($sourceAccount && $destAccount) {
                $journalEntry = JournalEntry::create([
                    'entry_number' => JournalEntry::generateEntryNumber(),
                    'entry_date'   => $import->transaction_date ?? now(),
                    'memo'         => "Transfer: {$import->description}",
                    'reference'    => $import->reference,
                    'status'       => JournalEntry::STATUS_DRAFT,
                    'created_by'   => Auth::id(),
                ]);

                JournalLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id'       => $destAccount->id,
                    'debit'            => $import->amount,
                    'credit'           => 0,
                    'description'      => $import->description,
                ]);

                JournalLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id'       => $sourceAccount->id,
                    'debit'            => 0,
                    'credit'           => $import->amount,
                    'description'      => $import->description,
                ]);

                $import->update([
                    'status'                   => DocumentTransactionImport::STATUS_ACCEPTED,
                    'created_journal_entry_id' => $journalEntry->id,
                    'reviewed_by'              => Auth::id(),
                    'reviewed_at'              => now(),
                ]);
            }

            return;
        }

        // For income: no Expense record, just a journal entry
        if ($import->type === DocumentTransactionImport::TYPE_INCOME) {
            $revenueCode = $import->account_code ?: '4000';
            $revenueAccount = $this->generalAccount($revenueCode);

            if ($cashAccount && $revenueAccount) {
                $journalEntry = JournalEntry::create([
                    'entry_number' => JournalEntry::generateEntryNumber(),
                    'entry_date'   => $import->transaction_date ?? now(),
                    'memo'         => "Revenue: {$import->description}",
                    'reference'    => $import->reference,
                    'status'       => JournalEntry::STATUS_DRAFT,
                    'created_by'   => Auth::id(),
                ]);

                JournalLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id'       => $cashAccount->id,
                    'debit'            => $import->amount,
                    'credit'           => 0,
                    'description'      => $import->description,
                ]);

                JournalLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id'       => $revenueAccount->id,
                    'debit'            => 0,
                    'credit'           => $import->amount,
                    'description'      => $import->description,
                ]);

                $import->update([
                    'status'                   => DocumentTransactionImport::STATUS_ACCEPTED,
                    'created_journal_entry_id' => $journalEntry->id,
                    'reviewed_by'              => Auth::id(),
                    'reviewed_at'              => now(),
                ]);
            }

            return;
        }

        // For expenses: create Expense record + journal entry
        $expense = Expense::create([
            'expense_number'   => Expense::generateExpenseNumber(),
            'date'             => $import->transaction_date ?? now(),
            'vendor'           => $import->vendor ?? 'Unknown',
            'description'      => $import->description ?? 'Imported from spreadsheet',
            'category'         => $import->category ?? 'other',
            'amount'           => $import->amount,
            'payment_method'   => $import->payment_method ?? 'card',
            'reference_number' => $import->reference,
            'notes'            => 'Auto-imported from document #' . $import->document_id,
            'created_by'       => Auth::id(),
        ]);

            $expenseAccountCode = $import->account_code ?: PostingRules::EXPENSE_OTHER;
        $expenseAccount = $this->generalAccount($expenseAccountCode);

        $journalEntry = null;

        if ($expenseAccount && $cashAccount) {
            $journalEntry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber(),
                'entry_date'   => $import->transaction_date ?? now(),
                'memo'         => "Expense: {$import->description}",
                'reference'    => $expense->expense_number,
                'source_type'  => Expense::class,
                'source_id'    => $expense->id,
                'status'       => JournalEntry::STATUS_DRAFT,
                'created_by'   => Auth::id(),
            ]);

            JournalLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id'       => $expenseAccount->id,
                'debit'            => $import->amount,
                'credit'           => 0,
                'description'      => $import->description,
            ]);

            JournalLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id'       => $cashAccount->id,
                'debit'            => 0,
                'credit'           => $import->amount,
                'description'      => $import->description,
            ]);
        }

        $import->update([
            'status'                   => DocumentTransactionImport::STATUS_ACCEPTED,
            'created_expense_id'       => $expense->id,
            'created_journal_entry_id' => $journalEntry?->id,
            'reviewed_by'              => Auth::id(),
            'reviewed_at'              => now(),
        ]);
    }

    /**
     * Look up a general-scope account by code. Import account codes
     * share the same numbering, so this maps scanned-record categories
     * to the formal bookkeeping chart.
     */
    private function generalAccount(string $code): ?Account
    {
        return Account::general()->where('code', $code)->where('is_active', true)->first();
    }
}
