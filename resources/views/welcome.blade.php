@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    {{-- Hero --}}
    <section class="dashboard-hero">
        <div>
            <p class="dashboard-kicker">Dispatch Command</p>
            <div class="dashboard-title-row">
                <h1 class="dashboard-title">{{ $companyName }}</h1>
                <p class="dashboard-tagline">{{ $companyTagline }}</p>
            </div>
            <p class="dashboard-subtitle">Keep intake, active dispatch, and service follow-through in one place with a dashboard that favors clarity over clutter.</p>

            <div class="dashboard-chip-row">
                <span class="dashboard-chip"><strong>{{ $open }}</strong> open tickets</span>
                <span class="dashboard-chip"><strong>{{ $today }}</strong> created today</span>
                <span class="dashboard-chip"><strong>{{ $customers }}</strong> active customers</span>
            </div>
        </div>

        <aside class="dashboard-command">
            <div>
                <p class="dashboard-command__eyebrow">Operations</p>
                <h2 class="dashboard-command__title">Start a new job or jump straight into the active queue.</h2>
                <p class="dashboard-command__body">The fastest path should be obvious. This panel keeps the primary actions isolated and visible instead of burying them in the hero copy.</p>
            </div>

            <div class="flex flex-col gap-3">
                <a href="{{ route('service-requests.create') }}" class="inline-flex items-center justify-center px-6 py-3 btn-crystal text-sm font-semibold rounded-xl">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Dispatch Ticket
                </a>
                <a href="{{ route('service-requests.index') }}" class="inline-flex items-center justify-center px-5 py-3 btn-crystal-secondary text-sm font-semibold rounded-xl">
                    View Active Queue
                </a>
            </div>

            <div class="dashboard-command__meta">
                <div class="dashboard-command__meta-card">
                    <span>Newest Intake</span>
                    <strong>{{ $today }}</strong>
                </div>
                <div class="dashboard-command__meta-card">
                    <span>Queue Pressure</span>
                    <strong>{{ $open }}</strong>
                </div>
            </div>
        </aside>
    </section>

    {{-- Quick stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="metric-card metric-card--cyan">
            <p class="metric-card__label">New Tickets</p>
            <p class="metric-card__value text-cyan-300">{{ $open }}</p>
            <p class="metric-card__meta">Ready for dispatch</p>
        </div>
        <div class="metric-card metric-card--violet">
            <p class="metric-card__label">Created Today</p>
            <p class="metric-card__value text-violet-200">{{ $today }}</p>
            <p class="metric-card__meta">Fresh intake volume</p>
        </div>
        <div class="metric-card metric-card--amber">
            <p class="metric-card__label">Active Customers</p>
            <p class="metric-card__value text-amber-200">{{ $customers }}</p>
            <p class="metric-card__meta">Reachable customer base</p>
        </div>
    </div>

    {{-- Recent tickets --}}
    <div class="surface-1 dashboard-section">
        <div class="dashboard-section__header">
            <div>
                <p class="dashboard-section__eyebrow">Live Queue</p>
                <h2 class="dashboard-section__title">Recent Tickets</h2>
            </div>
            <a href="{{ route('service-requests.index') }}" class="dashboard-section__link">View full queue</a>
        </div>

        @if($recent->isEmpty())
            <p class="text-gray-500 text-sm">No dispatch tickets yet. Create one to get started.</p>
        @else
            <div class="dashboard-table-wrap overflow-x-auto">
                <table class="table-crystal min-w-full text-sm">
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
                                    <a href="{{ route('service-requests.show', $sr) }}" class="inline-flex text-gray-400 hover:text-cyan-400" title="View ticket">
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

    {{-- API monitor health --}}
    <div class="surface-1 dashboard-section mt-6">
        <div class="dashboard-section__header">
            <div>
                <p class="dashboard-section__eyebrow">External Services</p>
                <h2 class="dashboard-section__title">API Health</h2>
            </div>
            <a href="{{ route('settings.api-monitor.index') }}" class="dashboard-section__link">Manage</a>
        </div>

        @if(($apiHealth?->total ?? 0) === 0)
            <p class="text-sm text-gray-500">No active monitored endpoints yet.</p>
        @else
            <div class="dashboard-meta-grid">
                <div class="dashboard-meta-card">
                    <p class="dashboard-meta-card__label">Active Endpoints</p>
                    <p class="dashboard-meta-card__value">{{ (int) $apiHealth->total }}</p>
                </div>
                <div class="dashboard-meta-card" style="border-color: rgba(16,185,129,0.3); background: rgba(16,185,129,0.09);">
                    <p class="dashboard-meta-card__label text-green-300">Healthy</p>
                    <p class="dashboard-meta-card__value text-green-200">{{ (int) $apiHealth->healthy }}</p>
                </div>
                <div class="dashboard-meta-card" style="border-color: rgba(245,158,11,0.3); background: rgba(245,158,11,0.09);">
                    <p class="dashboard-meta-card__label text-amber-300">Degraded</p>
                    <p class="dashboard-meta-card__value text-amber-200">{{ (int) $apiHealth->degraded }}</p>
                </div>
                <div class="dashboard-meta-card" style="border-color: rgba(244,63,94,0.3); background: rgba(244,63,94,0.09);">
                    <p class="dashboard-meta-card__label text-red-300">Down</p>
                    <p class="dashboard-meta-card__value text-red-200">{{ (int) $apiHealth->down }}</p>
                </div>
            </div>
        @endif
    </div>

    {{-- Technician compliance (optional) --}}
    @if($complianceEnabled && $compliance)
        <div class="surface-1 dashboard-section mt-6">
            <div class="dashboard-section__header">
                <div>
                    <p class="dashboard-section__eyebrow">Readiness</p>
                    <h2 class="dashboard-section__title">Technician Compliance</h2>
                </div>
                <a href="{{ route('technician-profiles.index') }}" class="dashboard-section__link">Manage</a>
            </div>

            @if($compliance->total === 0)
                <p class="text-sm text-gray-500">No technician profiles created yet.</p>
            @else
                <div class="dashboard-meta-grid">
                    <div class="dashboard-meta-card">
                        <p class="dashboard-meta-card__label">Profiles</p>
                        <p class="dashboard-meta-card__value">{{ $compliance->total }}</p>
                    </div>
                    <div class="dashboard-meta-card" style="border-color: {{ $compliance->expired > 0 ? 'rgba(244,63,94,0.3)' : 'rgba(16,185,129,0.3)' }}; background: {{ $compliance->expired > 0 ? 'rgba(244,63,94,0.09)' : 'rgba(16,185,129,0.09)' }};">
                        <p class="dashboard-meta-card__label {{ $compliance->expired > 0 ? 'text-red-300' : 'text-green-300' }}">Expired</p>
                        <p class="dashboard-meta-card__value {{ $compliance->expired > 0 ? 'text-red-200' : 'text-green-200' }}">{{ $compliance->expired }}</p>
                    </div>
                    <div class="dashboard-meta-card" style="border-color: {{ $compliance->expiring > 0 ? 'rgba(245,158,11,0.3)' : 'rgba(16,185,129,0.3)' }}; background: {{ $compliance->expiring > 0 ? 'rgba(245,158,11,0.09)' : 'rgba(16,185,129,0.09)' }};">
                        <p class="dashboard-meta-card__label {{ $compliance->expiring > 0 ? 'text-amber-300' : 'text-green-300' }}">Expiring Soon</p>
                        <p class="dashboard-meta-card__value {{ $compliance->expiring > 0 ? 'text-amber-200' : 'text-green-200' }}">{{ $compliance->expiring }}</p>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
