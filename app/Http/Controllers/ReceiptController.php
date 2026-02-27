<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\ServiceRequest;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceiptController extends Controller
{
    /**
     * Show the form to issue a receipt from an invoice.
     */
    public function create(ServiceRequest $serviceRequest, Invoice $invoice)
    {
        abort_if($invoice->service_request_id !== $serviceRequest->id, 404);

        $serviceRequest->load(['customer', 'catalogItem']);

        $invoiceItems = is_array($invoice->line_items) ? $invoice->line_items : [];

        return view('receipts.create', [
            'serviceRequest' => $serviceRequest,
            'invoice'        => $invoice,
            'invoiceItems'   => $invoiceItems,
        ]);
    }

    /**
     * Persist a new receipt sourced from an invoice.
     */
    public function store(Request $request, ServiceRequest $serviceRequest, Invoice $invoice)
    {
        abort_if($invoice->service_request_id !== $serviceRequest->id, 404);

        $validated = $request->validate([
            'customer_name'     => 'required|string|max:200',
            'customer_phone'    => 'nullable|string|max:20',
            'vehicle_description' => 'nullable|string|max:200',
            'service_description' => 'nullable|string|max:200',
            'service_location'  => 'nullable|string|max:500',
            'line_items'        => 'required|array|min:1',
            'line_items.*.name' => 'required|string|max:200',
            'line_items.*.description' => 'nullable|string|max:500',
            'line_items.*.quantity' => 'required|numeric|min:0.01',
            'line_items.*.unit' => 'nullable|string|max:20',
            'line_items.*.unit_price' => 'required|numeric|min:0',
            'subtotal'          => 'required|numeric|min:0',
            'tax_rate'          => 'nullable|numeric|min:0|max:100',
            'tax_amount'        => 'required|numeric|min:0',
            'total'             => 'required|numeric|min:0',
            'payment_method'    => 'nullable|string|max:30',
            'payment_reference' => 'nullable|string|max:200',
            'payment_date'      => 'nullable|date',
            'notes'             => 'nullable|string|max:2000',
        ]);

        $receipt = Receipt::create([
            'service_request_id' => $serviceRequest->id,
            'invoice_id'         => $invoice->id,
            'receipt_number'     => Receipt::generateReceiptNumber(),
            'customer_name'      => $validated['customer_name'],
            'customer_phone'     => $validated['customer_phone'] ?? null,
            'vehicle_description' => $validated['vehicle_description'] ?? null,
            'service_description' => $validated['service_description'] ?? null,
            'service_location'   => $validated['service_location'] ?? null,
            'line_items'         => $validated['line_items'],
            'subtotal'           => $validated['subtotal'],
            'tax_rate'           => $validated['tax_rate'] ?? 0,
            'tax_amount'         => $validated['tax_amount'],
            'total'              => $validated['total'],
            'payment_method'     => $validated['payment_method'] ?? null,
            'payment_reference'  => $validated['payment_reference'] ?? null,
            'payment_date'       => $validated['payment_date'] ?? null,
            'notes'              => $validated['notes'] ?? null,
            'issued_by'          => Auth::id(),
            'company_snapshot'   => [
                'name'    => Setting::getValue('company_name', config('app.name')),
                'address' => Setting::getValue('company_address', ''),
                'phone'   => Setting::getValue('company_phone', ''),
                'email'   => Setting::getValue('company_email', ''),
            ],
        ]);

        return redirect()->route('receipts.show', [$serviceRequest, $receipt])
            ->with('success', 'Receipt ' . $receipt->receipt_number . ' issued.');
    }

    public function show(ServiceRequest $serviceRequest, Receipt $receipt)
    {
        abort_if($receipt->service_request_id !== $serviceRequest->id, 404);

        return view('receipts.show', [
            'serviceRequest' => $serviceRequest,
            'receipt'        => $receipt,
        ]);
    }

    public function pdf(ServiceRequest $serviceRequest, Receipt $receipt)
    {
        abort_if($receipt->service_request_id !== $serviceRequest->id, 404);

        $pdf = Pdf::loadView('receipts.pdf', ['receipt' => $receipt]);
        $pdf->setPaper('letter');

        $filename = $receipt->receipt_number . '.pdf';

        return $pdf->download($filename);
    }
}
