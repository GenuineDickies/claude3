{{-- Journal Entries: preserves filter form (status/from/to/search), entry cards with status badges, void reasons, line item tables (debit/credit), pagination --}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-4">
        <h1 class="text-2xl font-bold text-white">Journal Entries</h1>
    </div>

    {{-- Filters --}}
    <form method="GET" class="surface-1 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Status</label>
                <select name="status" class="select-crystal w-full rounded-md border-white/10 text-sm">
                    <option value="">All</option>
                    @foreach (['posted' => 'Posted', 'draft' => 'Draft', 'void' => 'Void'] as $val => $lbl)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="w-full rounded-md border-white/10 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="w-full rounded-md border-white/10 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">Search</label>
                <div class="flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Entry #, memo, ref…" class="flex-1 rounded-md border-white/10 text-sm">
                    <button type="submit" class="px-4 py-2 btn-crystal text-sm">Filter</button>
                </div>
            </div>
        </div>
    </form>

    {{-- Entries --}}
    @forelse ($entries as $entry)
        <div class="surface-1 overflow-hidden">
            <div class="px-4 py-3 border-b border-white/10 flex items-center justify-between flex-wrap gap-2">
                <div>
                    <span class="font-mono text-sm font-semibold text-white">{{ $entry->entry_number }}</span>
                    <span class="text-sm text-gray-500 ml-2">{{ $entry->entry_date->format('M j, Y') }}</span>
                    @if ($entry->reference)
                        <span class="text-sm text-gray-400 ml-2">Ref: {{ $entry->reference }}</span>
                    @endif
                </div>
                <div>
                    @php
                        $statusColors = [
                            'posted' => 'bg-green-100 text-green-800',
                            'draft'  => 'bg-yellow-100 text-yellow-800',
                            'void'   => 'bg-red-100 text-red-800',
                        ];
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$entry->status] ?? 'bg-white/5 text-gray-400' }}">
                        {{ ucfirst($entry->status) }}
                    </span>
                </div>
            </div>

            @if ($entry->memo)
                <div class="px-4 py-2 text-sm text-gray-400 bg-white/5 border-b border-white/10">
                    {{ $entry->memo }}
                </div>
            @endif

            @if ($entry->status === 'void' && $entry->void_reason)
                <div class="px-4 py-2 text-sm text-red-400 bg-red-50 border-b border-red-100">
                    Void reason: {{ $entry->void_reason }}
                </div>
            @endif

            <table class="table-crystal min-w-full divide-y divide-white/5">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-20">Code</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-28">Debit</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-28">Credit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($entry->lines as $line)
                        <tr>
                            <td class="px-4 py-1.5 text-sm font-mono text-gray-500">{{ $line->account->code }}</td>
                            <td class="px-4 py-1.5 text-sm text-white">{{ $line->account->name }}</td>
                            <td class="px-4 py-1.5 text-sm text-gray-500">{{ $line->description ?? '—' }}</td>
                            <td class="px-4 py-1.5 text-sm font-mono text-right {{ (float) $line->debit > 0 ? 'text-white' : 'text-gray-300' }}">
                                {{ (float) $line->debit > 0 ? '$' . number_format($line->debit, 2) : '' }}
                            </td>
                            <td class="px-4 py-1.5 text-sm font-mono text-right {{ (float) $line->credit > 0 ? 'text-white' : 'text-gray-300' }}">
                                {{ (float) $line->credit > 0 ? '$' . number_format($line->credit, 2) : '' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div class="surface-1 p-8 text-center">
            <p class="text-gray-500">No journal entries found.</p>
        </div>
    @endforelse

    <div>{{ $entries->links() }}</div>
</div>
@endsection
