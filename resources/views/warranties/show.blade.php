@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <a href="{{ route('service-requests.show', $serviceRequest) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-blue-600">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Ticket #{{ $serviceRequest->id }}
    </a>

    @php $expiry = $warranty->expiryStatus(); @endphp

    {{-- Header --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ $warranty->part_name }}</h1>
                @if ($warranty->part_number)
                    <p class="text-sm text-gray-500 font-mono mt-1">#{{ $warranty->part_number }}</p>
                @endif
                <div class="mt-2">
                    @php
                        $badgeColors = match($expiry) {
                            'active'        => 'bg-green-100 text-green-800',
                            'expiring_soon' => 'bg-yellow-100 text-yellow-800',
                            'expired'       => 'bg-red-100 text-red-800',
                        };
                    @endphp
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $badgeColors }}">
                        {{ \App\Models\Warranty::EXPIRY_LABELS[$expiry] }}
                    </span>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('warranties.edit', [$serviceRequest, $warranty]) }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                    Edit
                </a>
                <form method="POST" action="{{ route('warranties.destroy', [$serviceRequest, $warranty]) }}"
                      onsubmit="return confirm('Delete this warranty?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-white border border-red-300 text-red-600 text-sm font-medium rounded-md hover:bg-red-50 transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Warranty details --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Warranty Details</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <div>
                <dt class="text-gray-500">Install Date</dt>
                <dd class="font-medium text-gray-900 mt-0.5">{{ $warranty->install_date->format('M j, Y') }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Duration</dt>
                <dd class="font-medium text-gray-900 mt-0.5">{{ $warranty->warranty_months }} month{{ $warranty->warranty_months !== 1 ? 's' : '' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Expires</dt>
                <dd class="font-medium text-gray-900 mt-0.5">{{ $warranty->warranty_expires_at->format('M j, Y') }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Customer</dt>
                <dd class="font-medium text-gray-900 mt-0.5">
                    {{ $warranty->serviceRequest?->customer?->first_name }} {{ $warranty->serviceRequest?->customer?->last_name }}
                </dd>
            </div>
        </dl>
    </div>

    {{-- Vendor info --}}
    @if ($warranty->vendor_name || $warranty->vendor_phone || $warranty->vendor_invoice_number)
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Vendor</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            @if ($warranty->vendor_name)
            <div>
                <dt class="text-gray-500">Name</dt>
                <dd class="font-medium text-gray-900 mt-0.5">{{ $warranty->vendor_name }}</dd>
            </div>
            @endif
            @if ($warranty->vendor_phone)
            <div>
                <dt class="text-gray-500">Phone</dt>
                <dd class="font-medium text-gray-900 mt-0.5 font-mono">{{ $warranty->vendor_phone }}</dd>
            </div>
            @endif
            @if ($warranty->vendor_invoice_number)
            <div>
                <dt class="text-gray-500">Invoice #</dt>
                <dd class="font-medium text-gray-900 mt-0.5 font-mono">{{ $warranty->vendor_invoice_number }}</dd>
            </div>
            @endif
        </dl>
    </div>
    @endif

    {{-- Notes --}}
    @if ($warranty->notes)
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-2">Notes</h2>
        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $warranty->notes }}</p>
    </div>
    @endif

    {{-- Documents --}}
    @include('partials.document-list', [
        'documents' => $warranty->documents,
        'uploadUrl' => route('documents.store', $warranty),
        'defaultCategory' => 'warranty_doc',
    ])
</div>
@endsection
