@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('service-requests.index') }}" class="hover:text-blue-600">All Tickets</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('service-requests.show', $serviceRequest) }}" class="hover:text-blue-600">SR #{{ $serviceRequest->id }}</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-700 font-medium">{{ $estimate->displayNumber() }}</span>
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
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $estimate->displayNumber() }}</h1>
                    @if($estimate->version > 1)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-700">
                            V{{ $estimate->version }}
                        </span>
                    @endif
                    @if($estimate->is_locked)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-200 text-gray-600">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                            Locked
                        </span>
                    @endif
                </div>
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
                'bg-amber-100 text-amber-700' => $estimate->status === 'pending_approval',
                'bg-green-100 text-green-700' => $estimate->status === 'accepted',
                'bg-red-100 text-red-700' => $estimate->status === 'declined',
            ])>
                {{ \App\Models\Estimate::statuses()[$estimate->status] ?? ucfirst($estimate->status) }}
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
                @if($estimate->approved_total !== null)
                <div class="flex justify-between w-56 mt-1">
                    <span class="text-xs font-semibold text-green-700">Approved Total</span>
                    <span class="text-sm font-bold font-mono text-green-700">${{ number_format($estimate->approved_total, 2) }}</span>
                </div>
                @endif
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

    {{-- Approval Info --}}
    @if($estimate->approved_at)
        <div class="bg-green-50 rounded-lg shadow-sm p-6 border border-green-200">
            <h2 class="text-lg font-semibold text-green-800 mb-3">Customer Approval</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="block text-green-600">Signed By</span>
                    <span class="font-medium text-gray-800">{{ $estimate->signer_name }}</span>
                </div>
                <div>
                    <span class="block text-green-600">Approved At</span>
                    <span class="font-medium text-gray-800">{{ $estimate->approved_at->format('M j, Y g:i A') }}</span>
                </div>
                <div>
                    <span class="block text-green-600">Approved Total</span>
                    <span class="font-medium text-gray-800">${{ number_format($estimate->approved_total, 2) }}</span>
                </div>
            </div>
            @if($estimate->signature_data)
                <div class="mt-4">
                    <span class="block text-green-600 text-sm mb-1">Signature</span>
                    <img src="{{ $estimate->signature_data }}" alt="Customer signature" class="max-h-24 border border-green-200 rounded bg-white p-1">
                </div>
            @endif
        </div>
    @elseif($estimate->status === 'pending_approval')
        <div class="bg-amber-50 rounded-lg shadow-sm p-6 border border-amber-200">
            <h2 class="text-lg font-semibold text-amber-800 mb-2">Awaiting Customer Approval</h2>
            <p class="text-sm text-amber-700">An approval link has been sent to the customer. The link expires {{ $estimate->approval_token_expires_at?->diffForHumans() ?? 'in 7 days' }}.</p>
        </div>
    @elseif($estimate->requiresApproval() && $estimate->status === 'sent')
        <div class="bg-yellow-50 rounded-lg shadow-sm p-6 border border-yellow-200">
            <h2 class="text-lg font-semibold text-yellow-800 mb-2">Approval Required</h2>
            <p class="text-sm text-yellow-700">This estimate exceeds the signature threshold and requires customer approval before a work order can be created.</p>
        </div>
    @endif

    {{-- Actions --}}
    <div class="flex justify-between items-center">
        <a href="{{ route('service-requests.show', $serviceRequest) }}"
           class="text-sm text-gray-500 hover:text-blue-600 underline">&larr; Back to SR #{{ $serviceRequest->id }}</a>

        <div class="flex gap-3 flex-wrap">
            @if(in_array($estimate->status, ['sent', 'pending_approval']) && $estimate->requiresApproval() && !$estimate->isApproved())
                <form action="{{ route('estimates.request-approval', [$serviceRequest, $estimate]) }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-amber-600 text-white text-sm font-medium px-5 py-2.5 rounded-md hover:bg-amber-700 shadow-xs transition"
                            onclick="return confirm('Send approval request to the customer via SMS?')">
                        {{ $estimate->status === 'pending_approval' ? 'Resend Approval Request' : 'Request Customer Approval' }}
                    </button>
                </form>
            @endif
            @if($estimate->status === 'accepted')
                <a href="{{ route('work-orders.create', $serviceRequest) }}"
                   class="bg-green-600 text-white text-sm font-medium px-5 py-2.5 rounded-md hover:bg-green-700 shadow-xs transition">
                    Create Work Order
                </a>
            @endif
            @if($estimate->status === 'sent' && !$estimate->is_locked)
                <form action="{{ route('estimates.revise', [$serviceRequest, $estimate]) }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-purple-600 text-white text-sm font-medium px-5 py-2.5 rounded-md hover:bg-purple-700 shadow-xs transition">
                        Revise (Create V{{ $estimate->version + 1 }})
                    </button>
                </form>
            @endif
            @if(!$estimate->is_locked && !in_array($estimate->status, ['accepted', 'declined']))
                <a href="{{ route('estimates.edit', [$serviceRequest, $estimate]) }}"
                   class="bg-blue-600 text-white text-sm font-medium px-5 py-2.5 rounded-md hover:bg-blue-700 shadow-xs transition">
                    Edit Estimate
                </a>
            @endif
            @if(!$estimate->is_locked)
                <form action="{{ route('estimates.destroy', [$serviceRequest, $estimate]) }}" method="POST"
                      onsubmit="return confirm('Delete this estimate?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="bg-white text-red-600 text-sm font-medium px-5 py-2.5 rounded-md border border-red-200 hover:bg-red-50 transition">
                        Delete
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Version History --}}
    @if($versions->count() > 1)
    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Version History</h2>
        <div class="space-y-2">
            @foreach($versions as $v)
                <div @class([
                    'flex items-center justify-between px-4 py-2.5 rounded-lg text-sm',
                    'bg-blue-50 border border-blue-200' => $v->id === $estimate->id,
                    'bg-gray-50' => $v->id !== $estimate->id,
                ])>
                    <div class="flex items-center gap-3">
                        <span class="font-semibold text-gray-700">V{{ $v->version }}</span>
                        <span @class([
                            'px-2 py-0.5 rounded-full text-xs font-semibold',
                            'bg-gray-100 text-gray-600' => $v->status === 'draft',
                            'bg-blue-100 text-blue-700' => $v->status === 'sent',
                            'bg-amber-100 text-amber-700' => $v->status === 'pending_approval',
                            'bg-green-100 text-green-700' => $v->status === 'accepted',
                            'bg-red-100 text-red-700' => $v->status === 'declined',
                        ])>{{ \App\Models\Estimate::statuses()[$v->status] ?? ucfirst($v->status) }}</span>
                        @if($v->is_locked)
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                        @endif
                        <span class="text-gray-400">{{ $v->created_at->format('M j, Y g:i A') }}</span>
                    </div>
                    @if($v->id !== $estimate->id)
                        <a href="{{ route('estimates.show', [$serviceRequest, $v]) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">View</a>
                    @else
                        <span class="text-xs text-blue-600 font-medium">Current</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Documents --}}
    @include('partials.document-list', [
        'documents' => $estimate->documents,
        'uploadUrl' => route('documents.store-generic', ['type' => 'estimate', 'id' => $estimate->id]),
    ])
</div>
@endsection
