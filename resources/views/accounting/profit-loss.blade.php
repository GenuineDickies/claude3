@extends('layouts.app')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Profit &amp; Loss</h1>
        <form method="GET" class="flex items-center gap-3 flex-wrap">
            <label class="text-sm text-gray-600">From:</label>
            <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="rounded-md border-gray-300 text-sm">
            <label class="text-sm text-gray-600">To:</label>
            <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="rounded-md border-gray-300 text-sm">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Update</button>
        </form>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-36">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">

                {{-- Revenue --}}
                <tr class="bg-green-50">
                    <td colspan="2" class="px-4 py-2 text-sm font-semibold text-green-800">Revenue</td>
                    <td></td>
                </tr>
                @forelse ($revenue as $row)
                    <tr>
                        <td class="px-4 py-1.5 pl-8 text-sm font-mono text-gray-500">{{ $row['code'] }}</td>
                        <td class="px-4 py-1.5 text-sm text-gray-900">{{ $row['name'] }}</td>
                        <td class="px-4 py-1.5 text-sm font-mono text-right text-gray-900">${{ number_format($row['balance'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-1.5 pl-8 text-sm text-gray-400 italic">No revenue</td></tr>
                @endforelse
                <tr class="bg-green-50 border-t border-green-200">
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
                            <td class="px-4 py-1.5 text-sm text-gray-900">{{ $row['name'] }}</td>
                            <td class="px-4 py-1.5 text-sm font-mono text-right text-red-600">({{ number_format($row['balance'], 2) }})</td>
                        </tr>
                    @endforeach
                    <tr class="bg-orange-50 border-t border-orange-200">
                        <td colspan="2" class="px-4 py-2 text-sm font-semibold text-orange-800 text-right">Total COGS</td>
                        <td class="px-4 py-2 text-sm font-mono font-semibold text-right text-orange-800">({{ number_format($total_cogs, 2) }})</td>
                    </tr>
                @endif

                {{-- Gross Profit --}}
                <tr class="bg-blue-50 border-t-2 border-blue-200">
                    <td colspan="2" class="px-4 py-2 text-sm font-bold text-blue-900 text-right">Gross Profit</td>
                    <td class="px-4 py-2 text-sm font-mono font-bold text-right {{ $gross_profit >= 0 ? 'text-blue-900' : 'text-red-600' }}">
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
                        <td class="px-4 py-1.5 text-sm text-gray-900">{{ $row['name'] }}</td>
                        <td class="px-4 py-1.5 text-sm font-mono text-right text-red-600">({{ number_format($row['balance'], 2) }})</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-1.5 pl-8 text-sm text-gray-400 italic">No expenses</td></tr>
                @endforelse
                <tr class="bg-red-50 border-t border-red-200">
                    <td colspan="2" class="px-4 py-2 text-sm font-semibold text-red-800 text-right">Total Expenses</td>
                    <td class="px-4 py-2 text-sm font-mono font-semibold text-right text-red-800">({{ number_format($total_expenses, 2) }})</td>
                </tr>

                {{-- Net Income --}}
                <tr class="bg-gray-100 border-t-2 border-gray-400">
                    <td colspan="2" class="px-4 py-3 text-base font-bold text-gray-900 text-right">Net Income</td>
                    <td class="px-4 py-3 text-base font-mono font-bold text-right {{ $net_income >= 0 ? 'text-green-700' : 'text-red-600' }}">
                        ${{ number_format($net_income, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
