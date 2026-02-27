@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto">

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="{{ route('settings.edit') }}" class="hover:text-blue-600">Settings</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-700 font-medium">API Monitoring</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">API Health Monitoring</h1>
        <p class="text-sm text-gray-500 mt-1">Track health checks for external services like Telnyx and Google Maps. Run checks on demand and tune intervals.</p>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-xs mb-6 p-5">
        <h2 class="text-sm font-semibold text-gray-800 mb-3">Add Endpoint</h2>
        <form action="{{ route('settings.api-monitor.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-3">
            @csrf
            <input type="text" name="name" placeholder="Endpoint name" class="md:col-span-2 border border-gray-300 rounded-md px-3 py-2 text-sm" required>
            <input type="url" name="url" placeholder="https://api.example.com/health" class="md:col-span-3 border border-gray-300 rounded-md px-3 py-2 text-sm" required>
            <select name="method" class="border border-gray-300 rounded-md px-3 py-2 text-sm" required>
                @foreach(['GET','POST','PUT','PATCH','DELETE'] as $m)
                    <option value="{{ $m }}">{{ $m }}</option>
                @endforeach
            </select>
            <input type="number" name="expected_status_code" min="100" max="599" placeholder="Expected (opt)" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
            <input type="number" name="check_interval_minutes" min="1" max="1440" value="5" class="border border-gray-300 rounded-md px-3 py-2 text-sm" required>
            <label class="inline-flex items-center text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-blue-600 mr-2">
                Active
            </label>
            <div class="md:col-span-6">
                <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-blue-700">Add Endpoint</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-xs overflow-hidden mb-8">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Endpoint</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Last Check</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Config</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($endpoints as $endpoint)
                    <tr>
                        <td class="px-4 py-3 align-top">
                            <div class="font-medium text-gray-900">{{ $endpoint->name }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">{{ $endpoint->method }} {{ $endpoint->url }}</div>
                            <div class="text-xs text-gray-400 mt-1">Failures: {{ $endpoint->consecutive_failures }}</div>
                        </td>
                        <td class="px-4 py-3 align-top">
                            @php
                                $status = $endpoint->last_status ?? 'unknown';
                                $badgeClass = match($status) {
                                    'healthy' => 'bg-green-100 text-green-700',
                                    'degraded' => 'bg-amber-100 text-amber-700',
                                    'down' => 'bg-red-100 text-red-700',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <span class="inline-flex px-2 py-1 rounded-md text-xs font-semibold {{ $badgeClass }}">{{ ucfirst($status) }}</span>
                            @if($endpoint->last_error)
                                <div class="text-xs text-red-600 mt-1">{{ $endpoint->last_error }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 align-top text-xs text-gray-600">
                            @if($endpoint->last_checked_at)
                                {{ $endpoint->last_checked_at->diffForHumans() }}
                                <div class="text-gray-400">{{ $endpoint->last_checked_at->format('Y-m-d H:i:s') }}</div>
                                <div class="text-gray-500">{{ $endpoint->last_response_time_ms ?? 'n/a' }} ms</div>
                            @else
                                Never
                            @endif
                        </td>
                        <td class="px-4 py-3 align-top">
                            <form action="{{ route('settings.api-monitor.update', $endpoint) }}" method="POST" class="space-y-2">
                                @csrf
                                @method('PUT')
                                <input type="number" name="expected_status_code" min="100" max="599" value="{{ $endpoint->expected_status_code }}" placeholder="Expected" class="w-24 border border-gray-300 rounded-md px-2 py-1 text-xs">
                                <input type="number" name="check_interval_minutes" min="1" max="1440" value="{{ $endpoint->check_interval_minutes }}" class="w-20 border border-gray-300 rounded-md px-2 py-1 text-xs">
                                <label class="inline-flex items-center text-xs text-gray-700">
                                    <input type="checkbox" name="is_active" value="1" {{ $endpoint->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 mr-1">
                                    Active
                                </label>
                                <div>
                                    <button type="submit" class="text-xs bg-gray-800 text-white px-2 py-1 rounded hover:bg-gray-900">Save</button>
                                </div>
                            </form>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <form action="{{ route('settings.api-monitor.run', $endpoint) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700">Run now</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No endpoints configured yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
