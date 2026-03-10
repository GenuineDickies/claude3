<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\CatalogItem;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Models\VendorDocumentLine;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Manages vendor receipts and invoices from draft capture through posting, payment, and file attachments.
 */
class VendorDocumentController extends Controller
{
    /**
     * Show the vendor document index with filters for type, status, vendor, date range, and search text.
     */
    public function index(Request $request)
    {
        $query = VendorDocument::with('vendor')->latest('document_date');

        if ($type = $request->input('type')) {
            $query->where('document_type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($vendorId = $request->input('vendor_id')) {
            $query->where('vendor_id', $vendorId);
        }

        if ($from = $request->input('from')) {
            $query->whereDate('document_date', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('document_date', '<=', $to);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('vendor_document_number', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn ($v) => $v->where('name', 'like', "%{$search}%"));
            });
        }

        $documents = $query->paginate(25)->withQueryString();
        $vendors = Vendor::active()->orderBy('name')->get(['id', 'name']);

        return view('vendor-documents.index', [
            'documents'     => $documents,
            'vendors'       => $vendors,
            'currentType'   => $type,
            'currentStatus' => $status,
            'currentVendor' => $vendorId,
            'currentSearch' => $search,
            'currentFrom'   => $from,
            'currentTo'     => $to,
        ]);
    }

