@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">All Tickets</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('rapid-dispatch.create') }}"
               class="inline-flex items-center px-3 py-2 btn-crystal-amber text-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z"/></svg>
                Rapid
            </a>
            <a href="{{ route('service-requests.create') }}"
               class="inline-flex items-center px-4 py-2 btn-crystal text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                New Ticket
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="surface-1 p-4">
        <form method="GET" action="{{ route('service-requests.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="status" class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="status" id="status"
                        class="rounded-md border-white/10 text-sm shadow-xs input-crystal">
                    <option value="">All Statuses</option>
                    @foreach (\App\Models\ServiceRequest::STATUSES as $s)
                        <option value="{{ $s }}" {{ $currentStatus === $s ? 'selected' : '' }}>
                            {{ \App\Models\ServiceRequest::STATUS_LABELS[$s] ?? ucwords(str_replace('_', ' ', $s)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-[200px]">
                <label for="search" class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" name="search" id="search" value="{{ $currentSearch }}"
                       placeholder="Search customer, phone, location…"
                       class="w-full rounded-md border-white/10 text-sm shadow-xs input-crystal">
            </div>

            <button type="submit"
                    class="inline-flex items-center px-4 py-2 btn-crystal text-sm">
                Filter
            </button>

            @if ($currentStatus || $currentSearch)
                <a href="{{ route('service-requests.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-300 underline">Clear</a>
            @endif
        </form>
    </div>

    {{-- Results --}}
    <div class="surface-1">
        @if ($serviceRequests->isEmpty())
            <div class="p-6 text-center">
                <p class="text-sm text-gray-500 mb-3">No tickets found.</p>
                <a href="{{ route('service-requests.create') }}"
                   class="text-sm text-cyan-400 hover:text-cyan-300 underline">Create a new ticket</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table-crystal min-w-full divide-y divide-white/5">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-transparent divide-y divide-white/5">
                        @foreach ($serviceRequests as $sr)
                            <tr class="hover:bg-white/5">
                                <td class="px-4 py-3 text-sm">
                                    <a href="{{ route('service-requests.show', $sr) }}" class="font-mono text-cyan-400 hover:text-cyan-300 font-medium">#{{ $sr->id }}</a>
                                </td>
                                <td class="px-4 py-3 text-sm text-white">
                                    {{ $sr->customer?->first_name }} {{ $sr->customer?->last_name }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-400 font-mono">
                                    {{ $sr->customer?->phone }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-400">
                                    {{ $sr->catalogItem?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <x-status-badge :status="$sr->status" />
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-400">
                                    {{ Str::limit($sr->location, 30) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                                    {{ $sr->created_at->diffForHumans() }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <a href="{{ route('service-requests.show', $sr) }}" class="text-gray-400 hover:text-cyan-400" title="View ticket">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($serviceRequests->hasPages())
                <div class="px-4 py-3 border-t border-white/10">
                    {{ $serviceRequests->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
