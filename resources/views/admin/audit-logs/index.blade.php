{{--
  Audit Logs — admin.audit-logs.index
  Controller vars: $auditLogs (paginator), $eventOptions, $currentSearch, $currentEvent
  Features preserved:
    - Filters: Search (user/email/IP/UA), Event dropdown, Filter + Clear buttons
    - Table cols: When, Event, User (name/email or System), IP, Details (JSON pretty-printed)
    - Empty state
    - Pagination
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">
    <div>
        <h1 class="text-2xl font-bold text-white">Audit Logs</h1>
        <p class="mt-1 text-sm text-gray-500">Review sign-in events, access denials, and administration changes.</p>
    </div>

    <div class="surface-1 p-4">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="search" class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Search</label>
                <input id="search" name="search" value="{{ $currentSearch }}" placeholder="User, email, IP, or user agent" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
            </div>
            <div>
                <label for="event" class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Event</label>
                <select id="event" name="event" class="rounded-md border-white/10 text-sm shadow-sm input-crystal">
                    <option value="">All events</option>
                    @foreach ($eventOptions as $eventOption)
                        <option value="{{ $eventOption }}" @selected($currentEvent === $eventOption)>{{ $eventOption }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-crystal px-4 py-2 text-sm font-semibold">Filter</button>
                <a href="{{ route('admin.audit-logs.index') }}" class="rounded-md border border-white/10 px-4 py-2 text-sm font-semibold text-gray-300 hover:bg-white/5">Clear</a>
            </div>
        </form>
    </div>

    <div class="overflow-hidden surface-1">
        @if ($auditLogs->isEmpty())
            <div class="px-6 py-12 text-center text-sm text-gray-500">No audit log entries matched the current filters.</div>
        @else
            <div class="overflow-x-auto">
                <table class="table-crystal min-w-full divide-y divide-white/5">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">When</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Event</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">User</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">IP</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach ($auditLogs as $auditLog)
                            <tr>
                                <td class="px-4 py-4 text-sm text-gray-400 whitespace-nowrap">{{ $auditLog->created_at?->format('M j, Y g:i A') }}</td>
                                <td class="px-4 py-4 text-sm text-white">{{ $auditLog->event }}</td>
                                <td class="px-4 py-4 text-sm text-gray-300">
                                    @if ($auditLog->user)
                                        <div>{{ $auditLog->user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $auditLog->user->email }}</div>
                                    @else
                                        <span class="text-gray-500">System</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-400 font-mono">{{ $auditLog->ip_address ?: '—' }}</td>
                                <td class="px-4 py-4 text-xs text-gray-400">
                                    @if (! empty($auditLog->details))
                                        <pre class="whitespace-pre-wrap break-words">{{ json_encode($auditLog->details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @else
                                        <span class="text-gray-500">No extra details</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($auditLogs->hasPages())
                <div class="border-t border-white/10 px-4 py-3">{{ $auditLogs->links() }}</div>
            @endif
        @endif
    </div>
</div>
@endsection