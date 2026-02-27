@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Expenses</h1>
        <a href="{{ route('expenses.create') }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Record Expense
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow-xs p-4">
        <form method="GET" action="{{ route('expenses.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="category" class="block text-xs font-medium text-gray-500 mb-1">Category</label>
                <select name="category" id="category"
                        class="rounded-md border-gray-300 text-sm shadow-xs focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All</option>
                    @foreach ($categories as $key => $label)
                        <option value="{{ $key }}" {{ ($currentCategory ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="from" class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" name="from" id="from" value="{{ $currentFrom ?? '' }}"
                       class="rounded-md border-gray-300 text-sm shadow-xs focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="to" class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" name="to" id="to" value="{{ $currentTo ?? '' }}"
                       class="rounded-md border-gray-300 text-sm shadow-xs focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="search" class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" name="search" id="search" value="{{ $currentSearch ?? '' }}"
                       placeholder="Vendor, description, #..."
                       class="rounded-md border-gray-300 text-sm shadow-xs focus:border-blue-500 focus:ring-blue-500">
            </div>

            <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors">
                Filter
            </button>

            @if ($currentCategory || $currentSearch || $currentFrom || $currentTo)
                <a href="{{ route('expenses.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 underline">Clear</a>
            @endif
        </form>
    </div>

    {{-- Results --}}
    <div class="bg-white rounded-lg shadow-xs">
        @if ($expenses->isEmpty())
            <div class="p-6 text-center">
                <p class="text-sm text-gray-500">No expenses found.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Number</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($expenses as $expense)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">
                                    {{ $expense->date->format('M j, Y') }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <a href="{{ route('expenses.show', $expense) }}"
                                       class="font-mono text-blue-600 hover:text-blue-800 font-medium">{{ $expense->expense_number }}</a>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                                    {{ $expense->vendor }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                        {{ $expense->categoryLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 font-medium text-right whitespace-nowrap">
                                    ${{ number_format($expense->amount, 2) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ $expense->paymentMethodLabel() ?: '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <a href="{{ route('expenses.show', $expense) }}"
                                       class="text-blue-600 hover:text-blue-800 text-sm font-medium">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($expenses->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">
                    {{ $expenses->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
