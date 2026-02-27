@extends('layouts.app')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Chart of Accounts</h1>
    </div>

    {{-- Account groups --}}
    @foreach (['asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity', 'revenue' => 'Revenue', 'cogs' => 'Cost of Goods Sold', 'expense' => 'Expenses'] as $type => $label)
        @if (isset($grouped[$type]) && $grouped[$type]->count())
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ $label }}</h2>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-24">Code</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase w-20">Status</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-32">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($grouped[$type] as $account)
                            <tr class="{{ $account->is_active ? '' : 'opacity-50' }}">
                                <td class="px-4 py-2 text-sm font-mono text-gray-900">{{ $account->code }}</td>
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">
                                    <a href="{{ route('accounting.general-ledger', $account) }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                        {{ $account->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $account->description ?? '—' }}</td>
                                <td class="px-4 py-2 text-center">
                                    @if ($account->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm font-mono text-right {{ $account->balance() < 0 ? 'text-red-600' : 'text-gray-900' }}">
                                    ${{ number_format(abs($account->balance()), 2) }}
                                    @if ($account->balance() < 0) <span class="text-xs">(CR)</span> @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endforeach

    @if ($accounts->isEmpty())
        <div class="bg-white shadow rounded-lg p-8 text-center">
            <p class="text-gray-500">No accounts found. Run the ChartOfAccountsSeeder to set up default accounts.</p>
        </div>
    @endif
</div>
@endsection
