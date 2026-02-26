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
                                <td class="py-2 pr-4 text-gray-600">{{ $sr->serviceType?->name ?? '—' }}</td>
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
</div>
@endsection
