<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\PaymentRecord;
use App\Models\Receipt;
use App\Models\ServiceLog;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Services\StatusAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentRecordController extends Controller
{
    public function store(Request $request, ServiceRequest $serviceRequest)
    {
        $validated = $request->validate([
            'method'       => 'required|string|in:' . implode(',', PaymentRecord::METHODS),
            'amount'       => 'required|numeric|min:0.01',
            'reference'    => 'nullable|string|max:200',
            'collected_at' => 'nullable|date',
            'notes'        => 'nullable|string|max:2000',
            'invoice_id'   => 'nullable|integer|exists:invoices,id',
        ]);

        $payment = PaymentRecord::create([
            'service_request_id' => $serviceRequest->id,
            'invoice_id'         => $validated['invoice_id'] ?? null,
            'method'             => $validated['method'],
            'amount'             => $validated['amount'],
            'reference'          => $validated['reference'] ?? null,
            'collected_at'       => $validated['collected_at'] ?? now(),
            'collected_by'       => Auth::id(),
            'notes'              => $validated['notes'] ?? null,
        ]);

        ServiceLog::log($serviceRequest, 'payment_collected', [
            'payment_id' => $payment->id,
            'method'     => $payment->method,
            'amount'     => $payment->amount,
            'reference'  => $payment->reference,
        ], Auth::id());

        app(StatusAutomationService::class)->handle($serviceRequest, 'payment_collected');

        // Auto-issue receipt for every payment
        $customer = $serviceRequest->customer;
        $receipt = Receipt::create([
            'service_request_id'  => $serviceRequest->id,
            'invoice_id'          => $payment->invoice_id,
            'payment_record_id'   => $payment->id,
            'receipt_number'      => Receipt::generateReceiptNumber(),
            'customer_name'       => $customer
                ? trim($customer->first_name . ' ' . $customer->last_name)
                : 'Customer',
            'customer_phone'      => $customer?->phone,
            'vehicle_description' => trim(implode(' ', array_filter([
                $serviceRequest->vehicle_color,
                $serviceRequest->vehicle_year,
                $serviceRequest->vehicle_make,
                $serviceRequest->vehicle_model,
            ]))) ?: null,
            'service_description' => $payment->invoice_id
                ? 'Invoice Payment'
                : 'Deposit / Prepayment',
            'line_items'          => [[
                'name'       => $payment->invoice_id ? 'Invoice Payment' : 'Customer Deposit',
                'quantity'   => 1,
                'unit_price' => (float) $payment->amount,
            ]],
            'subtotal'            => $payment->amount,
            'tax_rate'            => 0,
            'tax_amount'          => 0,
            'total'               => $payment->amount,
            'payment_method'      => $payment->method,
            'payment_reference'   => $payment->reference,
            'payment_date'        => $payment->collected_at?->toDateString(),
            'notes'               => $payment->notes,
            'issued_by'           => Auth::id(),
            'company_snapshot'    => [
                'name'    => Setting::getValue('company_name', config('app.name')),
                'address' => Setting::getValue('company_address', ''),
                'phone'   => Setting::getValue('company_phone', ''),
                'email'   => Setting::getValue('company_email', ''),
            ],
        ]);

        // For deposits (no invoice), record a journal entry:
        // Debit Cash, Credit Customer Deposits
        if (!$payment->invoice_id) {
            $cash     = Account::where('code', '1100')->first();
            $deposits = Account::where('code', '2300')->first();

            if ($cash && $deposits) {
                $je = JournalEntry::create([
                    'entry_number' => JournalEntry::generateEntryNumber(),
                    'entry_date'   => $payment->collected_at ?? now(),
                    'memo'         => 'Customer deposit – ' . $receipt->receipt_number,
                    'source_type'  => PaymentRecord::class,
                    'source_id'    => $payment->id,
                    'status'       => JournalEntry::STATUS_POSTED,
                    'created_by'   => Auth::id(),
                    'posted_by'    => Auth::id(),
                    'posted_at'    => now(),
                ]);

                $je->lines()->createMany([
                    ['account_id' => $cash->id,     'debit' => $payment->amount, 'credit' => 0],
                    ['account_id' => $deposits->id, 'debit' => 0,                'credit' => $payment->amount],
                ]);
            }
        }

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', 'Payment of $' . number_format($payment->amount, 2)
                . ' recorded. Receipt ' . $receipt->receipt_number . ' issued.');
    }

    public function destroy(ServiceRequest $serviceRequest, PaymentRecord $payment)
    {
        abort_unless($payment->service_request_id === $serviceRequest->id, 404);

        ServiceLog::log($serviceRequest, 'payment_deleted', [
            'payment_id' => $payment->id,
            'method'     => $payment->method,
            'amount'     => $payment->amount,
        ], Auth::id());

        $payment->delete();

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', 'Payment record deleted.');
    }
}
