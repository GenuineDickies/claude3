{{-- Balance Sheet: preserves as-of date filter form, two-column assets vs liabilities+equity layout, net income row, balanced/out-of-balance summary --}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-4">
        <h1 class="text-2xl font-bold text-white">Balance Sheet</h1>
        <form method="GET" class="flex items-center gap-3">
            <label class="text-sm text-gray-400">As of:</label>
            <input type="date" name="as_of" value="{{ $asOf->format('Y-m-d') }}" class="rounded-md border-white/10 text-sm">
            <button type="submit" class="px-4 py-2 btn-crystal text-sm">Update</button>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- LEFT: Assets --}}
        <div class="surface-1 overflow-hidden">
            <div class="px-4 py-3 border-b border-white/10 bg-cyan-500/10">
                <h2 class="text-sm font-semibold text-blue-800 uppercase tracking-wide">Assets</h2>
            </div>
            <table class="table-crystal min-w-full divide-y divide-white/5">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-20">Code</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-32">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($assets as $row)
                        <tr>
                            <td class="px-4 py-1.5 text-sm font-mono text-gray-500">{{ $row['code'] }}</td>
                            <td class="px-4 py-1.5 text-sm text-white">{{ $row['name'] }}</td>
                            <td class="px-4 py-1.5 text-sm font-mono text-right text-white">${{ number_format($row['balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-4 text-center text-gray-400 text-sm">No asset balances</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-cyan-500/10 border-t-2 border-cyan-500/30">
                    <tr class="font-semibold">
                        <td colspan="2" class="px-4 py-3 text-sm text-blue-800">Total Assets</td>
                        <td class="px-4 py-3 text-sm font-mono text-right text-blue-800">${{ number_format($total_assets, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- RIGHT: Liabilities + Equity --}}
        <div class="space-y-6">

            {{-- Liabilities --}}
            <div class="surface-1 overflow-hidden">
                <div class="px-4 py-3 border-b border-white/10 bg-amber-50">
                    <h2 class="text-sm font-semibold text-amber-800 uppercase tracking-wide">Liabilities</h2>
                </div>
                <table class="table-crystal min-w-full divide-y divide-white/5">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-20">Code</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-32">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($liabilities as $row)
                            <tr>
                                <td class="px-4 py-1.5 text-sm font-mono text-gray-500">{{ $row['code'] }}</td>
                                <td class="px-4 py-1.5 text-sm text-white">{{ $row['name'] }}</td>
                                <td class="px-4 py-1.5 text-sm font-mono text-right text-white">${{ number_format($row['balance'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-4 text-center text-gray-400 text-sm">No liability balances</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-amber-50 border-t border-amber-200">
                        <tr class="font-semibold">
                            <td colspan="2" class="px-4 py-2 text-sm text-amber-800">Total Liabilities</td>
                            <td class="px-4 py-2 text-sm font-mono text-right text-amber-800">${{ number_format($total_liabilities, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Equity --}}
            <div class="surface-1 overflow-hidden">
                <div class="px-4 py-3 border-b border-white/10 bg-green-500/10">
                    <h2 class="text-sm font-semibold text-green-800 uppercase tracking-wide">Equity</h2>
                </div>
                <table class="table-crystal min-w-full divide-y divide-white/5">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-20">Code</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-32">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($equity as $row)
                            <tr>
                                <td class="px-4 py-1.5 text-sm font-mono text-gray-500">{{ $row['code'] }}</td>
                                <td class="px-4 py-1.5 text-sm text-white">{{ $row['name'] }}</td>
                                <td class="px-4 py-1.5 text-sm font-mono text-right text-white">${{ number_format($row['balance'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-4 text-center text-gray-400 text-sm">No equity balances</td></tr>
                        @endforelse
                        {{-- Net Income (Retained Earnings) --}}
                        <tr class="bg-green-500/10">
                            <td class="px-4 py-1.5 text-sm font-mono text-gray-500"></td>
                            <td class="px-4 py-1.5 text-sm font-medium text-green-800 italic">Net Income (Current Period)</td>
                            <td class="px-4 py-1.5 text-sm font-mono text-right {{ $net_income >= 0 ? 'text-green-700' : 'text-red-400' }}">
                                ${{ number_format($net_income, 2) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-green-500/10 border-t border-green-500/30">
                        <tr class="font-semibold">
                            <td colspan="2" class="px-4 py-2 text-sm text-green-800">Total Equity + Net Income</td>
                            <td class="px-4 py-2 text-sm font-mono text-right text-green-800">${{ number_format($total_equity + $net_income, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Summary --}}
    <div class="surface-1 overflow-hidden">
        <table class="table-crystal min-w-full">
            <tbody>
                <tr class="border-b">
                    <td class="px-4 py-3 text-sm font-semibold text-white">Total Assets</td>
                    <td class="px-4 py-3 text-sm font-mono text-right text-white">${{ number_format($total_assets, 2) }}</td>
                </tr>
                <tr class="border-b">
                    <td class="px-4 py-3 text-sm font-semibold text-white">Total Liabilities + Equity + Net Income</td>
                    <td class="px-4 py-3 text-sm font-mono text-right text-white">${{ number_format($equity_plus_income, 2) }}</td>
                </tr>
                <tr class="{{ round($total_assets, 2) === round($equity_plus_income, 2) ? 'bg-green-500/10' : 'bg-red-50' }}">
                    <td class="px-4 py-3 text-sm font-bold {{ round($total_assets, 2) === round($equity_plus_income, 2) ? 'text-green-800' : 'text-red-800' }}">
                        {{ round($total_assets, 2) === round($equity_plus_income, 2) ? '✓ Balanced' : '✗ Out of balance' }}
                    </td>
                    <td class="px-4 py-3 text-sm font-mono text-right {{ round($total_assets, 2) === round($equity_plus_income, 2) ? 'text-green-800' : 'text-red-800' }}">
                        @if (round($total_assets, 2) !== round($equity_plus_income, 2))
                            Difference: ${{ number_format(abs($total_assets - $equity_plus_income), 2) }}
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
