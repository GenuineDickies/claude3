@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Roles</h1>
            <p class="mt-1 text-sm text-gray-500">Create reusable roles and manage their page assignments from a central editor.</p>
        </div>
        <button type="button" x-data @click="$dispatch('open-modal', 'create-role')" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            New Role
        </button>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="rounded-lg bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.roles.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="search" class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Search roles</label>
                <input id="search" name="search" value="{{ $search }}" placeholder="Role name" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Filter</button>
                <a href="{{ route('admin.roles.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Clear</a>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Role</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Users</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Pages</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($roles as $role)
                        <tr>
                            <td class="px-4 py-4">
                                <div class="font-medium text-gray-900">{{ $role->role_name }}</div>
                                <div class="text-sm text-gray-500">{{ $role->description ?: 'No description provided.' }}</div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600">{{ $role->users_count }}</td>
                            <td class="px-4 py-4 text-sm text-gray-600">{{ $role->isAdministrator() ? 'All pages' : $role->pages_count }}</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.access.edit', $role) }}" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Access</a>
                                    <button type="button" x-data @click="$dispatch('open-modal', 'edit-role-{{ $role->id }}')" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Edit</button>
                                    @if (! $role->isAdministrator())
                                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-md border border-red-200 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50">Delete</button>
                                        </form>
                                    @endif
                                </div>
                                <x-modal name="edit-role-{{ $role->id }}" maxWidth="lg">
                                    <div class="p-6">
                                        <h2 class="text-lg font-semibold text-gray-900">Edit {{ $role->role_name }}</h2>
                                        <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="mt-4 space-y-4">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-gray-700">Role name</label>
                                                <input name="role_name" value="{{ $role->role_name }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                                                <textarea name="description" rows="3" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ $role->description }}</textarea>
                                            </div>
                                            <div class="flex justify-end gap-3">
                                                <button type="button" @click="$dispatch('close-modal', 'edit-role-{{ $role->id }}')" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                                                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </x-modal>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center text-sm text-gray-500">No roles matched the current filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($roles->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">{{ $roles->links() }}</div>
        @endif
    </div>

    <x-modal name="create-role" maxWidth="lg">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900">Create Role</h2>
            <form method="POST" action="{{ route('admin.roles.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Role name</label>
                    <input name="role_name" value="{{ old('role_name') }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" rows="3" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="$dispatch('close-modal', 'create-role')" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Create</button>
                </div>
            </form>
        </div>
    </x-modal>
</div>
@endsection