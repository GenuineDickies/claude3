{{-- Trial Balance: preserves as-of date filter form, account table with debits/credits, totals footer, out-of-balance warning --}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-4">
        <h1 class="text-2xl font-bold text-white">Trial Balance</h1>
        <form method="GET" class="flex items-center gap-3">
            <label class="text-sm text-gray-400">As of:</label>
            <input type="date" name="as_of" value="{{ $asOf->format('Y-m-d') }}" class="rounded-md border-white/10 text-sm">
            <button type="submit" class="px-4 py-2 btn-crystal text-sm">Update</button>
        </form>
    </div>

    <div class="surface-1 overflow-hidden">
        <table class="table-crystal min-w-full divide-y divide-white/5">
            <thead class="bg-white/5">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-20">Type</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-32">Debit</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-32">Credit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($accounts as $row)
                    <tr>
                        <td class="px-4 py-2 text-sm font-mono text-white">{{ $row['code'] }}</td>
                        <td class="px-4 py-2 text-sm text-white">{{ $row['name'] }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500 capitalize">{{ $row['type'] }}</td>
                        <td class="px-4 py-2 text-sm font-mono text-right text-white">
                            {{ $row['debit'] > 0 ? '$' . number_format($row['debit'], 2) : '' }}
                        </td>
                        <td class="px-4 py-2 text-sm font-mono text-right text-white">
                            {{ $row['credit'] > 0 ? '$' . number_format($row['credit'], 2) : '' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">No activity found.</td>
                    </tr>
                @endforelse
            </tbody>
            @if (count($accounts))
                <tfoot class="bg-white/5 border-t-2 border-white/10">
                    <tr class="font-semibold">
                        <td colspan="3" class="px-4 py-3 text-sm text-white">Totals</td>
                        <td class="px-4 py-3 text-sm font-mono text-right text-white">${{ number_format($totalDebits, 2) }}</td>
                        <td class="px-4 py-3 text-sm font-mono text-right text-white">${{ number_format($totalCredits, 2) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    @if (count($accounts) && round($totalDebits, 2) !== round($totalCredits, 2))
        <div class="bg-red-50 border border-red-500/30 rounded-lg p-4">
            <p class="text-red-800 font-semibold">Warning: Trial balance is out of balance!</p>
            <p class="text-red-400 text-sm">Difference: ${{ number_format(abs($totalDebits - $totalCredits), 2) }}</p>
        </div>
    @endif
</div>
@endsection
