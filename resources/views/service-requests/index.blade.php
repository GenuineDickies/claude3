{{--
  Service Request Queue — service-requests.index
  Controller vars: $serviceRequests (paginator), $currentStatus, $currentSearch
  Features preserved:
    - Rapid Dispatch button (btn-crystal-amber)
    - New Service Request button
    - Status filter dropdown (all ServiceRequest::STATUSES with STATUS_LABELS)
    - Search by customer/phone/location
    - Clear filters link (conditional)
    - All columns: #, Customer, Phone, Service, Status, Location, Created, view action
    - Empty state CTA
    - Pagination with withQueryString preserving filters
  Added:
    - Quick filter chips for common statuses (below the big dropdown — the
      dropdown is still the source of truth; chips just submit the form with
      a preset)
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">

    {{-- Toolbar: title + Rapid + New --}}
    <div class="page-toolbar">
        <div>
            <div class="page-toolbar__title">
                <span>Service Request Queue</span>
                <span class="page-toolbar__meta">
                    {{ $serviceRequests->total() ?? $serviceRequests->count() }} total
                </span>
            </div>
        </div>
        <div class="page-toolbar__actions">
            <a href="{{ route('rapid-dispatch.create') }}" class="btn-crystal-amber btn-crystal-sm inline-flex items-center">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z"/>
                </svg>
                Rapid
            </a>
            <a href="{{ route('service-requests.create') }}" class="btn-crystal btn-crystal-sm inline-flex items-center">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                New Service Request
            </a>
        </div>
    </div>

    {{-- Quick filter chips + search bar --}}
    <div class="surface-1 p-3 space-y-3">
        {{-- Chip row (fast presets) --}}
        <div class="filter-chip-row">
            @php
                $chipPresets = [
                    ''           => 'All',
                    'new'        => 'New',
                    'dispatched' => 'Dispatched',
                    'en_route'   => 'En route',
                    'on_scene'   => 'On scene',
                    'completed'  => 'Completed',
                    'cancelled'  => 'Cancelled',
                ];
            @endphp
            @foreach($chipPresets as $val => $label)
                @php
                    $params = request()->query();
                    if ($val === '') { unset($params['status']); } else { $params['status'] = $val; }
                    $url = route('service-requests.index', $params);
                    $active = ($currentStatus ?? '') === $val || (($currentStatus ?? '') === '' && $val === '');
                @endphp
                <a href="{{ $url }}"
                   class="filter-chip {{ $active ? 'filter-chip--active' : '' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Full filter form (status dropdown is source of truth; covers all statuses,
             including any not shown as chips) --}}
        <form method="GET" action="{{ route('service-requests.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="status" class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="status" id="status" class="rounded-md border-white/10 text-sm shadow-xs input-crystal">
                    <option value="">All statuses</option>
                    @foreach (\App\Models\ServiceRequest::STATUSES as $s)
                        <option value="{{ $s }}" {{ $currentStatus === $s ? 'selected' : '' }}>
                            {{ \App\Models\ServiceRequest::STATUS_LABELS[$s] ?? ucwords(str_replace('_', ' ', $s)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-[220px]">
                <label for="search" class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" name="search" id="search" value="{{ $currentSearch }}"
                       placeholder="Customer, phone, location…"
                       class="w-full rounded-md border-white/10 text-sm shadow-xs input-crystal">
            </div>

            <button type="submit" class="btn-crystal btn-crystal-sm">Filter</button>

            @if ($currentStatus || $currentSearch)
                <a href="{{ route('service-requests.index') }}" class="text-xs text-gray-500 hover:text-gray-300 underline">
                    Clear
                </a>
            @endif
        </form>
    </div>

    {{-- Results table --}}
    <div class="surface-1">
        @if ($serviceRequests->isEmpty())
            <div class="p-6 text-center">
                <p class="text-sm text-gray-500 mb-3">No service requests found.</p>
                <a href="{{ route('service-requests.create') }}" class="text-sm text-cyan-400 hover:text-cyan-300 underline">
                    Create a new service request
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table-crystal table-crystal--dense min-w-full divide-y divide-white/5">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="text-left">#</th>
                            <th class="text-left">Customer</th>
                            <th class="text-left">Phone</th>
                            <th class="text-left">Service</th>
                            <th class="text-left">Status</th>
                            <th class="text-left">Location</th>
                            <th class="text-left">Created</th>
                            <th class="text-left"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-transparent divide-y divide-white/5">
                        @foreach ($serviceRequests as $sr)
                            <tr class="hover:bg-white/5">
                                <td>
                                    <a href="{{ route('service-requests.show', $sr) }}" class="font-mono text-cyan-400 hover:text-cyan-300 font-medium">#{{ $sr->id }}</a>
                                </td>
                                <td class="text-white">{{ $sr->customer?->first_name }} {{ $sr->customer?->last_name }}</td>
                                <td class="text-gray-400 font-mono">{{ $sr->customer?->phone }}</td>
                                <td class="text-gray-400">{{ $sr->catalogItem?->name ?? '—' }}</td>
                                <td><x-status-badge :status="$sr->status" /></td>
                                <td class="text-gray-400">{{ Str::limit($sr->location, 30) }}</td>
                                <td class="text-gray-500 whitespace-nowrap">{{ $sr->created_at->diffForHumans() }}</td>
                                <td class="text-right">
                                    <a href="{{ route('service-requests.show', $sr) }}" class="text-gray-400 hover:text-cyan-400" title="View service request">
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
                <div class="px-4 py-3 border-t border-white/10 pagination-crystal">
                    {{ $serviceRequests->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
