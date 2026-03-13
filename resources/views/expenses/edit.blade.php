@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <a href="{{ route('expenses.show', $expense) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-cyan-400">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        {{ $expense->expense_number }}
    </a>

    <h1 class="text-2xl font-bold text-white">Edit Expense</h1>

    <form method="POST" action="{{ route('expenses.update', $expense) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Basic info --}}
        <div class="surface-1 p-6">
            <h2 class="text-lg font-semibold text-gray-300 mb-4">Expense Details</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-300 mb-1">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="date" id="date" value="{{ old('date', $expense->date->format('Y-m-d')) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal" required>
                    @error('date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-300 mb-1">Category <span class="text-red-500">*</span></label>
                    <select name="category" id="category"
                            class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal" required>
                        <option value="">Select category…</option>
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}" {{ old('category', $expense->category) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="vendor" class="block text-sm font-medium text-gray-300 mb-1">Vendor <span class="text-red-500">*</span></label>
                    <input type="text" name="vendor" id="vendor" value="{{ old('vendor', $expense->vendor) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal" required>
                    @error('vendor') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-300 mb-1">Description</label>
                    <input type="text" name="description" id="description" value="{{ old('description', $expense->description) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                    @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Payment info --}}
        <div class="surface-1 p-6">
            <h2 class="text-lg font-semibold text-gray-300 mb-4">Payment</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-300 mb-1">Amount <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 text-sm">$</span>
                        <input type="number" name="amount" id="amount" value="{{ old('amount', $expense->amount) }}"
                               step="0.01" min="0.01"
                               class="w-full pl-7 rounded-md border-white/10 shadow-xs text-sm input-crystal" required>
                    </div>
                    @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-300 mb-1">Payment Method</label>
                    <select name="payment_method" id="payment_method"
                            class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                        <option value="">—</option>
                        @foreach ($paymentMethods as $key => $label)
                            <option value="{{ $key }}" {{ old('payment_method', $expense->payment_method) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('payment_method') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="reference_number" class="block text-sm font-medium text-gray-300 mb-1">Reference / Check #</label>
                    <input type="text" name="reference_number" id="reference_number" value="{{ old('reference_number', $expense->reference_number) }}"
                           class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">
                    @error('reference_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Receipt upload --}}
        <div class="surface-1 p-6">
            <h2 class="text-lg font-semibold text-gray-300 mb-4">Receipt</h2>
            @if ($expense->receipt_path)
                <p class="text-sm text-gray-400 mb-2">
                    Current:
                    <a href="{{ route('expenses.receipt', $expense) }}" class="text-cyan-400 hover:text-cyan-300 underline">Download receipt</a>
                </p>
            @endif
            <div>
                <label for="receipt" class="block text-xs font-medium text-gray-500 mb-1">Upload new receipt to replace (max 10 MB)</label>
                <input type="file" name="receipt" id="receipt"
                       accept=".jpg,.jpeg,.png,.gif,.pdf"
                       class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-cyan-500/10 file:text-cyan-400 hover:file:bg-cyan-500/20">
                @error('receipt') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Notes --}}
        <div class="surface-1 p-6">
            <label for="notes" class="block text-sm font-medium text-gray-300 mb-1">Notes</label>
            <textarea name="notes" id="notes" rows="3"
                      class="w-full rounded-md border-white/10 shadow-xs text-sm input-crystal">{{ old('notes', $expense->notes) }}</textarea>
            @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('expenses.show', $expense) }}"
               class="px-4 py-2 text-sm font-medium text-gray-300 bg-white/5 border border-white/10 rounded-md hover:bg-white/5 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-md  transition-colors">
                Update Expense
            </button>
        </div>
    </form>
</div>
@endsection
