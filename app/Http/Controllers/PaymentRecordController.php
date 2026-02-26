<?php

namespace App\Http\Controllers;

use App\Models\PaymentRecord;
use App\Models\ServiceLog;
use App\Models\ServiceRequest;
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
        ]);

        $payment = PaymentRecord::create([
            'service_request_id' => $serviceRequest->id,
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

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', 'Payment of $' . number_format($payment->amount, 2) . ' recorded.');
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
