@extends('layouts.app')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Journal Entries</h1>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white shadow rounded-lg p-4">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select name="status" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">All</option>
                    @foreach (['posted' => 'Posted', 'draft' => 'Draft', 'void' => 'Void'] as $val => $lbl)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="w-full rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <div class="flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Entry #, memo, ref…" class="flex-1 rounded-md border-gray-300 text-sm">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Filter</button>
                </div>
            </div>
        </div>
    </form>

    {{-- Entries --}}
    @forelse ($entries as $entry)
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between flex-wrap gap-2">
                <div>
                    <span class="font-mono text-sm font-semibold text-gray-900">{{ $entry->entry_number }}</span>
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
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$entry->status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ ucfirst($entry->status) }}
                    </span>
                </div>
            </div>

            @if ($entry->memo)
                <div class="px-4 py-2 text-sm text-gray-600 bg-gray-50 border-b border-gray-100">
                    {{ $entry->memo }}
                </div>
            @endif

            @if ($entry->status === 'void' && $entry->void_reason)
                <div class="px-4 py-2 text-sm text-red-600 bg-red-50 border-b border-red-100">
                    Void reason: {{ $entry->void_reason }}
                </div>
            @endif

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
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
                            <td class="px-4 py-1.5 text-sm text-gray-900">{{ $line->account->name }}</td>
                            <td class="px-4 py-1.5 text-sm text-gray-500">{{ $line->description ?? '—' }}</td>
                            <td class="px-4 py-1.5 text-sm font-mono text-right {{ (float) $line->debit > 0 ? 'text-gray-900' : 'text-gray-300' }}">
                                {{ (float) $line->debit > 0 ? '$' . number_format($line->debit, 2) : '' }}
                            </td>
                            <td class="px-4 py-1.5 text-sm font-mono text-right {{ (float) $line->credit > 0 ? 'text-gray-900' : 'text-gray-300' }}">
                                {{ (float) $line->credit > 0 ? '$' . number_format($line->credit, 2) : '' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div class="bg-white shadow rounded-lg p-8 text-center">
            <p class="text-gray-500">No journal entries found.</p>
        </div>
    @endforelse

    <div>{{ $entries->links() }}</div>
</div>
@endsection
