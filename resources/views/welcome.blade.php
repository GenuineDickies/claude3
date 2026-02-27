@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- Hero --}}
    <div class="bg-white rounded-lg shadow-xs p-8 mb-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $companyName }}</h1>
        <p class="text-lg text-gray-600 mb-6">{{ $companyTagline }}</p>
        <a href="{{ route('service-requests.create') }}"
           class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            New Dispatch Ticket
        </a>
    </div>

    {{-- Quick stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-xs p-6 text-center">
            <p class="text-3xl font-bold text-blue-600">{{ $open }}</p>
            <p class="text-sm text-gray-500 mt-1">New Tickets</p>
        </div>
        <div class="bg-white rounded-lg shadow-xs p-6 text-center">
            <p class="text-3xl font-bold text-blue-600">{{ $today }}</p>
            <p class="text-sm text-gray-500 mt-1">Created Today</p>
        </div>
        <div class="bg-white rounded-lg shadow-xs p-6 text-center">
            <p class="text-3xl font-bold text-blue-600">{{ $customers }}</p>
            <p class="text-sm text-gray-500 mt-1">Active Customers</p>
        </div>
    </div>

    {{-- Recent tickets --}}
    <div class="bg-white rounded-lg shadow-xs p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Tickets</h2>

        @if($recent->isEmpty())
            <p class="text-gray-500 text-sm">No dispatch tickets yet. Create one to get started.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500">
                            <th class="pb-2 pr-4">ID</th>
                            <th class="pb-2 pr-4">Customer</th>
                            <th class="pb-2 pr-4">Service</th>
                            <th class="pb-2 pr-4">Location</th>
                            <th class="pb-2 pr-4">Status</th>
                            <th class="pb-2">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recent as $sr)
                            <tr class="border-b last:border-0">
                                <td class="py-2 pr-4 font-mono text-gray-700">#{{ $sr->id }}</td>
                                <td class="py-2 pr-4">{{ $sr->customer?->first_name }} {{ $sr->customer?->last_name }}</td>
                                <td class="py-2 pr-4 text-gray-600">{{ $sr->catalogItem?->name ?? '—' }}</td>
                                <td class="py-2 pr-4 text-gray-600">{{ \Illuminate\Support\Str::limit($sr->location, 30) }}</td>
                                <td class="py-2 pr-4">
                                    <x-status-badge :status="$sr->status" />
                                </td>
                                <td class="py-2 text-gray-500">{{ $sr->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- API monitor health --}}
    <div class="bg-white rounded-lg shadow-xs p-6 mt-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">API Health</h2>
            <a href="{{ route('settings.api-monitor.index') }}" class="text-sm text-blue-600 hover:text-blue-800">Manage</a>
        </div>

        @if(($apiHealth?->total ?? 0) === 0)
            <p class="text-sm text-gray-500">No active monitored endpoints yet.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div class="rounded-lg bg-gray-50 p-3">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Active Endpoints</p>
                    <p class="text-xl font-semibold text-gray-800">{{ (int) $apiHealth->total }}</p>
                </div>
                <div class="rounded-lg bg-green-50 p-3">
                    <p class="text-xs text-green-600 uppercase tracking-wide">Healthy</p>
                    <p class="text-xl font-semibold text-green-700">{{ (int) $apiHealth->healthy }}</p>
                </div>
                <div class="rounded-lg bg-amber-50 p-3">
                    <p class="text-xs text-amber-600 uppercase tracking-wide">Degraded</p>
                    <p class="text-xl font-semibold text-amber-700">{{ (int) $apiHealth->degraded }}</p>
                </div>
                <div class="rounded-lg bg-red-50 p-3">
                    <p class="text-xs text-red-600 uppercase tracking-wide">Down</p>
                    <p class="text-xl font-semibold text-red-700">{{ (int) $apiHealth->down }}</p>
                </div>
            </div>
        @endif
    </div>

    {{-- Technician compliance (optional) --}}
    @if($complianceEnabled && $compliance)
        <div class="bg-white rounded-lg shadow-xs p-6 mt-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Technician Compliance</h2>
                <a href="{{ route('technician-profiles.index') }}" class="text-sm text-blue-600 hover:text-blue-800">Manage</a>
            </div>

            @if($compliance->total === 0)
                <p class="text-sm text-gray-500">No technician profiles created yet.</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="rounded-lg bg-gray-50 p-3">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Profiles</p>
                        <p class="text-xl font-semibold text-gray-800">{{ $compliance->total }}</p>
                    </div>
                    <div class="rounded-lg {{ $compliance->expired > 0 ? 'bg-red-50' : 'bg-green-50' }} p-3">
                        <p class="text-xs {{ $compliance->expired > 0 ? 'text-red-600' : 'text-green-600' }} uppercase tracking-wide">Expired</p>
                        <p class="text-xl font-semibold {{ $compliance->expired > 0 ? 'text-red-700' : 'text-green-700' }}">{{ $compliance->expired }}</p>
                    </div>
                    <div class="rounded-lg {{ $compliance->expiring > 0 ? 'bg-amber-50' : 'bg-green-50' }} p-3">
                        <p class="text-xs {{ $compliance->expiring > 0 ? 'text-amber-600' : 'text-green-600' }} uppercase tracking-wide">Expiring Soon</p>
                        <p class="text-xl font-semibold {{ $compliance->expiring > 0 ? 'text-amber-700' : 'text-green-700' }}">{{ $compliance->expiring }}</p>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
