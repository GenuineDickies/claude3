@extends('layouts.app')

@section('content')
<div class="space-y-5">

    @include('documents._sub-nav')

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Import Categories</h1>
        <p class="mt-1 text-sm text-gray-500">Accounts used by the AI to categorise scanned bank statements and spreadsheets. Separate from the formal <a href="{{ route('accounting.chart-of-accounts') }}" class="text-blue-600 hover:underline">Chart of Accounts</a>.</p>
    </div>

    {{-- Account groups --}}
    @foreach (['asset' => 'Assets / Bank Accounts', 'equity' => 'Equity', 'revenue' => 'Revenue / Income', 'cogs' => 'Cost of Goods Sold', 'expense' => 'Expenses'] as $type => $label)
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
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($grouped[$type] as $account)
                            <tr class="{{ $account->is_active ? '' : 'opacity-50' }}">
                                <td class="px-4 py-2 text-sm font-mono text-gray-900">{{ $account->code }}</td>
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $account->name }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $account->description ?? '—' }}</td>
                                <td class="px-4 py-2 text-center">
                                    @if ($account->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                                    @endif
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
            <p class="text-gray-500">No import categories found. Run the ChartOfAccountsSeeder to set up default categories.</p>
        </div>
    @endif
</div>
@endsection
