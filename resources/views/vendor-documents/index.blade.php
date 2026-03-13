@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">Vendor Documents</h1>
        <a href="{{ route('vendor-documents.create') }}"
           class="inline-flex items-center px-4 py-2 btn-crystal text-sm font-semibold rounded-md  transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            New Document
        </a>
    </div>

    {{-- Filters --}}
    <div class="surface-1 p-4">
        <form method="GET" action="{{ route('vendor-documents.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="type" class="block text-xs font-medium text-gray-500 mb-1">Type</label>
                <select name="type" id="type"
                        class="rounded-md border-white/10 text-sm shadow-xs input-crystal">
                    <option value="">All</option>
                    @foreach (\App\Models\VendorDocument::TYPES as $key => $label)
                        <option value="{{ $key }}" {{ ($currentType ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status" class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="status" id="status"
                        class="rounded-md border-white/10 text-sm shadow-xs input-crystal">
                    <option value="">All</option>
                    @foreach (\App\Models\VendorDocument::STATUSES as $key => $label)
                        <option value="{{ $key }}" {{ ($currentStatus ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="vendor_id" class="block text-xs font-medium text-gray-500 mb-1">Vendor</label>
                <select name="vendor_id" id="vendor_id"
                        class="rounded-md border-white/10 text-sm shadow-xs input-crystal">
                    <option value="">All</option>
                    @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->id }}" {{ ($currentVendor ?? '') == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="from" class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" name="from" id="from" value="{{ $currentFrom ?? '' }}"
                       class="rounded-md border-white/10 text-sm shadow-xs input-crystal">
            </div>

            <div>
                <label for="to" class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" name="to" id="to" value="{{ $currentTo ?? '' }}"
                       class="rounded-md border-white/10 text-sm shadow-xs input-crystal">
            </div>

            <div>
                <label for="search" class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" name="search" id="search" value="{{ $currentSearch ?? '' }}"
                       placeholder="Doc #, vendor…"
                       class="rounded-md border-white/10 text-sm shadow-xs input-crystal">
            </div>

            <button type="submit"
                    class="inline-flex items-center px-4 py-2 btn-crystal text-sm">
                Filter
            </button>

            @if ($currentType || $currentStatus || $currentVendor || $currentFrom || $currentTo || $currentSearch)
                <a href="{{ route('vendor-documents.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-300 underline">Clear</a>
            @endif
        </form>
    </div>

    {{-- Results --}}
    <div class="surface-1">
        @if ($documents->isEmpty())
            <div class="p-6 text-center">
                <p class="text-sm text-gray-500">No vendor documents found.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table-crystal min-w-full divide-y divide-white/5">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doc #</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-transparent divide-y divide-white/5">
                        @foreach ($documents as $doc)
                            <tr class="hover:bg-white/5">
                                <td class="px-4 py-3 text-sm text-gray-400 whitespace-nowrap">
                                    {{ $doc->document_date->format('M j, Y') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-white font-medium">
                                    {{ $doc->vendor->name }}
                                </td>
                                <td class="px-4 py-3 text-sm font-mono text-gray-400">
                                    {{ $doc->vendor_document_number ?: '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $doc->isReceipt() ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-cyan-400' }}">
                                        {{ $doc->typeLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-white font-medium text-right whitespace-nowrap">
                                    ${{ number_format($doc->total, 2) }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($doc->is_paid)
                                        <span class="text-green-400 font-medium">Yes</span>
                                    @else
                                        <span class="text-red-500 font-medium">No</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @include('vendor-documents._status-badge', ['status' => $doc->status])
                                </td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <a href="{{ route('vendor-documents.show', $doc) }}"
                                       class="text-cyan-400 hover:text-cyan-300 text-sm font-medium">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($documents->hasPages())
                <div class="px-4 py-3 border-t border-white/10">
                    {{ $documents->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
