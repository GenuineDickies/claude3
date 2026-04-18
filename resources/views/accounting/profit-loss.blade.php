{{-- Profit & Loss: preserves date-range filter form, revenue/COGS/gross profit/expense/net income table sections with subtotals --}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-4">
        <h1 class="text-2xl font-bold text-white">Profit &amp; Loss</h1>
        <form method="GET" class="flex items-center gap-3 flex-wrap">
            <label class="text-sm text-gray-400">From:</label>
            <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="rounded-md border-white/10 text-sm">
            <label class="text-sm text-gray-400">To:</label>
            <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="rounded-md border-white/10 text-sm">
            <button type="submit" class="px-4 py-2 btn-crystal text-sm">Update</button>
        </form>
    </div>

    <div class="surface-1 overflow-hidden">
        <table class="table-crystal min-w-full divide-y divide-white/5">
            <thead class="bg-white/5">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-36">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">

                {{-- Revenue --}}
                <tr class="bg-green-500/10">
                    <td colspan="2" class="px-4 py-2 text-sm font-semibold text-green-800">Revenue</td>
                    <td></td>
                </tr>
                @forelse ($revenue as $row)
                    <tr>
                        <td class="px-4 py-1.5 pl-8 text-sm font-mono text-gray-500">{{ $row['code'] }}</td>
                        <td class="px-4 py-1.5 text-sm text-white">{{ $row['name'] }}</td>
                        <td class="px-4 py-1.5 text-sm font-mono text-right text-white">${{ number_format($row['balance'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-1.5 pl-8 text-sm text-gray-400 italic">No revenue</td></tr>
                @endforelse
                <tr class="bg-green-500/10 border-t border-green-500/30">
                    <td colspan="2" class="px-4 py-2 text-sm font-semibold text-green-800 text-right">Total Revenue</td>
                    <td class="px-4 py-2 text-sm font-mono font-semibold text-right text-green-800">${{ number_format($total_revenue, 2) }}</td>
                </tr>

                {{-- COGS --}}
                @if (count($cogs))
                    <tr class="bg-orange-50">
                        <td colspan="2" class="px-4 py-2 text-sm font-semibold text-orange-800">Cost of Goods Sold</td>
                        <td></td>
                    </tr>
                    @foreach ($cogs as $row)
                        <tr>
                            <td class="px-4 py-1.5 pl-8 text-sm font-mono text-gray-500">{{ $row['code'] }}</td>
                            <td class="px-4 py-1.5 text-sm text-white">{{ $row['name'] }}</td>
                            <td class="px-4 py-1.5 text-sm font-mono text-right text-red-400">({{ number_format($row['balance'], 2) }})</td>
                        </tr>
                    @endforeach
                    <tr class="bg-orange-50 border-t border-orange-200">
                        <td colspan="2" class="px-4 py-2 text-sm font-semibold text-orange-800 text-right">Total COGS</td>
                        <td class="px-4 py-2 text-sm font-mono font-semibold text-right text-orange-800">({{ number_format($total_cogs, 2) }})</td>
                    </tr>
                @endif

                {{-- Gross Profit --}}
                <tr class="bg-cyan-500/10 border-t-2 border-cyan-500/30">
                    <td colspan="2" class="px-4 py-2 text-sm font-bold text-blue-900 text-right">Gross Profit</td>
                    <td class="px-4 py-2 text-sm font-mono font-bold text-right {{ $gross_profit >= 0 ? 'text-blue-900' : 'text-red-400' }}">
                        ${{ number_format($gross_profit, 2) }}
                    </td>
                </tr>

                {{-- Expenses --}}
                <tr class="bg-red-50">
                    <td colspan="2" class="px-4 py-2 text-sm font-semibold text-red-800">Operating Expenses</td>
                    <td></td>
                </tr>
                @forelse ($expenses as $row)
                    <tr>
                        <td class="px-4 py-1.5 pl-8 text-sm font-mono text-gray-500">{{ $row['code'] }}</td>
                        <td class="px-4 py-1.5 text-sm text-white">{{ $row['name'] }}</td>
                        <td class="px-4 py-1.5 text-sm font-mono text-right text-red-400">({{ number_format($row['balance'], 2) }})</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-1.5 pl-8 text-sm text-gray-400 italic">No expenses</td></tr>
                @endforelse
                <tr class="bg-red-50 border-t border-red-500/30">
                    <td colspan="2" class="px-4 py-2 text-sm font-semibold text-red-800 text-right">Total Expenses</td>
                    <td class="px-4 py-2 text-sm font-mono font-semibold text-right text-red-800">({{ number_format($total_expenses, 2) }})</td>
                </tr>

                {{-- Net Income --}}
                <tr class="bg-white/5 border-t-2 border-gray-400">
                    <td colspan="2" class="px-4 py-3 text-base font-bold text-white text-right">Net Income</td>
                    <td class="px-4 py-3 text-base font-mono font-bold text-right {{ $net_income >= 0 ? 'text-green-700' : 'text-red-400' }}">
                        ${{ number_format($net_income, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
