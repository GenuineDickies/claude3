{{--
  Invoice Show Page — invoices.show
  Feature preservation notes:
    - Breadcrumb back link to service request
    - Header card with invoice display number, version badge, locked badge, status badge
    - Created and due_date subtitle
    - Edit link (draft only, not locked)
    - Revise form (sent only, not locked)
    - Status update form (Mark Sent / Mark Paid) via PATCH update-status
    - Download PDF link
    - Issue Receipt link (when paid)
    - Company info card (snapshot name/address/phone/email)
    - Bill To panel (customer_name, customer_phone, vehicle_description, plate, VIN, service_description, service_location)
    - Line Items table (name/description/qty/unit/unit_price/amount)
    - Totals block (subtotal, tax conditional, total due)
    - Payment Terms section (conditional)
    - Notes section (conditional)
    - Version History list (conditional, with status badge and view links)
    - Documents partial
    - Back link
  Layout changes only:
    - Outer container widened from max-w-3xl to max-w-7xl
    - Vertical spacing tightened from space-y-6 to space-y-4
    - All Alpine state, forms, routes, and PHP logic kept intact
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">

    {{-- Breadcrumb --}}
    <a href="{{ route('service-requests.show', $serviceRequest) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-cyan-400">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Service Request #{{ $serviceRequest->id }}
    </a>

    {{-- Header --}}
    <div class="surface-1 p-6">
        <div class="flex justify-between items-start">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-white">Invoice {{ $invoice->displayNumber() }}</h1>
                    @if($invoice->version > 1)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-700">
                            V{{ $invoice->version }}
                        </span>
                    @endif
                    @if($invoice->is_locked)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-white/10 text-gray-400">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                            Locked
                        </span>
                    @endif
                    @php
                        $statusColors = [
                            'draft'     => 'bg-white/5 text-gray-300',
                            'sent'      => 'bg-blue-100 text-cyan-400',
                            'paid'      => 'bg-green-100 text-green-700',
                            'overdue'   => 'bg-red-100 text-red-700',
                            'cancelled' => 'bg-white/5 text-gray-500',
                        ];
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusColors[$invoice->status] ?? 'bg-white/5 text-gray-300' }}">
                        {{ ucfirst($invoice->status) }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">Created {{ $invoice->created_at->format('M j, Y g:i A') }}</p>
                @if ($invoice->due_date)
                    <p class="text-sm text-gray-500">Due {{ $invoice->due_date->format('M j, Y') }}</p>
                @endif
            </div>
            <div class="flex gap-2">
                {{-- Edit (draft only) --}}
                @if (!$invoice->is_locked && $invoice->status === 'draft')
                <a href="{{ route('invoices.edit', [$serviceRequest, $invoice]) }}"
                   class="inline-flex items-center px-3 py-2 bg-white/10 text-gray-300 text-sm font-semibold rounded-md hover:bg-gray-300 transition">
                    Edit
                </a>
                @endif

                {{-- Revise (sent only) --}}
                @if (!$invoice->is_locked && $invoice->status === 'sent')
                <form method="POST" action="{{ route('invoices.revise', [$serviceRequest, $invoice]) }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-3 py-2 bg-purple-600 text-white text-sm font-semibold rounded-md hover:bg-purple-700 transition">
                        Revise (V{{ $invoice->version + 1 }})
                    </button>
                </form>
                @endif

                {{-- Status update --}}
                @if ($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
                <form method="POST" action="{{ route('invoices.update-status', [$serviceRequest, $invoice]) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    @if ($invoice->status === 'draft')
                        <input type="hidden" name="status" value="sent">
                        <button type="submit" class="inline-flex items-center px-3 py-2 btn-crystal text-sm font-semibold rounded-md  transition">
                            Mark Sent
                        </button>
                    @elseif ($invoice->status === 'sent' || $invoice->status === 'overdue')
                        <input type="hidden" name="status" value="paid">
                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-green-600 text-white text-sm font-semibold rounded-md hover:bg-green-700 transition">
                            Mark Paid
                        </button>
                    @endif
                </form>
                @endif

                <a href="{{ route('invoices.pdf', [$serviceRequest, $invoice]) }}"
                   class="inline-flex items-center px-4 py-2 btn-crystal text-sm font-semibold rounded-md  transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download PDF
                </a>

                {{-- Issue Receipt (available when invoice is paid) --}}
                @if ($invoice->status === 'paid')
                    <a href="{{ route('receipts.create', [$serviceRequest, $invoice]) }}"
                       class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-md hover:bg-emerald-700 transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Issue Receipt
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Company info --}}
    <div class="surface-1 p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-300 mb-2">{{ $invoice->company_snapshot['name'] ?? '' }}</h2>
                @if ($invoice->company_snapshot['address'] ?? '')
                    <p class="text-sm text-gray-400 whitespace-pre-line">{{ $invoice->company_snapshot['address'] }}</p>
                @endif
                @if ($invoice->company_snapshot['phone'] ?? '')
                    <p class="text-sm text-gray-400">{{ $invoice->company_snapshot['phone'] }}</p>
                @endif
                @if ($invoice->company_snapshot['email'] ?? '')
                    <p class="text-sm text-gray-400">{{ $invoice->company_snapshot['email'] }}</p>
                @endif
            </div>
            <div class="text-sm space-y-1">
                <p><span class="text-gray-500">Bill To:</span> <span class="font-medium">{{ $invoice->customer_name }}</span></p>
                @if ($invoice->customer_phone)
                    <p><span class="text-gray-500">Phone:</span> <span class="font-medium font-mono">{{ $invoice->customer_phone }}</span></p>
                @endif
                @if ($invoice->vehicle_description)
                    <p><span class="text-gray-500">Vehicle:</span> <span class="font-medium">{{ $invoice->vehicle_description }}</span></p>
                @endif
                @if ($invoice->vehicle)
                    @if ($invoice->vehicle->license_plate)
                        <p><span class="text-gray-500">Plate:</span> <span class="font-medium font-mono">{{ $invoice->vehicle->license_plate }}</span></p>
                    @endif
                    @if ($invoice->vehicle->vin)
                        <p><span class="text-gray-500">VIN:</span> <span class="font-medium font-mono">{{ $invoice->vehicle->vin }}</span></p>
                    @endif
                @endif
                @if ($invoice->service_description)
                    <p><span class="text-gray-500">Service:</span> <span class="font-medium">{{ $invoice->service_description }}</span></p>
                @endif
                @if ($invoice->service_location)
                    <p><span class="text-gray-500">Location:</span> <span class="font-medium">{{ $invoice->service_location }}</span></p>
                @endif
            </div>
        </div>
    </div>

    {{-- Line Items --}}
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-3">Items</h2>
        <div class="overflow-x-auto">
            <table class="table-crystal min-w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-gray-500">
                        <th class="pb-2 pr-4">Item</th>
                        <th class="pb-2 pr-4">Description</th>
                        <th class="pb-2 pr-4 text-right">Qty</th>
                        <th class="pb-2 pr-4">Unit</th>
                        <th class="pb-2 pr-4 text-right">Unit Price</th>
                        <th class="pb-2 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->line_items as $item)
                    <tr class="border-b border-white/10">
                        <td class="py-2 pr-4 font-medium">{{ $item['name'] }}</td>
                        <td class="py-2 pr-4 text-gray-400">{{ $item['description'] ?? '' }}</td>
                        <td class="py-2 pr-4 text-right">{{ $item['quantity'] }}</td>
                        <td class="py-2 pr-4 text-gray-400">{{ $item['unit'] ?? '' }}</td>
                        <td class="py-2 pr-4 text-right">${{ number_format($item['unit_price'], 2) }}</td>
                        <td class="py-2 text-right font-medium">${{ number_format($item['quantity'] * $item['unit_price'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 border-t pt-4 space-y-1 max-w-xs ml-auto text-sm">
            <div class="flex justify-between">
                <span class="text-gray-400">Subtotal</span>
                <span class="font-medium">${{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            @if ($invoice->tax_rate > 0)
            <div class="flex justify-between">
                <span class="text-gray-400">Tax ({{ $invoice->tax_rate + 0 }}%)</span>
                <span class="font-medium">${{ number_format($invoice->tax_amount, 2) }}</span>
            </div>
            @endif
            <div class="flex justify-between text-base font-bold border-t pt-2">
                <span>Total Due</span>
                <span>${{ number_format($invoice->total, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Payment Terms --}}
    @if ($invoice->payment_terms)
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-2">Payment Terms</h2>
        <p class="text-sm text-gray-400">{{ $invoice->payment_terms }}</p>
    </div>
    @endif

    {{-- Notes --}}
    @if ($invoice->notes)
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-2">Notes</h2>
        <p class="text-sm text-gray-400 whitespace-pre-line">{{ $invoice->notes }}</p>
    </div>
    @endif

    {{-- Version History --}}
    @if($versions->count() > 1)
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-3">Version History</h2>
        <div class="space-y-2">
            @foreach($versions as $v)
                <div @class([
                    'flex items-center justify-between px-4 py-2.5 rounded-lg text-sm',
                    'bg-cyan-500/10 border border-cyan-500/30' => $v->id === $invoice->id,
                    'bg-white/5' => $v->id !== $invoice->id,
                ])>
                    <div class="flex items-center gap-3">
                        <span class="font-semibold text-gray-300">V{{ $v->version }}</span>
                        @php $sc = ['draft'=>'bg-white/5 text-gray-300','sent'=>'bg-blue-100 text-cyan-400','paid'=>'bg-green-100 text-green-700','overdue'=>'bg-red-100 text-red-700','cancelled'=>'bg-white/5 text-gray-500']; @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $sc[$v->status] ?? 'bg-white/5 text-gray-300' }}">{{ ucfirst($v->status) }}</span>
                        @if($v->is_locked)
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                        @endif
                        <span class="text-gray-400">{{ $v->created_at->format('M j, Y g:i A') }}</span>
                    </div>
                    @if($v->id !== $invoice->id)
                        <a href="{{ route('invoices.show', [$serviceRequest, $v]) }}" class="text-cyan-400 hover:text-cyan-300 text-xs font-medium">View</a>
                    @else
                        <span class="text-xs text-cyan-400 font-medium">Current</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Documents --}}
    @include('partials.document-list', [
        'documents' => $invoice->documents,
        'uploadUrl' => route('documents.store-generic', ['type' => 'invoice', 'id' => $invoice->id]),
    ])

    {{-- Back --}}
    <div class="flex gap-3">
        <a href="{{ route('service-requests.show', $serviceRequest) }}" class="text-sm text-gray-500 hover:text-cyan-400 underline">&larr; Back to Service Request</a>
    </div>
</div>
@endsection
