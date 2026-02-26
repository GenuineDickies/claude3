@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Warranties</h1>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow-xs p-4">
        <form method="GET" action="{{ route('warranties.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="filter" class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="filter" id="filter"
                        class="rounded-md border-gray-300 text-sm shadow-xs focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All</option>
                    @foreach (\App\Models\Warranty::EXPIRY_LABELS as $key => $label)
                        <option value="{{ $key }}" {{ ($filter ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors">
                Filter
            </button>

            @if ($filter ?? null)
                <a href="{{ route('warranties.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 underline">Clear</a>
            @endif
        </form>
    </div>

    {{-- Results --}}
    <div class="bg-white rounded-lg shadow-xs">
        @if ($warranties->isEmpty())
            <div class="p-6 text-center">
                <p class="text-sm text-gray-500">No warranties found.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Part</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Install Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($warranties as $warranty)
                            @php $expiry = $warranty->expiryStatus(); @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                    {{ $warranty->part_name }}
                                    @if ($warranty->part_number)
                                        <span class="text-gray-400 text-xs font-mono ml-1">#{{ $warranty->part_number }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <a href="{{ route('service-requests.show', $warranty->service_request_id) }}"
                                       class="font-mono text-blue-600 hover:text-blue-800 font-medium">#{{ $warranty->service_request_id }}</a>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ $warranty->serviceRequest?->customer?->first_name }} {{ $warranty->serviceRequest?->customer?->last_name }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ $warranty->vendor_name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">
                                    {{ $warranty->install_date->format('M j, Y') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">
                                    {{ $warranty->warranty_expires_at->format('M j, Y') }}
                                </td>
                                <td class="px-4 py-3 text-sm">
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
                                </td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <a href="{{ route('warranties.show', [$warranty->service_request_id, $warranty]) }}"
                                       class="text-blue-600 hover:text-blue-800 text-sm font-medium">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($warranties->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">
                    {{ $warranties->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
