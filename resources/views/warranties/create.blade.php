@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <a href="{{ route('service-requests.show', $serviceRequest) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-blue-600">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Ticket #{{ $serviceRequest->id }}
    </a>

    <h1 class="text-2xl font-bold text-gray-900">Add Warranty</h1>

    <form method="POST" action="{{ route('warranties.store', $serviceRequest) }}" class="space-y-6">
        @csrf

        {{-- Part info --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Part Information</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="part_name" class="block text-sm font-medium text-gray-700 mb-1">Part Name <span class="text-red-500">*</span></label>
                    <input type="text" name="part_name" id="part_name" value="{{ old('part_name') }}"
                           class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500" required>
                    @error('part_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="part_number" class="block text-sm font-medium text-gray-700 mb-1">Part Number</label>
                    <input type="text" name="part_number" id="part_number" value="{{ old('part_number') }}"
                           class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('part_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Vendor info --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Vendor Information</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="vendor_name" class="block text-sm font-medium text-gray-700 mb-1">Vendor Name</label>
                    <input type="text" name="vendor_name" id="vendor_name" value="{{ old('vendor_name') }}"
                           class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('vendor_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="vendor_phone" class="block text-sm font-medium text-gray-700 mb-1">Vendor Phone</label>
                    <input type="text" name="vendor_phone" id="vendor_phone" value="{{ old('vendor_phone') }}"
                           class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('vendor_phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="vendor_invoice_number" class="block text-sm font-medium text-gray-700 mb-1">Vendor Invoice #</label>
                    <input type="text" name="vendor_invoice_number" id="vendor_invoice_number" value="{{ old('vendor_invoice_number') }}"
                           class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('vendor_invoice_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Warranty period --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Warranty Period</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="install_date" class="block text-sm font-medium text-gray-700 mb-1">Install Date <span class="text-red-500">*</span></label>
                    <input type="date" name="install_date" id="install_date" value="{{ old('install_date', now()->format('Y-m-d')) }}"
                           class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500" required>
                    @error('install_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="warranty_months" class="block text-sm font-medium text-gray-700 mb-1">Warranty Duration (months) <span class="text-red-500">*</span></label>
                    <input type="number" name="warranty_months" id="warranty_months" value="{{ old('warranty_months', 12) }}"
                           min="1" max="600"
                           class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500" required>
                    @error('warranty_months') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea name="notes" id="notes" rows="3"
                      class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
            @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('service-requests.show', $serviceRequest) }}"
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors">
                Save Warranty
            </button>
        </div>
    </form>
</div>
@endsection
