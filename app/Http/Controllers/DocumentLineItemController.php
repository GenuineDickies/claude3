<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Document;
use App\Models\DocumentLineItem;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocumentLineItemController extends Controller
{
    private const CASH_ACCOUNT_CODE = '1100';

    /** Update category and/or account on a draft line item, then accept it. */
    public function accept(Request $request, DocumentLineItem $lineItem)
    {
        if (! $lineItem->isDraft()) {
            return back()->with('error', 'This line item has already been reviewed.');
        }

        $validated = $request->validate([
            'category'   => 'required|string|in:' . implode(',', array_keys(Expense::CATEGORIES)),
            'account_id' => 'required|exists:accounts,id',
        ]);

        DB::transaction(function () use ($lineItem, $validated) {
            $lineItem->update([
                'category'   => $validated['category'],
                'account_id' => $validated['account_id'],
            ]);

            $this->createJournalEntry($lineItem);
        });

        return back()->with('success', "Line item accepted and recorded in chart of accounts.");
    }

    /** Reject a draft line item. */
    public function reject(DocumentLineItem $lineItem)
    {
        if (! $lineItem->isDraft()) {
            return back()->with('error', 'This line item has already been reviewed.');
        }

        $lineItem->update([
            'status'      => DocumentLineItem::STATUS_REJECTED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Line item rejected.');
    }

    /** Accept all draft line items for a document at once. */
    public function bulkAccept(Request $request, Document $document)
    {
        $validated = $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.id'         => 'required|exists:document_line_items,id',
            'items.*.category'   => 'required|string|in:' . implode(',', array_keys(Expense::CATEGORIES)),
            'items.*.account_id' => 'required|exists:accounts,id',
        ]);

        $accepted = 0;

        DB::transaction(function () use ($validated, $document, &$accepted) {
            foreach ($validated['items'] as $itemData) {
                $lineItem = DocumentLineItem::where('id', $itemData['id'])
                    ->where('document_id', $document->id)
                    ->where('status', DocumentLineItem::STATUS_DRAFT)
                    ->first();

                if (! $lineItem) {
                    continue;
                }

                $lineItem->update([
                    'category'   => $itemData['category'],
                    'account_id' => $itemData['account_id'],
                ]);

                $this->createJournalEntry($lineItem);
                $accepted++;
            }
        });

        return back()->with('success', "{$accepted} line item(s) accepted and recorded.");
    }

    /** Bulk reject all draft line items for a document. */
    public function bulkReject(Document $document)
    {
        $count = $document->lineItems()
            ->where('status', DocumentLineItem::STATUS_DRAFT)
            ->update([
                'status'      => DocumentLineItem::STATUS_REJECTED,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

        return back()->with('success', "{$count} line item(s) rejected.");
    }

    /** Create a journal entry for an accepted line item (debit expense account, credit cash). */
    private function createJournalEntry(DocumentLineItem $lineItem): void
    {
        $expenseAccount = Account::general()
            ->where('id', $lineItem->account_id)
            ->where('is_active', true)
            ->first();

        $cashAccount = Account::general()
            ->where('code', self::CASH_ACCOUNT_CODE)
            ->where('is_active', true)
            ->first();

        if (! $expenseAccount || ! $cashAccount) {
            // Still mark as accepted even if accounts are missing
            $lineItem->update([
                'status'      => DocumentLineItem::STATUS_ACCEPTED,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);
            return;
        }

        $document = $lineItem->document;
        $memo = "Line item: {$lineItem->description}";
        if ($document) {
            $memo = "{$document->original_filename} — {$lineItem->description}";
        }

        $journalEntry = JournalEntry::create([
            'entry_number' => JournalEntry::generateEntryNumber(),
            'entry_date'   => now(),
            'memo'         => $memo,
            'reference'    => $document ? "DOC-{$document->id}" : null,
            'source_type'  => Document::class,
            'source_id'    => $lineItem->document_id,
            'status'       => JournalEntry::STATUS_DRAFT,
            'created_by'   => Auth::id(),
        ]);

        JournalLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id'       => $expenseAccount->id,
            'debit'            => $lineItem->amount,
            'credit'           => 0,
            'description'      => $lineItem->description,
        ]);

        JournalLine::create([
            'journal_entry_id' => $journalEntry->id,
            'account_id'       => $cashAccount->id,
            'debit'            => 0,
            'credit'           => $lineItem->amount,
            'description'      => $lineItem->description,
        ]);

        $lineItem->update([
            'status'                   => DocumentLineItem::STATUS_ACCEPTED,
            'created_journal_entry_id' => $journalEntry->id,
            'reviewed_by'              => Auth::id(),
            'reviewed_at'              => now(),
        ]);
    }
}
