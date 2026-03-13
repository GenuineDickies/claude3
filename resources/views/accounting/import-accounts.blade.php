@extends('layouts.app')

@section('content')
<div class="space-y-5">

    @include('documents._sub-nav')

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-white">Import Categories</h1>
        <p class="mt-1 text-sm text-gray-500">Accounts used by the AI to categorise scanned bank statements and spreadsheets. Separate from the formal <a href="{{ route('accounting.chart-of-accounts') }}" class="text-cyan-400 hover:underline">Chart of Accounts</a>.</p>
    </div>

    {{-- Account groups --}}
    @foreach (['asset' => 'Assets / Bank Accounts', 'equity' => 'Equity', 'revenue' => 'Revenue / Income', 'cogs' => 'Cost of Goods Sold', 'expense' => 'Expenses'] as $type => $label)
        @if (isset($grouped[$type]) && $grouped[$type]->count())
            <div class="surface-1 overflow-hidden">
                <div class="px-4 py-3 border-b border-white/10 bg-white/5">
                    <h2 class="text-sm font-semibold text-gray-300 uppercase tracking-wide">{{ $label }}</h2>
                </div>
                <table class="table-crystal min-w-full divide-y divide-white/5">
                    <thead class="bg-white/5">
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
                                <td class="px-4 py-2 text-sm font-mono text-white">{{ $account->code }}</td>
                                <td class="px-4 py-2 text-sm font-medium text-white">{{ $account->name }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $account->description ?? '—' }}</td>
                                <td class="px-4 py-2 text-center">
                                    @if ($account->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-white/5 text-gray-400">Inactive</span>
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
        <div class="surface-1 p-8 text-center">
            <p class="text-gray-500">No import categories found. Run the ChartOfAccountsSeeder to set up default categories.</p>
        </div>
    @endif
</div>
@endsection
