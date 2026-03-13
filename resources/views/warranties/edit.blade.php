@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <a href="{{ route('warranties.show', [$serviceRequest, $warranty]) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-cyan-400">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        {{ $warranty->part_name }}
    </a>

    <h1 class="text-2xl font-bold text-white">Edit Warranty</h1>

    <form method="POST" action="{{ route('warranties.update', [$serviceRequest, $warranty]) }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Part info --}}
        <div class="surface-1 p-6">
            <h2 class="text-lg font-semibold text-gray-300 mb-4">Part Information</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="part_name" class="block text-sm font-medium text-gray-300 mb-1">Part Name <span class="text-red-500">*</span></label>
                    <input type="text" name="part_name" id="part_name" value="{{ old('part_name', $warranty->part_name) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal" required>
                    @error('part_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="part_number" class="block text-sm font-medium text-gray-300 mb-1">Part Number</label>
                    <input type="text" name="part_number" id="part_number" value="{{ old('part_number', $warranty->part_number) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                    @error('part_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Vendor info --}}
        <div class="surface-1 p-6">
            <h2 class="text-lg font-semibold text-gray-300 mb-4">Vendor Information</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="vendor_name" class="block text-sm font-medium text-gray-300 mb-1">Vendor Name</label>
                    <input type="text" name="vendor_name" id="vendor_name" value="{{ old('vendor_name', $warranty->vendor_name) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                    @error('vendor_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="vendor_phone" class="block text-sm font-medium text-gray-300 mb-1">Vendor Phone</label>
                    <input type="text" name="vendor_phone" id="vendor_phone" value="{{ old('vendor_phone', $warranty->vendor_phone) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                    @error('vendor_phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="vendor_invoice_number" class="block text-sm font-medium text-gray-300 mb-1">Vendor Invoice #</label>
                    <input type="text" name="vendor_invoice_number" id="vendor_invoice_number" value="{{ old('vendor_invoice_number', $warranty->vendor_invoice_number) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                    @error('vendor_invoice_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Warranty period --}}
        <div class="surface-1 p-6">
            <h2 class="text-lg font-semibold text-gray-300 mb-4">Warranty Period</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="install_date" class="block text-sm font-medium text-gray-300 mb-1">Install Date <span class="text-red-500">*</span></label>
                    <input type="date" name="install_date" id="install_date" value="{{ old('install_date', $warranty->install_date->format('Y-m-d')) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal" required>
                    @error('install_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="warranty_months" class="block text-sm font-medium text-gray-300 mb-1">Warranty Duration (months) <span class="text-red-500">*</span></label>
                    <input type="number" name="warranty_months" id="warranty_months" value="{{ old('warranty_months', $warranty->warranty_months) }}"
                           min="1" max="600"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal" required>
                    @error('warranty_months') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="surface-1 p-6">
            <label for="notes" class="block text-sm font-medium text-gray-300 mb-1">Notes</label>
            <textarea name="notes" id="notes" rows="3"
                      class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">{{ old('notes', $warranty->notes) }}</textarea>
            @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('warranties.show', [$serviceRequest, $warranty]) }}"
               class="px-4 py-2 text-sm font-medium text-gray-300 bg-white/5 border border-white/10 rounded-md hover:bg-white/5 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-md  transition-colors">
                Update Warranty
            </button>
        </div>
    </form>
</div>
@endsection
