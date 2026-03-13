@extends('layouts.app')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">General Ledger</h1>
            <p class="text-sm text-gray-500 mt-1">
                <span class="font-mono">{{ $account->code }}</span> — {{ $account->name }}
                <span class="text-xs ml-2 px-2 py-0.5 bg-white/5 rounded capitalize">{{ $account->type }}</span>
            </p>
        </div>
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
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-28">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-32">Entry #</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">Debit</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">Credit</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-32">Balance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                {{-- Opening balance --}}
                <tr class="bg-white/5">
                    <td class="px-4 py-2 text-sm text-gray-500" colspan="3">Opening Balance</td>
                    <td></td>
                    <td></td>
                    <td class="px-4 py-2 text-sm font-mono text-right font-semibold text-gray-300">
                        ${{ number_format($opening_balance, 2) }}
                    </td>
                </tr>

                @forelse ($entries as $row)
                    <tr>
                        <td class="px-4 py-1.5 text-sm text-gray-500">{{ $row['date'] }}</td>
                        <td class="px-4 py-1.5 text-sm font-mono text-cyan-400">{{ $row['entry_number'] }}</td>
                        <td class="px-4 py-1.5 text-sm text-white">{{ $row['description'] ?: $row['memo'] }}</td>
                        <td class="px-4 py-1.5 text-sm font-mono text-right {{ $row['debit'] > 0 ? 'text-white' : '' }}">
                            {{ $row['debit'] > 0 ? '$' . number_format($row['debit'], 2) : '' }}
                        </td>
                        <td class="px-4 py-1.5 text-sm font-mono text-right {{ $row['credit'] > 0 ? 'text-white' : '' }}">
                            {{ $row['credit'] > 0 ? '$' . number_format($row['credit'], 2) : '' }}
                        </td>
                        <td class="px-4 py-1.5 text-sm font-mono text-right font-medium {{ $row['running_balance'] < 0 ? 'text-red-400' : 'text-white' }}">
                            ${{ number_format($row['running_balance'], 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-400 text-sm">No activity in this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="text-center">
        <a href="{{ route('accounting.chart-of-accounts') }}" class="text-sm text-cyan-400 hover:text-cyan-300 hover:underline">
            ← Back to Chart of Accounts
        </a>
    </div>
</div>
@endsection
