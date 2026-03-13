@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <a href="{{ route('expenses.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-cyan-400">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        All Expenses
    </a>

    {{-- Header --}}
    <div class="surface-1 p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white">{{ $expense->expense_number }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ $expense->date->format('M j, Y') }}</p>
                <div class="mt-2">
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-white/5 text-gray-300">
                        {{ $expense->categoryLabel() }}
                    </span>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('expenses.edit', $expense) }}"
                   class="inline-flex items-center px-4 py-2 bg-white/5 border border-white/10 text-gray-300 text-sm font-medium rounded-md hover:bg-white/5 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                    Edit
                </a>
                <form method="POST" action="{{ route('expenses.destroy', $expense) }}"
                      onsubmit="return confirm('Delete this expense?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-transparent border border-red-500/30 text-red-400 text-sm font-medium rounded-md hover:bg-red-500/10 transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Expense details --}}
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-4">Details</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <div>
                <dt class="text-gray-500">Vendor</dt>
                <dd class="font-medium text-white mt-0.5">{{ $expense->vendor }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Amount</dt>
                <dd class="font-medium text-white mt-0.5">${{ number_format($expense->amount, 2) }}</dd>
            </div>
            @if ($expense->description)
            <div class="sm:col-span-2">
                <dt class="text-gray-500">Description</dt>
                <dd class="font-medium text-white mt-0.5">{{ $expense->description }}</dd>
            </div>
            @endif
            <div>
                <dt class="text-gray-500">Payment Method</dt>
                <dd class="font-medium text-white mt-0.5">{{ $expense->paymentMethodLabel() ?: '—' }}</dd>
            </div>
            @if ($expense->reference_number)
            <div>
                <dt class="text-gray-500">Reference #</dt>
                <dd class="font-medium text-white mt-0.5 font-mono">{{ $expense->reference_number }}</dd>
            </div>
            @endif
            <div>
                <dt class="text-gray-500">Recorded By</dt>
                <dd class="font-medium text-white mt-0.5">{{ $expense->creator?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Created</dt>
                <dd class="font-medium text-white mt-0.5">{{ $expense->created_at->format('M j, Y g:i A') }}</dd>
            </div>
        </dl>
    </div>

    {{-- Receipt --}}
    @if ($expense->receipt_path)
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-2">Receipt</h2>
        <a href="{{ route('expenses.receipt', $expense) }}"
           class="inline-flex items-center text-sm text-cyan-400 hover:text-cyan-300 font-medium">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Download Receipt
        </a>
    </div>
    @endif

    {{-- Notes --}}
    @if ($expense->notes)
    <div class="surface-1 p-6">
        <h2 class="text-lg font-semibold text-gray-300 mb-2">Notes</h2>
        <p class="text-sm text-gray-300 whitespace-pre-line">{{ $expense->notes }}</p>
    </div>
    @endif

    {{-- Documents --}}
    @include('partials.document-list', [
        'documents' => $expense->documents,
        'uploadUrl' => route('documents.store-generic', ['type' => 'expense', 'id' => $expense->id]),
    ])
</div>
@endsection
