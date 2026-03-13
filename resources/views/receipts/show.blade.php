@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <a href="{{ route('service-requests.show', $serviceRequest) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-cyan-400">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Ticket #{{ $serviceRequest->id }}
    </a>

    {{-- Header --}}
    <div class="surface-1 p-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-white">Receipt {{ $receipt->receipt_number }}</h1>
                <p class="text-sm text-gray-500 mt-1">Issued {{ $receipt->created_at->format('M j, Y g:i A') }}</p>
            </div>
            <a href="{{ route('receipts.pdf', [$serviceRequest, $receipt]) }}"
               class="inline-flex items-center px-4 py-2 btn-crystal text-sm font-semibold rounded-md  transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Download PDF
            </a>
        </div>
    </div>

    {{-- Company info --}}
    <div class="surface-1 p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-300 mb-2">{{ $receipt->company_snapshot['name'] ?? '' }}</h2>
                @if ($receipt->company_snapshot['address'] ?? '')
                    <p class="text-sm text-gray-400 whitespace-pre-line">{{ $receipt->company_snapshot['address'] }}</p>
                @endif
                @if ($receipt->company_snapshot['phone'] ?? '')
                    <p class="text-sm text-gray-400">{{ $receipt->company_snapshot['phone'] }}</p>
                @endif
                @if ($receipt->company_snapshot['email'] ?? '')
                    <p class="text-sm text-gray-400">{{ $receipt->company_snapshot['email'] }}</p>
                @endif
            </div>
            <div class="text-sm space-y-1">
                <p><span class="text-gray-500">Customer:</span> <span class="font-medium">{{ $receipt->customer_name }}</span></p>
                @if ($receipt->customer_phone)
                    <p><span class="text-gray-500">Phone:</span> <span class="font-medium font-mono">{{ $receipt->customer_phone }}</span></p>
                @endif
                @if ($receipt->vehicle_description)
                    <p><span class="text-gray-500">Vehicle:</span> <span class="font-medium">{{ $receipt->vehicle_description }}</span></p>
                @endif
                @if ($receipt->service_description)
                    <p><span class="text-gray-500">Service:</span> <span class="font-medium">{{ $receipt->service_description }}</span></p>
                @endif
                @if ($receipt->service_location)
                    <p><span class="text-gray-500">Location:</span> <span class="font-medium">{{ $receipt->service_location }}</span></p>
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
                    @foreach ($receipt->line_items as $item)
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
                <span class="font-medium">${{ number_format($receipt->subtotal, 2) }}</span>
            </div>
            @if ($receipt->tax_rate > 0)
            <div class="flex justify-between">
                <span class="text-gray-400">Tax ({{ $receipt->tax_rate + 0 }}%)</span>
                <span class="font-medium">${{ number_format($receipt->tax_amount, 2) }}</span>
            </div>
            @endif
            <div class="flex justify-between text-base font-bold border-t pt-2">
                <span>Total</span>
                <span>${{ number_format($receipt->total, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Payment --}}
    @if ($receipt->payment_method)
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-3">Payment</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div>
                <span class="block text-gray-500">Method</span>
                <span class="font-medium">{{ ucfirst($receipt->payment_method) }}</span>
            </div>
            @if ($receipt->payment_reference)
            <div>
                <span class="block text-gray-500">Reference</span>
                <span class="font-medium font-mono">{{ $receipt->payment_reference }}</span>
            </div>
            @endif
            @if ($receipt->payment_date)
            <div>
                <span class="block text-gray-500">Date</span>
                <span class="font-medium">{{ $receipt->payment_date->format('M j, Y') }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Notes --}}
    @if ($receipt->notes)
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-2">Notes</h2>
        <p class="text-sm text-gray-400 whitespace-pre-line">{{ $receipt->notes }}</p>
    </div>
    @endif

    {{-- Back --}}
    <div class="flex gap-3">
        <a href="{{ route('service-requests.show', $serviceRequest) }}" class="text-sm text-gray-500 hover:text-cyan-400 underline">&larr; Back to Ticket</a>
    </div>
</div>
@endsection
