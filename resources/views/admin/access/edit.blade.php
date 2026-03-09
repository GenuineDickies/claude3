@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div>
        <a href="{{ route('admin.roles.index') }}" class="text-sm text-blue-600 hover:text-blue-700">&larr; Back to roles</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">Role Access: {{ $role->role_name }}</h1>
        <p class="mt-1 text-sm text-gray-500">Select which registered pages this role can open. Access checks are enforced centrally for every protected page.</p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="rounded-lg bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.access.edit', $role) }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="search" class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Search pages</label>
                <input id="search" name="search" value="{{ $search }}" placeholder="Page name or path" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Filter</button>
                <a href="{{ route('admin.access.edit', $role) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Clear</a>
            </div>
        </form>
    </div>

    @if ($role->isAdministrator())
        <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
            Administrator is a reserved role and always has access to every registered page.
        </div>
    @endif

    <div class="rounded-lg bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.access.update', $role) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($pages as $page)
                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4 hover:border-blue-300 hover:bg-blue-50/40">
                        <input
                            type="checkbox"
                            name="page_ids[]"
                            value="{{ $page->id }}"
                            @checked(in_array($page->id, $assignedPageIds, true))
                            @disabled($role->isAdministrator())
                            class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                        <span>
                            <span class="block text-sm font-medium text-gray-900">{{ $page->page_name }}</span>
                            <span class="mt-1 block text-xs font-mono text-gray-500">{{ $page->page_path }}</span>
                            <span class="mt-2 block text-xs text-gray-500">{{ $page->description ?: 'No description provided.' }}</span>
                        </span>
                    </label>
                @empty
                    <div class="col-span-full rounded-lg border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-500">No pages matched the current filter.</div>
                @endforelse
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.roles.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Back</a>
                @unless ($role->isAdministrator())
                    <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Save access</button>
                @endunless
            </div>
        </form>
    </div>
</div>
@endsection