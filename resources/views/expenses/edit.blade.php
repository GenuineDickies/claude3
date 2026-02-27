@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <a href="{{ route('expenses.show', $expense) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-blue-600">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        {{ $expense->expense_number }}
    </a>

    <h1 class="text-2xl font-bold text-gray-900">Edit Expense</h1>

    <form method="POST" action="{{ route('expenses.update', $expense) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Basic info --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Expense Details</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="date" id="date" value="{{ old('date', $expense->date->format('Y-m-d')) }}"
                           class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500" required>
                    @error('date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                    <select name="category" id="category"
                            class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500" required>
                        <option value="">Select category…</option>
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}" {{ old('category', $expense->category) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="vendor" class="block text-sm font-medium text-gray-700 mb-1">Vendor <span class="text-red-500">*</span></label>
                    <input type="text" name="vendor" id="vendor" value="{{ old('vendor', $expense->vendor) }}"
                           class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500" required>
                    @error('vendor') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <input type="text" name="description" id="description" value="{{ old('description', $expense->description) }}"
                           class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Payment info --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Payment</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 text-sm">$</span>
                        <input type="number" name="amount" id="amount" value="{{ old('amount', $expense->amount) }}"
                               step="0.01" min="0.01"
                               class="w-full pl-7 rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500" required>
                    </div>
                    @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                    <select name="payment_method" id="payment_method"
                            class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">—</option>
                        @foreach ($paymentMethods as $key => $label)
                            <option value="{{ $key }}" {{ old('payment_method', $expense->payment_method) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('payment_method') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="reference_number" class="block text-sm font-medium text-gray-700 mb-1">Reference / Check #</label>
                    <input type="text" name="reference_number" id="reference_number" value="{{ old('reference_number', $expense->reference_number) }}"
                           class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('reference_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Receipt upload --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Receipt</h2>
            @if ($expense->receipt_path)
                <p class="text-sm text-gray-600 mb-2">
                    Current:
                    <a href="{{ route('expenses.receipt', $expense) }}" class="text-blue-600 hover:text-blue-800 underline">Download receipt</a>
                </p>
            @endif
            <div>
                <label for="receipt" class="block text-xs font-medium text-gray-500 mb-1">Upload new receipt to replace (max 10 MB)</label>
                <input type="file" name="receipt" id="receipt"
                       accept=".jpg,.jpeg,.png,.gif,.pdf"
                       class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @error('receipt') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Notes --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea name="notes" id="notes" rows="3"
                      class="w-full rounded-md border-gray-300 shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $expense->notes) }}</textarea>
            @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('expenses.show', $expense) }}"
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors">
                Update Expense
            </button>
        </div>
    </form>
</div>
@endsection
