{{--
  Dashboard — White Knight internal
  Controller vars: $companyName, $companyTagline (from AppLayout view composer),
                   $companyLogoUrl, $open, $today, $customers, $recent,
                   $apiHealth, $complianceEnabled, $compliance
  Features preserved:
    - Company name + tagline (shown compactly in header)
    - Primary actions: New Dispatch Service Request, View Active Queue
    - KPI counters: Open, Created today, Active customers
    - Recent service requests table with customer, service, location, status, created_at
    - Per-row view link and hover row
    - API Health panel (4 states: total, healthy, degraded, down) with Manage link
    - Technician Compliance panel (conditional on $complianceEnabled) with Manage link
    - All $recent empty-state messaging
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">

    {{-- Page toolbar: compact brand line + primary actions --}}
    <div class="page-toolbar">
        <div>
            <div class="page-toolbar__title">{{ $companyName }}</div>
            @if(!empty($companyTagline))
                <div class="page-toolbar__meta">{{ $companyTagline }}</div>
            @endif
        </div>
        <div class="page-toolbar__actions">
            <a href="{{ route('service-requests.index') }}" class="btn-crystal-secondary btn-crystal-sm">
                View Active Queue
            </a>
            <a href="{{ route('service-requests.create') }}" class="btn-crystal btn-crystal-sm inline-flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                New Dispatch Service Request
            </a>
        </div>
    </div>

    {{-- Counter strip (replaces hero-chip-row + duplicated metric-card row) --}}
    <div class="counter-strip">
        <div class="counter-cell">
            <div class="counter-cell__label">Open Service Requests</div>
            <div class="counter-cell__value text-cyan-300">{{ $open }}</div>
        </div>
        <div class="counter-cell">
            <div class="counter-cell__label">Created Today</div>
            <div class="counter-cell__value text-violet-200">{{ $today }}</div>
        </div>
        <div class="counter-cell">
            <div class="counter-cell__label">Active Customers</div>
            <div class="counter-cell__value text-amber-200">{{ $customers }}</div>
        </div>
        @if(($apiHealth?->down ?? 0) > 0)
            <div class="counter-cell counter-cell--alert">
                <div class="counter-cell__label">APIs Down</div>
                <div class="counter-cell__value text-red-200">{{ (int) $apiHealth->down }}</div>
                <div class="counter-cell__delta counter-cell__delta--down">
                    <a href="{{ route('settings.api-monitor.index') }}" class="hover:underline">Investigate →</a>
                </div>
            </div>
        @endif
        @if($complianceEnabled && $compliance && (($compliance->expired ?? 0) > 0))
            <div class="counter-cell counter-cell--alert">
                <div class="counter-cell__label">Compliance Expired</div>
                <div class="counter-cell__value text-red-200">{{ $compliance->expired }}</div>
                <div class="counter-cell__delta counter-cell__delta--down">
                    <a href="{{ route('technician-profiles.index') }}" class="hover:underline">Review →</a>
                </div>
            </div>
        @endif
    </div>

    {{-- Main layout: service requests (wide) + right rail --}}
    <div class="show-layout">

        {{-- Recent service requests (main column) --}}
        <div class="surface-1 dashboard-section">
            <div class="dashboard-section__header">
                <div>
                    <h2 class="dashboard-section__title">Recent Service Requests</h2>
                </div>
                <a href="{{ route('service-requests.index') }}" class="dashboard-section__link">View full queue →</a>
            </div>

            @if($recent->isEmpty())
                <p class="text-gray-500 text-sm">No dispatch service requests yet. Create one to get started.</p>
            @else
                <div class="dashboard-table-wrap overflow-x-auto">
                    <table class="table-crystal table-crystal--dense min-w-full">
                        <thead>
                            <tr class="border-b text-left text-gray-500">
                                <th class="pb-2 pr-4">ID</th>
                                <th class="pb-2 pr-4">Customer</th>
                                <th class="pb-2 pr-4">Service</th>
                                <th class="pb-2 pr-4">Location</th>
                                <th class="pb-2 pr-4">Status</th>
                                <th class="pb-2">Created</th>
                                <th class="pb-2 text-right"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recent as $sr)
                                <tr class="border-b last:border-0 hover:bg-white/5">
                                    <td class="py-2 pr-4">
                                        <a href="{{ route('service-requests.show', $sr) }}" class="font-mono text-cyan-400 hover:text-cyan-300 font-medium">#{{ $sr->id }}</a>
                                    </td>
                                    <td class="py-2 pr-4">{{ $sr->customer?->first_name }} {{ $sr->customer?->last_name }}</td>
                                    <td class="py-2 pr-4 text-gray-400">{{ $sr->catalogItem?->name ?? '—' }}</td>
                                    <td class="py-2 pr-4 text-gray-400">{{ \Illuminate\Support\Str::limit($sr->location, 30) }}</td>
                                    <td class="py-2 pr-4">
                                        <x-status-badge :status="$sr->status" />
                                    </td>
                                    <td class="py-2 text-gray-500">{{ $sr->created_at->diffForHumans() }}</td>
                                    <td class="py-2 text-right">
                                        <a href="{{ route('service-requests.show', $sr) }}" class="inline-flex text-gray-400 hover:text-cyan-400" title="View service request">
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
            @endif
        </div>

        {{-- Right rail: API health + Technician compliance --}}
        <aside class="right-rail">

            {{-- API health --}}
            <div class="right-rail-card">
                <div class="right-rail-card__head">
                    <div class="right-rail-card__title">API Health</div>
                    <a href="{{ route('settings.api-monitor.index') }}" class="right-rail-card__link">Manage</a>
                </div>

                @if(($apiHealth?->total ?? 0) === 0)
                    <p class="text-sm text-gray-500">No active monitored endpoints yet.</p>
                @else
                    <div class="space-y-1.5">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-400">Total endpoints</span>
                            <span class="font-mono text-white">{{ (int) $apiHealth->total }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-green-300">Healthy</span>
                            <span class="font-mono text-green-200">{{ (int) $apiHealth->healthy }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-amber-300">Degraded</span>
                            <span class="font-mono text-amber-200">{{ (int) $apiHealth->degraded }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-red-300">Down</span>
                            <span class="font-mono text-red-200">{{ (int) $apiHealth->down }}</span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Technician compliance (conditional) --}}
            @if($complianceEnabled && $compliance)
                <div class="right-rail-card">
                    <div class="right-rail-card__head">
                        <div class="right-rail-card__title">Technician Compliance</div>
                        <a href="{{ route('technician-profiles.index') }}" class="right-rail-card__link">Manage</a>
                    </div>

                    @if($compliance->total === 0)
                        <p class="text-sm text-gray-500">No technician profiles yet.</p>
                    @else
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-400">Profiles</span>
                                <span class="font-mono text-white">{{ $compliance->total }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="{{ $compliance->expired > 0 ? 'text-red-300' : 'text-green-300' }}">Expired</span>
                                <span class="font-mono {{ $compliance->expired > 0 ? 'text-red-200' : 'text-green-200' }}">{{ $compliance->expired }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="{{ $compliance->expiring > 0 ? 'text-amber-300' : 'text-green-300' }}">Expiring soon</span>
                                <span class="font-mono {{ $compliance->expiring > 0 ? 'text-amber-200' : 'text-green-200' }}">{{ $compliance->expiring }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

        </aside>
    </div>
</div>
@endsection
