<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocumentIntelligenceJob;
use App\Models\Document;
use App\Services\DocumentMatchingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentInboxController extends Controller
{
    /** Map URL slugs to Eloquent model classes (whitelist). */
    private const LINKABLE_TYPES = [
        'warranty'        => \App\Models\Warranty::class,
        'service-request' => \App\Models\ServiceRequest::class,
        'customer'        => \App\Models\Customer::class,
        'invoice'         => \App\Models\Invoice::class,
        'estimate'        => \App\Models\Estimate::class,
        'expense'         => \App\Models\Expense::class,
        'work-order'      => \App\Models\WorkOrder::class,
        'receipt'         => \App\Models\Receipt::class,
    ];

    /** Show the inbox — all docs not yet linked to an entity. */
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');

        // Inbox-wide stats for the summary bar
        $stats = [
            'total'      => Document::inbox()->count(),
            'processing' => Document::inbox()->where('ai_status', 'processing')->orWhere(fn ($q) => $q->whereNull('documentable_type')->where('ai_status', 'pending'))->count(),
            'completed'  => Document::inbox()->where('ai_status', 'completed')->count(),
            'unmatched'  => Document::inbox()->where('match_status', 'unmatched')->where('ai_status', 'completed')->count(),
            'matched'    => Document::inbox()->whereIn('match_status', ['matched', 'manual'])->count(),
            'skipped'    => Document::inbox()->where('match_status', 'skipped')->count(),
            'failed'     => Document::inbox()->where('ai_status', 'failed')->count(),
        ];

        // Category breakdown for completed docs
        $categories = Document::inbox()
            ->where('ai_status', 'completed')
            ->selectRaw("COALESCE(ai_suggested_category, category) as cat, COUNT(*) as cnt")
            ->groupBy('cat')
            ->pluck('cnt', 'cat')
            ->toArray();

        // Count how many can be bulk-accepted (matched with score >= 0.8, still unmatched)
        $autoAcceptable = Document::inbox()
            ->where('match_status', 'unmatched')
            ->where('ai_status', 'completed')
            ->whereNotNull('match_candidates')
            ->get()
            ->filter(fn ($d) => !empty($d->match_candidates) && $d->match_candidates[0]['score'] >= 0.8)
            ->count();

        $query = Document::inbox()->with('uploader')->latest();

        $query = match ($filter) {
            'unmatched'  => $query->where('match_status', 'unmatched')->where('ai_status', 'completed'),
            'matched'    => $query->whereIn('match_status', ['matched', 'manual']),
            'processing' => $query->whereIn('ai_status', ['processing', 'pending']),
            'failed'     => $query->where('ai_status', 'failed'),
            'skipped'    => $query->where('match_status', 'skipped'),
            default      => $query,
        };

        $documents = $query->paginate(25)->withQueryString();

        $isProcessing = $stats['processing'] > 0;

        return view('documents.inbox', compact('documents', 'filter', 'stats', 'categories', 'autoAcceptable', 'isProcessing'));
    }

    /** Bulk-upload files into the inbox (no parent entity). */
    public function upload(Request $request)
    {
        $request->validate([
            'files'   => 'required|array|min:1|max:100',
            'files.*' => 'required|file|max:1048576|mimes:jpg,jpeg,png,webp,heic,heif,pdf,doc,docx,xls,xlsx',
        ]);

        $uploaded = 0;

        foreach ($request->file('files') as $file) {
            $path = $file->store('documents/inbox', 'local');

            $document = Document::create([
                'file_path'         => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type'         => $file->getMimeType(),
                'file_size'         => $file->getSize(),
                'category'          => 'other',
                'uploaded_by'       => Auth::id(),
                'documentable_type' => null,
                'documentable_id'   => null,
                'match_status'      => 'unmatched',
            ]);

            if (config('services.document_ai.enabled')) {
                ProcessDocumentIntelligenceJob::dispatch($document);
            }

            $uploaded++;
        }

        return redirect()->route('inbox.index')
            ->with('success', "{$uploaded} document(s) uploaded and queued for analysis.");
    }

    /** Manually link a document to an entity. */
    public function link(Request $request, Document $document)
    {
        $request->validate([
            'type' => 'required|string|in:' . implode(',', array_keys(self::LINKABLE_TYPES)),
            'id'   => 'required|integer|min:1',
        ]);

        $modelClass = self::LINKABLE_TYPES[$request->type];
        $entity = $modelClass::findOrFail($request->id);

        $document->linkTo($entity, 'manual');

        return redirect()->route('inbox.index')
            ->with('success', "Document linked to {$request->type} #{$request->id}.");
    }

    /** Accept the top auto-match suggestion. */
    public function acceptMatch(Document $document)
    {
        $candidates = $document->match_candidates ?? [];

        if (empty($candidates)) {
            return back()->with('error', 'No match candidates available.');
        }

        $top = $candidates[0];
        $modelClass = self::LINKABLE_TYPES[$top['type']] ?? null;

        if (! $modelClass) {
            return back()->with('error', 'Invalid match type.');
        }

        $entity = $modelClass::findOrFail($top['id']);
        $document->linkTo($entity, 'matched');

        return redirect()->route('inbox.index')
            ->with('success', "Document linked to {$top['label']}.");
    }

    /** Bulk-accept all high-confidence matches at once. */
    public function bulkAccept()
    {
        $accepted = 0;

        $documents = Document::inbox()
            ->where('match_status', 'unmatched')
            ->where('ai_status', 'completed')
            ->whereNotNull('match_candidates')
            ->get();

        foreach ($documents as $doc) {
            $candidates = $doc->match_candidates ?? [];
            if (empty($candidates) || $candidates[0]['score'] < 0.8) {
                continue;
            }

            $top = $candidates[0];
            $modelClass = self::LINKABLE_TYPES[$top['type']] ?? null;
            if (! $modelClass) {
                continue;
            }

            $entity = $modelClass::find($top['id']);
            if (! $entity) {
                continue;
            }

            $doc->linkTo($entity, 'matched');
            $accepted++;
        }

        return redirect()->route('inbox.index')
            ->with('success', "{$accepted} document(s) auto-accepted.");
    }

    /** Mark a document as skipped (user reviewed but chose not to match). */
    public function skip(Document $document)
    {
        $document->update(['match_status' => 'skipped']);

        return redirect()->route('inbox.index')
            ->with('success', 'Document marked as skipped.');
    }

    /** Re-run matching against current database records. */
    public function rematch(Document $document, DocumentMatchingService $matchingService)
    {
        if ($document->ai_status !== 'completed') {
            return back()->with('error', 'AI analysis must complete before matching.');
        }

        // Reset to unmatched so matching can re-run
        $document->update([
            'match_status'      => 'unmatched',
            'match_candidates'  => null,
            'documentable_type' => null,
            'documentable_id'   => null,
        ]);

        $result = $matchingService->match($document->fresh());

        $msg = $result['matched']
            ? 'Document re-matched successfully!'
            : 'No strong match found. ' . count($result['candidates']) . ' candidate(s) available.';

        return redirect()->route('inbox.index')->with('success', $msg);
    }

    /** Search for entities to manually link (AJAX endpoint). */
    public function search(Request $request)
    {
        $request->validate([
            'type'  => 'required|string|in:' . implode(',', array_keys(self::LINKABLE_TYPES)),
            'query' => 'required|string|min:2|max:100',
        ]);

        $modelClass = self::LINKABLE_TYPES[$request->type];
        $q = $request->query('query');
        $results = [];

        // Quick search per entity type
        $query = match ($request->type) {
            'invoice'         => $modelClass::where('invoice_number', 'LIKE', "%{$q}%")->limit(10),
            'expense'         => $modelClass::where('vendor', 'LIKE', "%{$q}%")->orWhere('expense_number', 'LIKE', "%{$q}%")->limit(10),
            'receipt'         => $modelClass::where('receipt_number', 'LIKE', "%{$q}%")->limit(10),
            'warranty'        => $modelClass::where('part_name', 'LIKE', "%{$q}%")->orWhere('vendor_name', 'LIKE', "%{$q}%")->limit(10),
            'work-order'      => $modelClass::where('work_order_number', 'LIKE', "%{$q}%")->limit(10),
            'estimate'        => $modelClass::where('estimate_number', 'LIKE', "%{$q}%")->limit(10),
            'customer'        => $modelClass::where('first_name', 'LIKE', "%{$q}%")->orWhere('last_name', 'LIKE', "%{$q}%")->orWhere('phone', 'LIKE', "%{$q}%")->limit(10),
            'service-request' => $modelClass::where('id', $q)->limit(10),
            default           => $modelClass::limit(10),
        };

        foreach ($query->get() as $item) {
            $results[] = [
                'id'    => $item->id,
                'label' => $this->entityLabel($request->type, $item),
            ];
        }

        return response()->json($results);
    }

    /** Build a human-readable label for an entity. */
    private function entityLabel(string $type, Model $item): string
    {
        return match ($type) {
            'invoice'         => "Invoice {$item->invoice_number}" . ($item->total ? " — \${$item->total}" : ''),
            'expense'         => "Expense {$item->expense_number}" . ($item->vendor ? " — {$item->vendor}" : ''),
            'receipt'         => "Receipt {$item->receipt_number}" . ($item->total ? " — \${$item->total}" : ''),
            'warranty'        => "Warranty: {$item->part_name}" . ($item->vendor_name ? " ({$item->vendor_name})" : ''),
            'work-order'      => "WO {$item->work_order_number}" . ($item->total ? " — \${$item->total}" : ''),
            'estimate'        => "Estimate {$item->estimate_number}" . ($item->total ? " — \${$item->total}" : ''),
            'customer'        => "{$item->first_name} {$item->last_name}" . ($item->phone ? " ({$item->phone})" : ''),
            'service-request' => "SR #{$item->id}" . ($item->vehicle_year ? " — {$item->vehicle_year} {$item->vehicle_make} {$item->vehicle_model}" : ''),
            default           => "#{$item->id}",
        };
    }
}
