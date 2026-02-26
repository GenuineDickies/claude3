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
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Documents</h2>
        </div>

        {{-- Upload form --}}
        <form method="POST" action="{{ route('documents.store', $warranty) }}" enctype="multipart/form-data"
              class="flex flex-wrap items-end gap-3 mb-4 pb-4 border-b border-gray-200">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <label for="file" class="block text-xs font-medium text-gray-500 mb-1">File</label>
                <input type="file" name="file" id="file" required
                       accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx"
                       class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @error('file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="category" class="block text-xs font-medium text-gray-500 mb-1">Category</label>
                <select name="category" id="category"
                        class="rounded-md border-gray-300 text-sm shadow-xs focus:border-blue-500 focus:ring-blue-500">
                    @foreach (\App\Models\Document::CATEGORY_LABELS as $key => $label)
                        <option value="{{ $key }}" {{ $key === 'warranty_doc' ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors">
                Upload
            </button>
        </form>

        {{-- Document list --}}
        @if ($warranty->documents->isEmpty())
            <p class="text-sm text-gray-500">No documents attached.</p>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach ($warranty->documents as $doc)
                <li class="flex items-center justify-between py-2">
                    <div class="flex items-center gap-3 min-w-0">
                        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                        <div class="min-w-0">
                            <a href="{{ route('documents.show', $doc) }}" target="_blank"
                               class="text-sm font-medium text-blue-600 hover:text-blue-800 truncate block">{{ $doc->original_filename }}</a>
                            <p class="text-xs text-gray-400">
                                {{ \App\Models\Document::CATEGORY_LABELS[$doc->category] ?? $doc->category }}
                                &middot; {{ number_format($doc->file_size / 1024, 0) }} KB
                                @if ($doc->uploader) &middot; {{ $doc->uploader->name }} @endif
                            </p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('documents.destroy', $doc) }}"
                          onsubmit="return confirm('Delete this document?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-400 hover:text-red-600 p-1" title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </form>
                </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
