@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Pages</h1>
            <p class="mt-1 text-sm text-gray-500">Register manual pages, edit labels, and sync the registry from authenticated routes.</p>
        </div>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('admin.pages.sync') }}">
                @csrf
                <button type="submit" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Sync Routes</button>
            </form>
            <button type="button" x-data @click="$dispatch('open-modal', 'create-page')" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Register Page</button>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="rounded-lg bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.pages.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="search" class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Search pages</label>
                <input id="search" name="search" value="{{ $search }}" placeholder="Page name or path" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Filter</button>
                <a href="{{ route('admin.pages.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Clear</a>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Page</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Path</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Assigned Roles</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($pages as $page)
                        <tr>
                            <td class="px-4 py-4 align-top">
                                <div class="font-medium text-gray-900">{{ $page->page_name }}</div>
                                <div class="text-sm text-gray-500">{{ $page->description ?: 'No description provided.' }}</div>
                            </td>
                            <td class="px-4 py-4 align-top text-sm font-mono text-gray-600">{{ $page->page_path }}</td>
                            <td class="px-4 py-4 align-top">
                                <div class="flex flex-wrap gap-2">
                                    @forelse ($page->roles as $role)
                                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">{{ $role->role_name }}</span>
                                    @empty
                                        <span class="text-sm text-gray-400">No roles assigned</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="flex justify-end gap-2">
                                    <button type="button" x-data @click="$dispatch('open-modal', 'edit-page-{{ $page->id }}')" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Edit</button>
                                    <form method="POST" action="{{ route('admin.pages.destroy', $page) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md border border-red-200 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
                                <x-modal name="edit-page-{{ $page->id }}" maxWidth="lg">
                                    <div class="p-6">
                                        <h2 class="text-lg font-semibold text-gray-900">Edit {{ $page->page_name }}</h2>
                                        <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="mt-4 space-y-4">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-gray-700">Page name</label>
                                                <input name="page_name" value="{{ $page->page_name }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-gray-700">Page path</label>
                                                <input name="page_path" value="{{ $page->page_path }}" class="w-full rounded-md border-gray-300 font-mono text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                                                <textarea name="description" rows="3" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ $page->description }}</textarea>
                                            </div>
                                            <div class="flex justify-end gap-3">
                                                <button type="button" @click="$dispatch('close-modal', 'edit-page-{{ $page->id }}')" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                                                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </x-modal>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center text-sm text-gray-500">No pages matched the current filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pages->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">{{ $pages->links() }}</div>
        @endif
    </div>

    <x-modal name="create-page" maxWidth="lg">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900">Register Page</h2>
            <form method="POST" action="{{ route('admin.pages.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Page name</label>
                    <input name="page_name" value="{{ old('page_name') }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Page path</label>
                    <input name="page_path" value="{{ old('page_path') }}" placeholder="/custom-page" class="w-full rounded-md border-gray-300 font-mono text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" rows="3" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="$dispatch('close-modal', 'create-page')" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Register</button>
                </div>
            </form>
        </div>
    </x-modal>
</div>
@endsection