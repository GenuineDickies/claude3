<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\ServiceLog;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Models\WorkOrder;
use App\Services\ChangeOrderAuthorizationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    /**
     * Show the form to create an invoice from a completed work order.
     */
    public function create(
        ServiceRequest $serviceRequest,
        WorkOrder $workOrder,
        ChangeOrderAuthorizationService $changeOrderAuthorizationService,
    )
    {
        abort_if($workOrder->service_request_id !== $serviceRequest->id, 404);

        if ($changeOrderAuthorizationService->hasBlockingPendingApproval($serviceRequest)) {
            return redirect()
                ->route('work-orders.show', [$serviceRequest, $workOrder])
                ->with('error', 'Cannot create invoice while a change order is pending approval.');
        }

        $serviceRequest->load(['customer', 'catalogItem']);
        $workOrder->load('items');

        $workOrderItems = $workOrder->items->map(fn ($i) => [
            'name'        => $i->name,
            'description' => $i->description ?? '',
            'quantity'    => (float) $i->quantity,
            'unit'        => $i->unit ?? 'ea',
            'unit_price'  => (float) $i->unit_price,
        ])->values()->all();

        return view('invoices.create', [
            'serviceRequest' => $serviceRequest,
            'workOrder'      => $workOrder,
            'workOrderItems' => $workOrderItems,
        ]);
    }

    /**
     * Persist a new invoice sourced from a work order.
     */
    public function store(
        Request $request,
        ServiceRequest $serviceRequest,
        WorkOrder $workOrder,
        ChangeOrderAuthorizationService $changeOrderAuthorizationService,
    )
    {
        abort_if($workOrder->service_request_id !== $serviceRequest->id, 404);

        if ($changeOrderAuthorizationService->hasBlockingPendingApproval($serviceRequest)) {
            return back()
                ->withInput()
                ->withErrors([
                    'invoice' => 'Cannot create invoice while a change order is pending approval.',
                ]);
        }

        $validated = $request->validate([
            'customer_name'      => 'required|string|max:200',
            'customer_phone'     => 'nullable|string|max:20',
            'vehicle_description' => 'nullable|string|max:200',
            'service_description' => 'nullable|string|max:200',
            'service_location'   => 'nullable|string|max:500',
            'line_items'         => 'required|array|min:1',
            'line_items.*.name'  => 'required|string|max:200',
            'line_items.*.description' => 'nullable|string|max:500',
            'line_items.*.quantity' => 'required|numeric|min:0.01',
            'line_items.*.unit'  => 'nullable|string|max:20',
            'line_items.*.unit_price' => 'required|numeric|min:0',
            'subtotal'           => 'required|numeric|min:0',
            'tax_rate'           => 'nullable|numeric|min:0|max:100',
            'tax_amount'         => 'required|numeric|min:0',
            'total'              => 'required|numeric|min:0',
            'due_date'           => 'nullable|date',
            'payment_terms'      => 'nullable|string|max:200',
            'notes'              => 'nullable|string|max:2000',
        ]);

        $invoice = Invoice::create([
            'service_request_id'  => $serviceRequest->id,
            'work_order_id'       => $workOrder->id,
            'invoice_number'      => Invoice::generateInvoiceNumber(),
            'status'              => Invoice::STATUS_DRAFT,
            'customer_name'       => $validated['customer_name'],
            'customer_phone'      => $validated['customer_phone'] ?? null,
            'vehicle_description' => $validated['vehicle_description'] ?? null,
            'service_description' => $validated['service_description'] ?? null,
            'service_location'    => $validated['service_location'] ?? null,
            'line_items'          => $validated['line_items'],
            'subtotal'            => $validated['subtotal'],
            'tax_rate'            => $validated['tax_rate'] ?? 0,
            'tax_amount'          => $validated['tax_amount'],
            'total'               => $validated['total'],
            'due_date'            => $validated['due_date'] ?? null,
            'payment_terms'       => $validated['payment_terms'] ?? null,
            'notes'               => $validated['notes'] ?? null,
            'issued_by'           => Auth::id(),
            'company_snapshot'    => [
                'name'    => Setting::getValue('company_name', config('app.name')),
                'address' => Setting::getValue('company_address', ''),
                'phone'   => Setting::getValue('company_phone', ''),
                'email'   => Setting::getValue('company_email', ''),
            ],
        ]);

        return redirect()->route('invoices.show', [$serviceRequest, $invoice])
            ->with('success', 'Invoice ' . $invoice->invoice_number . ' created.');
    }

    public function show(ServiceRequest $serviceRequest, Invoice $invoice)
    {
        abort_if($invoice->service_request_id !== $serviceRequest->id, 404);

        $versions = $invoice->allVersions();

        return view('invoices.show', [
            'serviceRequest' => $serviceRequest,
            'invoice'        => $invoice,
            'versions'       => $versions,
        ]);
    }

    /**
     * POST /service-requests/{sr}/invoices/{invoice}/revise
     * Create a new draft version from a sent invoice.
     */
    public function revise(ServiceRequest $serviceRequest, Invoice $invoice)
    {
        abort_if($invoice->service_request_id !== $serviceRequest->id, 404);
        abort_if($invoice->is_locked, 403, 'This version is already locked.');
        abort_unless($invoice->status === Invoice::STATUS_SENT, 403, 'Only sent invoices can be revised.');

        $newVersion = $invoice->createNewVersion();

        ServiceLog::log($serviceRequest, 'invoice_revised', [
            'old_version'    => $invoice->version,
            'new_version'    => $newVersion->version,
            'invoice_id'     => $newVersion->id,
            'invoice_number' => $newVersion->invoice_number,
        ], Auth::id());

        return redirect()
            ->route('invoices.edit', [$serviceRequest, $newVersion])
            ->with('success', "Revision V{$newVersion->version} created from locked V{$invoice->version}.");
    }

    /**
     * GET /service-requests/{sr}/invoices/{invoice}/edit
     */
    public function edit(ServiceRequest $serviceRequest, Invoice $invoice)
    {
        abort_if($invoice->service_request_id !== $serviceRequest->id, 404);
        abort_if($invoice->is_locked, 403, 'This invoice version is locked.');

        return view('invoices.edit', [
            'serviceRequest' => $serviceRequest,
            'invoice'        => $invoice,
        ]);
    }

    /**
     * PUT /service-requests/{sr}/invoices/{invoice}
     */
    public function update(Request $request, ServiceRequest $serviceRequest, Invoice $invoice)
    {
        abort_if($invoice->service_request_id !== $serviceRequest->id, 404);
        abort_if($invoice->is_locked, 403, 'This invoice version is locked.');

        $validated = $request->validate([
            'customer_name'       => 'required|string|max:200',
            'customer_phone'      => 'nullable|string|max:20',
            'vehicle_description' => 'nullable|string|max:200',
            'service_description' => 'nullable|string|max:200',
            'service_location'    => 'nullable|string|max:500',
            'line_items'          => 'required|array|min:1',
            'line_items.*.name'   => 'required|string|max:200',
            'line_items.*.description' => 'nullable|string|max:500',
            'line_items.*.quantity' => 'required|numeric|min:0.01',
            'line_items.*.unit'   => 'nullable|string|max:20',
            'line_items.*.unit_price' => 'required|numeric|min:0',
            'subtotal'            => 'required|numeric|min:0',
            'tax_rate'            => 'nullable|numeric|min:0|max:100',
            'tax_amount'          => 'required|numeric|min:0',
            'total'               => 'required|numeric|min:0',
            'due_date'            => 'nullable|date',
            'payment_terms'       => 'nullable|string|max:200',
            'notes'               => 'nullable|string|max:2000',
        ]);

        $invoice->update([
            'customer_name'       => $validated['customer_name'],
            'customer_phone'      => $validated['customer_phone'] ?? null,
            'vehicle_description' => $validated['vehicle_description'] ?? null,
            'service_description' => $validated['service_description'] ?? null,
            'service_location'    => $validated['service_location'] ?? null,
            'line_items'          => $validated['line_items'],
            'subtotal'            => $validated['subtotal'],
            'tax_rate'            => $validated['tax_rate'] ?? 0,
            'tax_amount'          => $validated['tax_amount'],
            'total'               => $validated['total'],
            'due_date'            => $validated['due_date'] ?? null,
            'payment_terms'       => $validated['payment_terms'] ?? null,
            'notes'               => $validated['notes'] ?? null,
        ]);

        return redirect()->route('invoices.show', [$serviceRequest, $invoice])
            ->with('success', 'Invoice updated.');
    }

    public function updateStatus(Request $request, ServiceRequest $serviceRequest, Invoice $invoice)
    {
        abort_if($invoice->service_request_id !== $serviceRequest->id, 404);

        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', Invoice::STATUSES),
        ]);

        if (! $invoice->canTransitionTo($validated['status'])) {
            return redirect()->route('invoices.show', [$serviceRequest, $invoice])
                ->with('error', "Cannot change invoice status from '{$invoice->status}' to '{$validated['status']}'.");
        }

        $invoice->update(['status' => $validated['status']]);

        return redirect()->route('invoices.show', [$serviceRequest, $invoice])
            ->with('success', 'Invoice status updated to ' . $validated['status'] . '.');
    }

    public function pdf(ServiceRequest $serviceRequest, Invoice $invoice)
    {
        abort_if($invoice->service_request_id !== $serviceRequest->id, 404);

        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice]);
        $pdf->setPaper('letter');

        $filename = $invoice->invoice_number . '.pdf';

        return $pdf->download($filename);
    }
}
