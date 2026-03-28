@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-white">Inbound Queue</h1>
            <p class="text-sm text-gray-500">Track inbound customer requests before they are converted to dispatch tickets.</p>
        </div>
        <a href="{{ route('leads.create') }}" class="inline-flex items-center px-4 py-2 btn-crystal text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Inbound Request
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="surface-1 p-4">
        <form method="GET" action="{{ route('leads.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-55">
                <label for="search" class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" name="search" id="search" value="{{ $currentSearch }}" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal" placeholder="Name, phone, email, service">
            </div>
            <div>
                <label for="stage" class="block text-xs font-medium text-gray-500 mb-1">Stage</label>
                <select name="stage" id="stage" class="rounded-md border-white/10 text-sm shadow-sm input-crystal">
                    <option value="">All</option>
                    @foreach ($stageOptions as $stageKey => $stageLabel)
                        <option value="{{ $stageKey }}" @selected($currentStage === $stageKey)>{{ $stageLabel }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex items-center px-4 py-2 btn-crystal text-sm">Filter</button>
            @if ($currentSearch !== '' || $currentStage !== '')
                <a href="{{ route('leads.index') }}" class="text-sm text-gray-500 hover:text-gray-300 underline">Clear</a>
            @endif
        </form>
    </div>

    <div class="surface-1">
        @if ($leads->isEmpty())
            <div class="p-6 text-center text-sm text-gray-500">No leads found.</div>
        @else
            <div class="overflow-x-auto">
                <table class="table-crystal min-w-full divide-y divide-white/5">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lead</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stage</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Converted</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach ($leads as $lead)
                            <tr class="hover:bg-white/5">
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-medium text-white">{{ $lead->first_name }} {{ $lead->last_name }}</div>
                                    <div class="text-gray-400 font-mono">{{ $lead->phone }}</div>
                                    @if ($lead->service_needed)
                                        <div class="text-xs text-gray-500 mt-1">{{ $lead->service_needed }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-300">{{ $lead->stageLabel() }}</td>
                                <td class="px-4 py-3 text-sm text-gray-400">{{ str_replace('_', ' ', $lead->source) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-400">{{ $lead->assignedUser?->name ?? 'Unassigned' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-400">{{ $lead->converted_at ? $lead->converted_at->diffForHumans() : 'No' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('leads.show', $lead) }}" class="text-gray-400 hover:text-cyan-400" title="Open lead">
                                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($leads->hasPages())
                <div class="px-4 py-3 border-t border-white/10">{{ $leads->withQueryString()->links() }}</div>
            @endif
        @endif
    </div>
</div>
@endsection
