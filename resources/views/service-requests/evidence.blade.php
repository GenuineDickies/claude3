{{-- Service Request Evidence Package — service-requests.evidence | Feature preservation notes: Back link to service-requests.show; Print/Save PDF button (window.print); Service Details grid (customer, phone, service type, status via x-status-badge, location, GPS coordinates + location_shared_at); Photos section grouped by type (before/during/after) via route('photos.show'); Customer Signature block with signature_data img, signer_name, signed_at, ip_address, user_agent; Payment Records table using methodLabel() and totalPayments(); SMS Consent status via hasSmsConsent() + sms_consent_at; Receipts list with receipts.pdf link; Complete Activity Log merging statusLogs + serviceLogs into a unified timeline with STATUS_LABELS mapping; Evidence Completeness checklist with dynamic score/percentage bar. Layout: max-w-7xl + space-y-4 for wider internal-tool layout; All Alpine state, forms, routes, and PHP logic kept intact. --}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start gap-3">
        <div>
            <a href="{{ route('service-requests.show', $serviceRequest) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-cyan-400 mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Service Request #{{ $serviceRequest->id }}
            </a>
            <h1 class="text-xl sm:text-2xl font-bold text-white">Evidence Package</h1>
            <p class="text-sm text-gray-500">Service Request #{{ $serviceRequest->id }} &middot; {{ $serviceRequest->created_at->format('M j, Y') }}</p>
        </div>
        <button onclick="window.print()" class="bg-gray-600 text-white text-sm font-medium px-4 py-2 min-h-[44px] rounded-md hover:bg-gray-700 transition print:hidden">
            Print / Save PDF
        </button>
    </div>

    {{-- Service Details --}}
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-3">Service Details</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="block text-gray-500">Customer</span>
                <span class="font-medium">{{ $serviceRequest->customer?->first_name }} {{ $serviceRequest->customer?->last_name }}</span>
            </div>
            <div>
                <span class="block text-gray-500">Phone</span>
                <span class="font-medium font-mono">{{ $serviceRequest->customer?->phone }}</span>
            </div>
            <div>
                <span class="block text-gray-500">Service Type</span>
                <span class="font-medium">{{ $serviceRequest->catalogItem?->name ?? '—' }}</span>
            </div>
            <div>
                <span class="block text-gray-500">Status</span>
                <x-status-badge :status="$serviceRequest->status" />
            </div>
            @if ($serviceRequest->location)
            <div class="col-span-2">
                <span class="block text-gray-500">Location</span>
                <span class="font-medium">{{ $serviceRequest->location }}</span>
            </div>
            @endif
            @if ($serviceRequest->latitude && $serviceRequest->longitude)
            <div class="col-span-2">
                <span class="block text-gray-500">GPS Coordinates</span>
                <span class="font-medium font-mono">{{ $serviceRequest->latitude }}, {{ $serviceRequest->longitude }}</span>
                @if ($serviceRequest->location_shared_at)
                    <span class="text-xs text-gray-400 ml-2">Shared {{ $serviceRequest->location_shared_at->format('M j, Y g:i A') }}</span>
                @endif
            </div>
            @endif
        </div>
    </div>

    {{-- Photos --}}
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-3">Photos ({{ $serviceRequest->photos->count() }})</h2>
        @if ($serviceRequest->photos->isNotEmpty())
            @foreach (['before' => 'Before', 'during' => 'During', 'after' => 'After'] as $type => $label)
                @php $typed = $serviceRequest->photos->where('type', $type); @endphp
                @if ($typed->isNotEmpty())
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mt-3 mb-2">{{ $label }}</p>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach ($typed as $photo)
                            <div>
                                <img src="{{ route('photos.show', [$serviceRequest, $photo]) }}"
                                     alt="{{ $photo->caption ?: $label }}"
                                     class="w-full h-32 object-cover rounded-md border border-white/10">
                                @if ($photo->caption)
                                    <p class="text-xs text-gray-500 mt-1">{{ $photo->caption }}</p>
                                @endif
                                <p class="text-xs text-gray-400">{{ $photo->taken_at?->format('M j, Y g:i A') }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endforeach
        @else
            <p class="text-sm text-gray-400 italic">No photos captured.</p>
        @endif
    </div>

    {{-- Customer Signature --}}
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-3">Customer Signature</h2>
        @php $sig = $serviceRequest->signatures->first(fn($s) => !empty($s->signature_data)); @endphp
        @if ($sig)
            <div class="border border-white/10 rounded-md p-4 text-center">
                <img src="{{ $sig->signature_data }}" alt="Signature" class="max-h-24 mx-auto">
                <p class="text-sm text-gray-400 mt-2">
                    <strong>{{ $sig->signer_name }}</strong> &middot; {{ $sig->signed_at->format('M j, Y g:i A') }}
                </p>
                <p class="text-xs text-gray-400">IP: {{ $sig->ip_address }} &middot; {{ $sig->user_agent }}</p>
            </div>
        @else
            <p class="text-sm text-red-500 italic">⚠ No signature captured.</p>
        @endif
    </div>

    {{-- Payment Records --}}
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-3">Payment Records</h2>
        @if ($serviceRequest->paymentRecords->isNotEmpty())
            <table class="table-crystal min-w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-gray-500">
                        <th class="pb-2">Method</th>
                        <th class="pb-2">Amount</th>
                        <th class="pb-2">Reference</th>
                        <th class="pb-2">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($serviceRequest->paymentRecords as $payment)
                    <tr class="border-b border-gray-50">
                        <td class="py-2">{{ $payment->methodLabel() }}</td>
                        <td class="py-2 font-medium">${{ number_format($payment->amount, 2) }}</td>
                        <td class="py-2 text-gray-500">{{ $payment->reference ?: '—' }}</td>
                        <td class="py-2 text-gray-500">{{ $payment->collected_at->format('M j, Y g:i A') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-bold">
                        <td class="pt-2">Total</td>
                        <td class="pt-2">${{ number_format($serviceRequest->totalPayments(), 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        @else
            <p class="text-sm text-red-500 italic">⚠ No payment records.</p>
        @endif
    </div>

    {{-- SMS Consent Record --}}
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-3">SMS Consent</h2>
        @if ($serviceRequest->customer)
            <div class="text-sm">
                @if ($serviceRequest->customer->hasSmsConsent())
                    <p class="text-green-400 font-medium">✓ Customer opted in to SMS</p>
                    @if ($serviceRequest->customer->sms_consent_at)
                        <p class="text-gray-500 text-xs mt-1">Consent granted: {{ $serviceRequest->customer->sms_consent_at->format('M j, Y g:i A') }}</p>
                    @endif
                @else
                    <p class="text-red-500 font-medium">✗ No SMS consent on record</p>
                @endif
            </div>
        @endif
    </div>

    {{-- Receipts --}}
    @if ($serviceRequest->receipts->isNotEmpty())
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-3">Receipts</h2>
        @foreach ($serviceRequest->receipts as $receipt)
            <div class="border border-white/10 rounded-md p-3 flex justify-between items-center">
                <div class="text-sm">
                    <span class="font-medium">{{ $receipt->receipt_number }}</span>
                    <span class="text-gray-400 ml-2">{{ $receipt->created_at->format('M j, Y') }}</span>
                    <span class="font-bold ml-2">${{ number_format($receipt->total, 2) }}</span>
                </div>
                <a href="{{ route('receipts.pdf', [$serviceRequest, $receipt]) }}" class="text-sm text-cyan-400 hover:underline">Download PDF</a>
            </div>
        @endforeach
    </div>
    @endif

    {{-- Complete Activity Log --}}
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-3">Complete Activity Log</h2>
        @php
            // Merge status logs and service logs into a unified timeline
            $timeline = collect();
            foreach ($serviceRequest->statusLogs as $sl) {
                $timeline->push((object)[
                    'time' => $sl->created_at,
                    'type' => 'status',
                    'label' => 'Status: ' . (\App\Models\ServiceRequest::STATUS_LABELS[$sl->old_status] ?? $sl->old_status) . ' → ' . (\App\Models\ServiceRequest::STATUS_LABELS[$sl->new_status] ?? $sl->new_status),
                    'by' => $sl->user?->name,
                    'note' => $sl->notes,
                ]);
            }
            foreach ($serviceRequest->serviceLogs->whereNotIn('event', ['status_change']) as $log) {
                $timeline->push((object)[
                    'time' => $log->logged_at,
                    'type' => $log->event,
                    'label' => $log->eventLabel(),
                    'by' => $log->user?->name,
                    'note' => $log->details['notes'] ?? $log->details['note'] ?? (!empty($log->details['amount']) ? '$' . number_format($log->details['amount'], 2) : null),
                ]);
            }
            $timeline = $timeline->sortBy('time');
        @endphp

        @if ($timeline->isNotEmpty())
            <div class="space-y-2 text-sm">
                @foreach ($timeline as $entry)
                    <div class="flex items-start gap-3 border-b border-gray-50 pb-2">
                        <div class="shrink-0 mt-1"><div class="w-2 h-2 rounded-full bg-gray-400"></div></div>
                        <div>
                            <span class="font-medium text-gray-300">{{ $entry->label }}</span>
                            @if ($entry->by)
                                <span class="text-gray-400">by {{ $entry->by }}</span>
                            @endif
                            <span class="text-xs text-gray-400 ml-1">{{ $entry->time->format('M j, Y g:i A') }}</span>
                            @if ($entry->note)
                                <p class="text-xs text-gray-500 mt-0.5 italic">{{ $entry->note }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400 italic">No activity logged.</p>
        @endif
    </div>

    {{-- Evidence Completeness --}}
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-3">Evidence Completeness</h2>
        @php
            $checks = [
                'GPS Location'       => (bool) ($serviceRequest->latitude && $serviceRequest->longitude),
                'Before Photos'      => $serviceRequest->photos->where('type', 'before')->isNotEmpty(),
                'After Photos'       => $serviceRequest->photos->where('type', 'after')->isNotEmpty(),
                'Customer Signature' => $serviceRequest->signatures->contains(fn($s) => !empty($s->signature_data)),
                'Payment Record'     => $serviceRequest->paymentRecords->isNotEmpty(),
                'SMS Consent'        => $serviceRequest->customer?->hasSmsConsent() ?? false,
                'Receipt Issued'     => $serviceRequest->receipts->isNotEmpty(),
            ];
            $score = collect($checks)->filter()->count();
            $total = count($checks);
        @endphp
        <div class="mb-3">
            <div class="flex justify-between text-sm mb-1">
                <span class="font-medium">{{ $score }} / {{ $total }} items</span>
                <span @class([
                    'font-semibold',
                    'text-green-400' => $score === $total,
                    'text-yellow-600' => $score >= $total / 2 && $score < $total,
                    'text-red-400' => $score < $total / 2,
                ])>
                    {{ round($score / $total * 100) }}%
                </span>
            </div>
            <div class="w-full bg-white/10 rounded-full h-2">
                <div @class([
                    'h-2 rounded-full',
                    'bg-green-500/100' => $score === $total,
                    'bg-yellow-500' => $score >= $total / 2 && $score < $total,
                    'bg-red-500' => $score < $total / 2,
                ]) style="width: {{ round($score / $total * 100) }}%"></div>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-2 text-sm">
            @foreach ($checks as $label => $passed)
                <div class="flex items-center gap-2">
                    @if ($passed)
                        <span class="text-green-500">✓</span>
                    @else
                        <span class="text-red-400">✗</span>
                    @endif
                    <span @class(['text-gray-300' => $passed, 'text-gray-400' => !$passed])>{{ $label }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
