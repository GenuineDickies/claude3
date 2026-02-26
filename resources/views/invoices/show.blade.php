@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <a href="{{ route('service-requests.show', $serviceRequest) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-blue-600">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Ticket #{{ $serviceRequest->id }}
    </a>

    {{-- Header --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-start">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-800">Invoice {{ $invoice->invoice_number }}</h1>
                    @php
                        $statusColors = [
                            'draft'     => 'bg-gray-100 text-gray-700',
                            'sent'      => 'bg-blue-100 text-blue-700',
                            'paid'      => 'bg-green-100 text-green-700',
                            'overdue'   => 'bg-red-100 text-red-700',
                            'cancelled' => 'bg-gray-100 text-gray-500',
                        ];
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusColors[$invoice->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ ucfirst($invoice->status) }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">Created {{ $invoice->created_at->format('M j, Y g:i A') }}</p>
                @if ($invoice->due_date)
                    <p class="text-sm text-gray-500">Due {{ $invoice->due_date->format('M j, Y') }}</p>
                @endif
            </div>
            <div class="flex gap-2">
                {{-- Status update --}}
                @if ($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
                <form method="POST" action="{{ route('invoices.update-status', [$serviceRequest, $invoice]) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    @if ($invoice->status === 'draft')
                        <input type="hidden" name="status" value="sent">
                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 transition">
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
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download PDF
                </a>
            </div>
        </div>
    </div>

    {{-- Company info --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-700 mb-2">{{ $invoice->company_snapshot['name'] ?? '' }}</h2>
                @if ($invoice->company_snapshot['address'] ?? '')
                    <p class="text-sm text-gray-600 whitespace-pre-line">{{ $invoice->company_snapshot['address'] }}</p>
                @endif
                @if ($invoice->company_snapshot['phone'] ?? '')
                    <p class="text-sm text-gray-600">{{ $invoice->company_snapshot['phone'] }}</p>
                @endif
                @if ($invoice->company_snapshot['email'] ?? '')
                    <p class="text-sm text-gray-600">{{ $invoice->company_snapshot['email'] }}</p>
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
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-3">Items</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
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
                    <tr class="border-b border-gray-100">
                        <td class="py-2 pr-4 font-medium">{{ $item['name'] }}</td>
                        <td class="py-2 pr-4 text-gray-600">{{ $item['description'] ?? '' }}</td>
                        <td class="py-2 pr-4 text-right">{{ $item['quantity'] }}</td>
                        <td class="py-2 pr-4 text-gray-600">{{ $item['unit'] ?? '' }}</td>
                        <td class="py-2 pr-4 text-right">${{ number_format($item['unit_price'], 2) }}</td>
                        <td class="py-2 text-right font-medium">${{ number_format($item['quantity'] * $item['unit_price'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 border-t pt-4 space-y-1 max-w-xs ml-auto text-sm">
            <div class="flex justify-between">
                <span class="text-gray-600">Subtotal</span>
                <span class="font-medium">${{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            @if ($invoice->tax_rate > 0)
            <div class="flex justify-between">
                <span class="text-gray-600">Tax ({{ $invoice->tax_rate + 0 }}%)</span>
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
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-2">Payment Terms</h2>
        <p class="text-sm text-gray-600">{{ $invoice->payment_terms }}</p>
    </div>
    @endif

    {{-- Notes --}}
    @if ($invoice->notes)
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-2">Notes</h2>
        <p class="text-sm text-gray-600 whitespace-pre-line">{{ $invoice->notes }}</p>
    </div>
    @endif

    {{-- Back --}}
    <div class="flex gap-3">
        <a href="{{ route('service-requests.show', $serviceRequest) }}" class="text-sm text-gray-500 hover:text-blue-600 underline">&larr; Back to Ticket</a>
    </div>
</div>
@endsection
