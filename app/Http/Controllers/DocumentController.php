<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocumentIntelligenceJob;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Estimate;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\ServiceRequest;
use App\Models\Warranty;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /** Map URL slugs to Eloquent model classes (whitelist). */
    private const DOCUMENTABLE_TYPES = [
        'warranty'        => Warranty::class,
        'service-request' => ServiceRequest::class,
        'customer'        => Customer::class,
        'invoice'         => Invoice::class,
        'estimate'        => Estimate::class,
        'expense'         => Expense::class,
        'work-order'      => WorkOrder::class,
    ];

    /** Upload a document attached to a warranty (legacy route). */
    public function store(Request $request, Warranty $warranty)
    {
        $validated = $request->validate([
            'file'     => 'required|file|max:20480|mimes:jpg,jpeg,png,webp,heic,heif,pdf,doc,docx,xls,xlsx',
            'category' => 'nullable|string|in:' . implode(',', Document::CATEGORIES),
        ]);

        $file = $request->file('file');
        $path = $file->store('documents/warranties/' . $warranty->id, 'local');

        $document = $warranty->documents()->create([
            'file_path'         => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type'         => $file->getMimeType(),
            'file_size'         => $file->getSize(),
            'category'          => $validated['category'] ?? 'warranty_doc',
            'uploaded_by'       => Auth::id(),
        ]);

        $this->dispatchAiProcessing($document);

        return redirect()->route('warranties.show', [
            $warranty->service_request_id,
            $warranty,
        ])->with('success', 'Document uploaded.');
    }

    /** Upload a document attached to any documentable model (generic polymorphic route). */
    public function storeGeneric(Request $request, string $type, int $id)
    {
        $modelClass = self::DOCUMENTABLE_TYPES[$type] ?? null;
        abort_unless($modelClass, 404, 'Invalid document owner type.');

        /** @var Model $owner */
        $owner = $modelClass::findOrFail($id);

        $validated = $request->validate([
            'file'     => 'required|file|max:20480|mimes:jpg,jpeg,png,webp,heic,heif,pdf,doc,docx,xls,xlsx',
            'category' => 'nullable|string|in:' . implode(',', Document::CATEGORIES),
        ]);

        $file = $request->file('file');
        $path = $file->store('documents/' . $type . '/' . $id, 'local');

        $document = $owner->documents()->create([
            'file_path'         => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type'         => $file->getMimeType(),
            'file_size'         => $file->getSize(),
            'category'          => $validated['category'] ?? 'other',
            'uploaded_by'       => Auth::id(),
        ]);

        $this->dispatchAiProcessing($document);

        return back()->with('success', 'Document uploaded.');
    }

    /** Download/view a document. */
    public function show(Document $document)
    {
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->response(
            $document->file_path,
            $document->original_filename
        );
    }

    /** Display AI analysis results for a document. */
    public function detail(Document $document)
    {
        $document->load('lineItems.account');
        $lineItems = $document->lineItems;
        $accounts = Account::general()->where('is_active', true)->orderBy('code')->get();
        $categories = Expense::CATEGORIES;

        return view('documents.detail', compact('document', 'lineItems', 'accounts', 'categories'));
    }

    /** Re-dispatch the AI processing job. */
    public function reanalyze(Document $document)
    {
        $document->update([
            'ai_status' => 'pending',
            'ai_error'  => null,
        ]);

        $this->dispatchAiProcessing($document);

        return back()->with('success', 'Document re-analysis queued.');
    }

    /** Accept the AI-suggested category. */
    public function acceptCategory(Document $document)
    {
        $document->acceptAiCategory();

        return back()->with('success', 'Category updated.');
    }

    /** Delete a document. */
    public function destroy(Document $document)
    {
        $documentable = $document->documentable;

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        if ($documentable instanceof Warranty) {
            return redirect()->route('warranties.show', [
                $documentable->service_request_id,
                $documentable,
            ])->with('success', 'Document deleted.');
        }

        return back()->with('success', 'Document deleted.');
    }

    /** Dispatch AI processing if the feature flag is enabled. */
    private function dispatchAiProcessing(Document $document): void
    {
        if (config('services.document_ai.enabled')) {
            ProcessDocumentIntelligenceJob::dispatch($document);
        }
    }
}