    /**
     * Show the create form with vendors, expense accounts, and catalog parts.
     */
    public function create(Request $request)
    {
        $vendors = Vendor::active()->orderBy('name')->get(['id', 'name']);
        $expenseAccounts = Account::general()
            ->where('is_active', true)
            ->whereIn('type', ['expense', 'cogs'])
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $catalogParts = CatalogItem::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'cogs_account_id']);

        $preselectedVendor = $request->input('vendor_id');

        return view('vendor-documents.create', compact(
            'vendors',
            'expenseAccounts',
            'catalogParts',
            'preselectedVendor',
        ));
    }

    /**
     * Create a draft vendor document with validated lines inside a database transaction.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id'              => 'required|exists:vendors,id',
            'document_type'          => ['required', Rule::in(array_keys(VendorDocument::TYPES))],
            'document_date'          => 'required|date',
            'vendor_document_number' => 'nullable|string|max:100',
            'payment_method'         => ['nullable', Rule::in(array_keys(VendorDocument::PAYMENT_METHODS))],
            'is_paid'               => 'boolean',
            'notes'                  => 'nullable|string|max:5000',
            'lines'                  => 'required|array|min:1',
            'lines.*.line_type'      => ['required', Rule::in(array_keys(VendorDocumentLine::TYPES))],
            'lines.*.description'    => 'required|string|max:300',
            'lines.*.part_id'        => 'nullable|exists:catalog_items,id',
            'lines.*.qty'            => 'required|numeric|min:0.001',
            'lines.*.unit_cost'      => 'required|numeric|min:0',
            'lines.*.core_amount'    => 'nullable|numeric|min:0',
            'lines.*.taxable'        => 'boolean',
            'lines.*.expense_account_id' => 'nullable|exists:accounts,id',
        ]);

        $doc = DB::transaction(function () use ($validated) {
            $doc = VendorDocument::create([
                'vendor_id'              => $validated['vendor_id'],
                'document_type'          => $validated['document_type'],
                'document_date'          => $validated['document_date'],
                'vendor_document_number' => $validated['vendor_document_number'] ?? null,
                'payment_method'         => $validated['payment_method'] ?? null,
                'is_paid'               => $validated['is_paid'] ?? ($validated['document_type'] === VendorDocument::TYPE_RECEIPT),
                'paid_at'               => ($validated['is_paid'] ?? false) ? now() : null,
                'status'                 => VendorDocument::STATUS_DRAFT,
                'notes'                  => $validated['notes'] ?? null,
                'created_by'             => Auth::id(),
                'subtotal'               => 0,
                'tax_total'              => 0,
                'shipping_total'         => 0,
                'total'                  => 0,
            ]);

            foreach ($validated['lines'] as $lineData) {
                $lineTotal = round((float) $lineData['qty'] * (float) $lineData['unit_cost'], 2);

                $doc->lines()->create([
                    'line_type'          => $lineData['line_type'],
                    'description'        => $lineData['description'],
                    'part_id'            => $lineData['part_id'] ?? null,
                    'qty'                => $lineData['qty'],
                    'unit_cost'          => $lineData['unit_cost'],
                    'line_total'         => $lineTotal,
                    'core_amount'        => $lineData['core_amount'] ?? 0,
                    'taxable'            => $lineData['taxable'] ?? false,
                    'expense_account_id' => $lineData['expense_account_id'] ?? null,
                ]);
            }

            $doc->recalculate();
            return $doc;
        });

        return redirect()->route('vendor-documents.show', $doc)
            ->with('success', 'Vendor document created.');
    }

    /**
     * Show a vendor document with posting, attachment, and accounting-link context.
     */
    public function show(VendorDocument $vendorDocument)
    {
        $vendorDocument->load([
            'vendor',
            'lines.part',
            'lines.expenseAccount',
            'attachments',
            'creator',
            'poster',
            'accountingLinks.journalEntry',
        ]);

        return view('vendor-documents.show', [
            'doc' => $vendorDocument,
        ]);
    }

    /**
     * Show the edit form for draft-only vendor documents.
     */
    public function edit(VendorDocument $vendorDocument)
    {
        abort_unless($vendorDocument->isDraft(), 403, 'Only draft documents can be edited.');

        $vendorDocument->load('lines');

        $vendors = Vendor::active()->orderBy('name')->get(['id', 'name']);
        $expenseAccounts = Account::general()
            ->where('is_active', true)
            ->whereIn('type', ['expense', 'cogs'])
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $catalogParts = CatalogItem::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'cogs_account_id']);

        return view('vendor-documents.edit', [
            'doc'             => $vendorDocument,
            'vendors'         => $vendors,
            'expenseAccounts' => $expenseAccounts,
            'catalogParts'    => $catalogParts,
        ]);
    }

    /**
     * Replace a draft vendor document's header and lines, then recalculate totals.
     */
    public function update(Request $request, VendorDocument $vendorDocument)
    {
        abort_unless($vendorDocument->isDraft(), 403, 'Only draft documents can be edited.');

        $validated = $request->validate([
            'vendor_id'              => 'required|exists:vendors,id',
            'document_type'          => ['required', Rule::in(array_keys(VendorDocument::TYPES))],
            'document_date'          => 'required|date',
            'vendor_document_number' => 'nullable|string|max:100',
            'payment_method'         => ['nullable', Rule::in(array_keys(VendorDocument::PAYMENT_METHODS))],
            'is_paid'               => 'boolean',
            'notes'                  => 'nullable|string|max:5000',
            'lines'                  => 'required|array|min:1',
            'lines.*.line_type'      => ['required', Rule::in(array_keys(VendorDocumentLine::TYPES))],
            'lines.*.description'    => 'required|string|max:300',
            'lines.*.part_id'        => 'nullable|exists:catalog_items,id',
            'lines.*.qty'            => 'required|numeric|min:0.001',
            'lines.*.unit_cost'      => 'required|numeric|min:0',
            'lines.*.core_amount'    => 'nullable|numeric|min:0',
            'lines.*.taxable'        => 'boolean',
            'lines.*.expense_account_id' => 'nullable|exists:accounts,id',
        ]);

        DB::transaction(function () use ($validated, $vendorDocument) {
            $vendorDocument->update([
                'vendor_id'              => $validated['vendor_id'],
                'document_type'          => $validated['document_type'],
                'document_date'          => $validated['document_date'],
                'vendor_document_number' => $validated['vendor_document_number'] ?? null,
                'payment_method'         => $validated['payment_method'] ?? null,
                'is_paid'               => $validated['is_paid'] ?? false,
                'paid_at'               => ($validated['is_paid'] ?? false) ? ($vendorDocument->paid_at ?? now()) : null,
                'notes'                  => $validated['notes'] ?? null,
            ]);

            // Replace all lines
            $vendorDocument->lines()->delete();

            foreach ($validated['lines'] as $lineData) {
                $lineTotal = round((float) $lineData['qty'] * (float) $lineData['unit_cost'], 2);

                $vendorDocument->lines()->create([
                    'line_type'          => $lineData['line_type'],
                    'description'        => $lineData['description'],
                    'part_id'            => $lineData['part_id'] ?? null,
                    'qty'                => $lineData['qty'],
                    'unit_cost'          => $lineData['unit_cost'],
                    'line_total'         => $lineTotal,
                    'core_amount'        => $lineData['core_amount'] ?? 0,
                    'taxable'            => $lineData['taxable'] ?? false,
                    'expense_account_id' => $lineData['expense_account_id'] ?? null,
                ]);
            }

            $vendorDocument->recalculate();
        });

        return redirect()->route('vendor-documents.show', $vendorDocument)
            ->with('success', 'Vendor document updated.');
    }

    /**
        * Post a draft vendor document to the general ledger through the accounting service.
     */
    public function post(VendorDocument $vendorDocument)
    {
        abort_unless($vendorDocument->isDraft(), 403, 'Only draft documents can be posted.');

        DB::transaction(function () use ($vendorDocument) {
            $vendorDocument->update([
                'status'    => VendorDocument::STATUS_POSTED,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
            ]);

            AccountingService::post(VendorDocument::class, $vendorDocument->id, Auth::id());
        });

        return redirect()->route('vendor-documents.show', $vendorDocument)
            ->with('success', 'Document posted to GL.');
    }

    /**
        * Void a posted vendor document and reverse its accounting impact.
     */
    public function void(VendorDocument $vendorDocument)
    {
        abort_unless($vendorDocument->isPosted(), 403, 'Only posted documents can be voided.');

        DB::transaction(function () use ($vendorDocument) {
            AccountingService::reverse(
                VendorDocument::class,
                $vendorDocument->id,
                'Vendor document voided',
                Auth::id(),
            );

            $vendorDocument->update(['status' => VendorDocument::STATUS_VOID]);
        });

        return redirect()->route('vendor-documents.show', $vendorDocument)
            ->with('success', 'Document voided and GL entries reversed.');
    }

    /**
        * Mark a posted unpaid vendor invoice as paid and create the A/P clearing entry.
     */
    public function pay(Request $request, VendorDocument $vendorDocument)
    {
        abort_unless($vendorDocument->isPosted(), 403, 'Document must be posted first.');
        abort_if($vendorDocument->is_paid, 403, 'Document is already paid.');

        $validated = $request->validate([
            'payment_method' => ['required', Rule::in(array_keys(VendorDocument::PAYMENT_METHODS))],
        ]);

        DB::transaction(function () use ($vendorDocument, $validated) {
            $vendorDocument->update([
                'is_paid'        => true,
                'paid_at'        => now(),
                'payment_method' => $validated['payment_method'],
            ]);

            AccountingService::postVendorBillPayment(
                $vendorDocument,
                $validated['payment_method'],
                Auth::id(),
            );
        });

        return redirect()->route('vendor-documents.show', $vendorDocument)
            ->with('success', 'Payment recorded and GL updated.');
    }

    /**
        * Store an uploaded attachment on local disk and link it to the vendor document.
     */
    public function storeAttachment(Request $request, VendorDocument $vendorDocument)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,pdf|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store('vendor-documents', 'local');

        $vendorDocument->attachments()->create([
            'file_path'         => $path,
            'file_type'         => $file->getClientMimeType(),
            'original_filename' => $file->getClientOriginalName(),
            'file_size'         => $file->getSize(),
            'uploaded_at'       => now(),
        ]);

        return redirect()->route('vendor-documents.show', $vendorDocument)
            ->with('success', 'Attachment uploaded.');
    }

    /**
        * Download a persisted attachment for the vendor document.
     */
    public function downloadAttachment(VendorDocument $vendorDocument, int $attachment)
    {
        $att = $vendorDocument->attachments()->findOrFail($attachment);

        abort_unless(Storage::disk('local')->exists($att->file_path), 404);

        return Storage::disk('local')->download($att->file_path, $att->original_filename);
    }

    /**
        * Delete an attachment from storage and from the database for draft documents only.
     */
    public function deleteAttachment(VendorDocument $vendorDocument, int $attachment)
    {
        abort_unless($vendorDocument->isDraft(), 403, 'Attachments can only be deleted from draft documents.');

        $att = $vendorDocument->attachments()->findOrFail($attachment);
        Storage::disk('local')->delete($att->file_path);
        $att->delete();

        return redirect()->route('vendor-documents.show', $vendorDocument)
            ->with('success', 'Attachment deleted.');
    }

    /**
     * Delete a draft vendor document and all dependent lines and attachments.
     */
    public function destroy(VendorDocument $vendorDocument)
    {
        abort_unless($vendorDocument->isDraft(), 403, 'Only draft documents can be deleted.');

        DB::transaction(function () use ($vendorDocument) {
            // Delete attachments from disk
            foreach ($vendorDocument->attachments as $att) {
                Storage::disk('local')->delete($att->file_path);
            }
            $vendorDocument->attachments()->delete();
            $vendorDocument->lines()->delete();
            $vendorDocument->delete();
        });

        return redirect()->route('vendor-documents.index')
            ->with('success', 'Vendor document deleted.');
    }
}
