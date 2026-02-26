@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('service-requests.index') }}" class="hover:text-blue-600">All Tickets</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('service-requests.show', $serviceRequest) }}" class="hover:text-blue-600">SR #{{ $serviceRequest->id }}</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-700 font-medium">Estimate #{{ $estimate->id }}</span>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="bg-linear-to-r from-blue-50 to-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Estimate #{{ $estimate->id }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    For Service Request #{{ $serviceRequest->id }}
                    @if($serviceRequest->customer)
                        — {{ $serviceRequest->customer->first_name }} {{ $serviceRequest->customer->last_name }}
                    @endif
                </p>
            </div>
            <span @class([
                'px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide',
                'bg-gray-100 text-gray-600' => $estimate->status === 'draft',
                'bg-blue-100 text-blue-700' => $estimate->status === 'sent',
                'bg-green-100 text-green-700' => $estimate->status === 'accepted',
                'bg-red-100 text-red-700' => $estimate->status === 'declined',
            ])>
                {{ ucfirst($estimate->status) }}
            </span>
        </div>
    </div>

    {{-- Details --}}
    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Details</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div>
                <span class="block text-gray-500">Customer</span>
                @if($serviceRequest->customer)
                    <span class="font-medium">{{ $serviceRequest->customer->first_name }} {{ $serviceRequest->customer->last_name }}</span>
                    <span class="block text-gray-400 text-xs">{{ $serviceRequest->customer->phone }}</span>
                @else
                    <span class="text-gray-400 italic">No customer assigned</span>
                @endif
            </div>
            <div>
                <span class="block text-gray-500">State</span>
                @if($estimate->state_code)
                    <span class="font-medium">{{ \App\Models\StateTaxRate::stateList()[$estimate->state_code] ?? $estimate->state_code }} ({{ $estimate->state_code }})</span>
                @else
                    <span class="text-gray-400 italic">Not specified</span>
                @endif
            </div>
            <div>
                <span class="block text-gray-500">Tax Rate</span>
                <span class="font-medium">{{ $estimate->tax_rate + 0 }}%</span>
            </div>
        </div>
    </div>

    {{-- Line Items --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-800">Estimate Items</h2>
        </div>

        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 border-b border-gray-300">
                <tr>
                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-600 uppercase tracking-wider">Item</th>
                    <th class="text-right px-3 py-2.5 text-xs font-semibold text-gray-600 uppercase tracking-wider w-28">Price</th>
                    <th class="text-center px-3 py-2.5 text-xs font-semibold text-gray-600 uppercase tracking-wider w-20">Qty</th>
                    <th class="text-center px-3 py-2.5 text-xs font-semibold text-gray-600 uppercase tracking-wider w-24">Unit</th>
                    <th class="text-right px-3 py-2.5 text-xs font-semibold text-gray-600 uppercase tracking-wider w-24">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($estimate->items as $item)
                    <tr class="even:bg-gray-50/60">
                        <td class="px-4 py-2.5">
                            <span class="font-medium text-gray-800">{{ $item->name }}</span>
                            @if($item->description)
                                <p class="text-xs text-gray-400 mt-0.5">{{ $item->description }}</p>
                            @endif
                        </td>
                        <td class="text-right px-3 py-2.5 font-mono text-gray-700">${{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-center px-3 py-2.5 text-gray-700">{{ $item->quantity + 0 }}</td>
                        <td class="text-center px-3 py-2.5 text-gray-500">{{ ucfirst($item->unit) }}</td>
                        <td class="text-right px-3 py-2.5 font-mono font-semibold text-gray-800">${{ number_format($item->lineTotal(), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        {{-- Totals --}}
        <div class="border-t-2 border-gray-200 bg-blue-50/60 px-6 py-4">
            <div class="flex flex-col items-end space-y-1.5 text-sm">
                <div class="flex justify-between w-56">
                    <span class="text-gray-500">Subtotal</span>
                    <span class="font-mono font-medium text-gray-700">${{ number_format($estimate->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between w-56">
                    <span class="text-gray-500">
                        Tax
                        @if($estimate->state_code)
                            <span class="text-gray-400">({{ $estimate->state_code }} {{ $estimate->tax_rate + 0 }}%)</span>
                        @elseif($estimate->tax_rate > 0)
                            <span class="text-gray-400">({{ $estimate->tax_rate + 0 }}%)</span>
                        @endif
                    </span>
                    <span class="font-mono font-medium text-gray-700">${{ number_format($estimate->tax_amount, 2) }}</span>
                </div>
                <div class="flex justify-between w-56 border-t-2 border-blue-200 pt-2 mt-1">
                    <span class="font-bold text-gray-900">Total</span>
                    <span class="text-lg font-bold font-mono text-blue-700">${{ number_format($estimate->total, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Notes --}}
    @if($estimate->notes)
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800 mb-2">Notes</h2>
            <p class="text-sm text-gray-600 leading-relaxed">{{ $estimate->notes }}</p>
        </div>
    @endif

    {{-- Actions --}}
    <div class="flex justify-between items-center">
        <a href="{{ route('service-requests.show', $serviceRequest) }}"
           class="text-sm text-gray-500 hover:text-blue-600 underline">&larr; Back to SR #{{ $serviceRequest->id }}</a>

        <div class="flex gap-3">
            <a href="{{ route('estimates.edit', [$serviceRequest, $estimate]) }}"
               class="bg-blue-600 text-white text-sm font-medium px-5 py-2.5 rounded-md hover:bg-blue-700 shadow-xs transition">
                Edit Estimate
            </a>
            <form action="{{ route('estimates.destroy', [$serviceRequest, $estimate]) }}" method="POST"
                  onsubmit="return confirm('Delete this estimate?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="bg-white text-red-600 text-sm font-medium px-5 py-2.5 rounded-md border border-red-200 hover:bg-red-50 transition">
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
