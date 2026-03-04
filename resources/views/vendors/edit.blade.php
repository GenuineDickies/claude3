@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <a href="{{ route('vendors.show', $vendor) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-blue-600">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        {{ $vendor->name }}
    </a>

    <h1 class="text-2xl font-bold text-gray-900">Edit Vendor</h1>

    <form method="POST" action="{{ route('vendors.update', $vendor) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('vendors._form', ['vendor' => $vendor, 'expenseAccounts' => $expenseAccounts])

        {{-- Active toggle --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <label class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       {{ old('is_active', $vendor->is_active) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="text-sm font-medium text-gray-700">Active</span>
            </label>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('vendors.show', $vendor) }}"
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors">
                Update Vendor
            </button>
        </div>
    </form>
</div>
@endsection
