{{--
  User Access — admin.users.index
  Controller vars: $users (paginator), $search, $status
  Features preserved:
    - Add User button
    - Success + error flash messages
    - Filters: Search (name/username/email), Status (active/disabled), Filter + Clear buttons
    - Table cols: User (name/email/username), Roles chips, Status badge, Created, Actions (Edit + Disable/Enable POST)
    - Empty state row
    - Pagination
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">User Access</h1>
            <p class="mt-1 text-sm text-gray-500">Manage accounts, assign multiple roles, and disable access without deleting users.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center btn-crystal px-4 py-2 text-sm font-semibold">
            Add User
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-500/30 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="surface-1 p-4">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <div class="flex-1">
                <label for="search" class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Search</label>
                <input id="search" name="search" value="{{ $search }}" placeholder="Name, username, or email" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
            </div>
            <div class="w-full lg:w-48">
                <label for="status" class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Status</label>
                <select id="status" name="status" class="select-crystal w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
                    <option value="">All</option>
                    <option value="active" @selected($status === 'active')>Active</option>
                    <option value="disabled" @selected($status === 'disabled')>Disabled</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Filter</button>
                <a href="{{ route('admin.users.index') }}" class="rounded-md border border-white/10 px-4 py-2 text-sm font-semibold text-gray-300 hover:bg-white/5">Clear</a>
            </div>
        </form>
    </div>

    <div class="overflow-hidden surface-1">
        <div class="overflow-x-auto">
            <table class="table-crystal min-w-full divide-y divide-white/5">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">User</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Roles</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Created</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 bg-transparent">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-4 align-top">
                                <div class="font-medium text-white">{{ $user->name }}</div>
                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                <div class="mt-1 text-xs font-mono text-gray-400">{{ $user->username }}</div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="flex flex-wrap gap-2">
                                    @forelse ($user->roles as $role)
                                        <span class="inline-flex rounded-full bg-cyan-500/10 px-2.5 py-1 text-xs font-medium text-cyan-400 ring-1 ring-blue-700/10">{{ $role->role_name }}</span>
                                    @empty
                                        <span class="text-sm text-gray-400">No roles assigned</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset',
                                    'bg-green-500/10 text-green-700 ring-green-600/20' => $user->status === 'active',
                                    'bg-red-50 text-red-700 ring-red-600/20' => $user->status === 'disabled',
                                ])>
                                    {{ ucfirst($user->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 align-top text-sm text-gray-500">{{ $user->created_at?->diffForHumans() }}</td>
                            <td class="px-4 py-4 align-top">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="rounded-md border border-white/10 px-3 py-1.5 text-sm font-medium text-gray-300 hover:bg-white/5">Edit</a>
                                    <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}">
                                        @csrf
                                        <button type="submit" class="rounded-md border border-white/10 px-3 py-1.5 text-sm font-medium text-gray-300 hover:bg-white/5">
                                            {{ $user->status === 'active' ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-sm text-gray-500">No users matched the current filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="pagination-crystal border-t border-white/10 px-4 py-3">{{ $users->links() }}</div>
        @endif
    </div>
</div>
@endsection