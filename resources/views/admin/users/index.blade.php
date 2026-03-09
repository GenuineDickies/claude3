@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">User Access</h1>
            <p class="mt-1 text-sm text-gray-500">Manage accounts, assign multiple roles, and disable access without deleting users.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            Add User
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="rounded-lg bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <div class="flex-1">
                <label for="search" class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Search</label>
                <input id="search" name="search" value="{{ $search }}" placeholder="Name, username, or email" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="w-full lg:w-48">
                <label for="status" class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Status</label>
                <select id="status" name="status" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All</option>
                    <option value="active" @selected($status === 'active')>Active</option>
                    <option value="disabled" @selected($status === 'disabled')>Disabled</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Filter</button>
                <a href="{{ route('admin.users.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Clear</a>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">User</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Roles</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Created</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-4 align-top">
                                <div class="font-medium text-gray-900">{{ $user->name }}</div>
                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                <div class="mt-1 text-xs font-mono text-gray-400">{{ $user->username }}</div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="flex flex-wrap gap-2">
                                    @forelse ($user->roles as $role)
                                        <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 ring-1 ring-blue-700/10">{{ $role->role_name }}</span>
                                    @empty
                                        <span class="text-sm text-gray-400">No roles assigned</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset',
                                    'bg-green-50 text-green-700 ring-green-600/20' => $user->status === 'active',
                                    'bg-red-50 text-red-700 ring-red-600/20' => $user->status === 'disabled',
                                ])>
                                    {{ ucfirst($user->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 align-top text-sm text-gray-500">{{ $user->created_at?->diffForHumans() }}</td>
                            <td class="px-4 py-4 align-top">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Edit</a>
                                    <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}">
                                        @csrf
                                        <button type="submit" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
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
            <div class="border-t border-gray-200 px-4 py-3">{{ $users->links() }}</div>
        @endif
    </div>
</div>
@endsection