<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Warranty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /** Upload a document attached to a documentable model. */
    public function store(Request $request, Warranty $warranty)
    {
        $validated = $request->validate([
            'file'     => 'required|file|max:20480|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx',
            'category' => 'nullable|string|in:' . implode(',', Document::CATEGORIES),
        ]);

        $file = $request->file('file');
        $path = $file->store('documents/warranties/' . $warranty->id, 'local');

        $warranty->documents()->create([
            'file_path'         => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type'         => $file->getMimeType(),
            'file_size'         => $file->getSize(),
            'category'          => $validated['category'] ?? 'warranty_doc',
            'uploaded_by'       => Auth::id(),
        ]);

        return redirect()->route('warranties.show', [
            $warranty->service_request_id,
            $warranty,
        ])->with('success', 'Document uploaded.');
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
}
