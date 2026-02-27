@extends('layouts.app')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Trial Balance</h1>
        <form method="GET" class="flex items-center gap-3">
            <label class="text-sm text-gray-600">As of:</label>
            <input type="date" name="as_of" value="{{ $asOf->format('Y-m-d') }}" class="rounded-md border-gray-300 text-sm">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Update</button>
        </form>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
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
                        <td class="px-4 py-2 text-sm font-mono text-gray-900">{{ $row['code'] }}</td>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $row['name'] }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500 capitalize">{{ $row['type'] }}</td>
                        <td class="px-4 py-2 text-sm font-mono text-right text-gray-900">
                            {{ $row['debit'] > 0 ? '$' . number_format($row['debit'], 2) : '' }}
                        </td>
                        <td class="px-4 py-2 text-sm font-mono text-right text-gray-900">
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
                <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                    <tr class="font-semibold">
                        <td colspan="3" class="px-4 py-3 text-sm text-gray-900">Totals</td>
                        <td class="px-4 py-3 text-sm font-mono text-right text-gray-900">${{ number_format($totalDebits, 2) }}</td>
                        <td class="px-4 py-3 text-sm font-mono text-right text-gray-900">${{ number_format($totalCredits, 2) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    @if (count($accounts) && round($totalDebits, 2) !== round($totalCredits, 2))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-red-800 font-semibold">Warning: Trial balance is out of balance!</p>
            <p class="text-red-600 text-sm">Difference: ${{ number_format(abs($totalDebits - $totalCredits), 2) }}</p>
        </div>
    @endif
</div>
@endsection
