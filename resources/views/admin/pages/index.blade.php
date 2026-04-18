{{--
  Pages — admin.pages.index
  Controller vars: $pages, $search
  Features preserved:
    - Sync Routes (POST) and Register Page (Alpine modal: create-page) buttons
    - Success + error flash messages
    - Search filter + Clear
    - Pages grouped by section with per-section colored headers and collapse toggle (Alpine)
    - Per-row truncate cells with hover tooltip (Alpine), Edit modal, Delete form
    - Edit modal (x-modal) and Register modal (x-modal) with name/path/description fields
    - Empty state
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Pages</h1>
            <p class="mt-1 text-sm text-gray-500">Register manual pages, edit labels, and sync the registry from authenticated routes.</p>
        </div>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('admin.pages.sync') }}">
                @csrf
                <button type="submit" class="rounded-md border border-white/10 px-4 py-2 text-sm font-semibold text-gray-300 hover:bg-white/5">Sync Routes</button>
            </form>
            <button type="button" x-data @click="$dispatch('open-modal', 'create-page')" class="btn-crystal px-4 py-2 text-sm font-semibold">Register Page</button>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-500/30 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="surface-1 p-4">
        <form method="GET" action="{{ route('admin.pages.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="search" class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Search pages</label>
                <input id="search" name="search" value="{{ $search }}" placeholder="Page name or path" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Filter</button>
                <a href="{{ route('admin.pages.index') }}" class="rounded-md border border-white/10 px-4 py-2 text-sm font-semibold text-gray-300 hover:bg-white/5">Clear</a>
            </div>
        </form>
    </div>

    @php
        $grouped = $pages->groupBy(function ($page) {
            $segments = explode('/', trim($page->page_path, '/'));
            return $segments[0] ?? 'other';
        })->sortKeys();

        $sectionStyles = [
            'accounting'          => ['color' => '#7c3aed', 'bg' => '#f5f3ff', 'border' => '#c4b5fd'],
            'api'                 => ['color' => '#475569', 'bg' => '#f8fafc', 'border' => '#94a3b8'],
            'catalog'             => ['color' => '#b45309', 'bg' => '#fffbeb', 'border' => '#fbbf24'],
            'customers'           => ['color' => '#0e7490', 'bg' => '#ecfeff', 'border' => '#22d3ee'],
            'dashboard'           => ['color' => '#1d4ed8', 'bg' => '#eff6ff', 'border' => '#60a5fa'],
            'documents'           => ['color' => '#a16207', 'bg' => '#fefce8', 'border' => '#facc15'],
            'expenses'            => ['color' => '#dc2626', 'bg' => '#fef2f2', 'border' => '#f87171'],
            'inbox'               => ['color' => '#0284c7', 'bg' => '#f0f9ff', 'border' => '#38bdf8'],
            'message-templates'   => ['color' => '#4338ca', 'bg' => '#eef2ff', 'border' => '#818cf8'],
            'profile'             => ['color' => '#334155', 'bg' => '#f8fafc', 'border' => '#64748b'],
            'rapid-dispatch'      => ['color' => '#c2410c', 'bg' => '#fff7ed', 'border' => '#fb923c'],
            'reports'             => ['color' => '#0f766e', 'bg' => '#f0fdfa', 'border' => '#2dd4bf'],
            'service-requests'    => ['color' => '#059669', 'bg' => '#ecfdf5', 'border' => '#34d399'],
            'settings'            => ['color' => '#3f3f46', 'bg' => '#fafafa', 'border' => '#a1a1aa'],
            'technician-profiles' => ['color' => '#4d7c0f', 'bg' => '#f7fee7', 'border' => '#a3e635'],
            'transaction-imports' => ['color' => '#a21caf', 'bg' => '#fdf4ff', 'border' => '#e879f9'],
            'vendor-documents'    => ['color' => '#e11d48', 'bg' => '#fff1f2', 'border' => '#fb7185'],
            'vendors'             => ['color' => '#be185d', 'bg' => '#fdf2f8', 'border' => '#f472b6'],
            'warranties'          => ['color' => '#6d28d9', 'bg' => '#f5f3ff', 'border' => '#a78bfa'],
        ];
        $defaultStyle = ['color' => '#6b7280', 'bg' => '#f9fafb', 'border' => '#d1d5db'];
    @endphp

    <div class="space-y-6">
        @forelse ($grouped as $section => $sectionPages)
            @php
                $sectionLabelOverrides = [
                    'inbox' => 'Document Intake',
                ];
                $sectionLabel = $sectionLabelOverrides[$section] ?? ucwords(str_replace('-', ' ', $section));
                $s = $sectionStyles[$section] ?? $defaultStyle;
            @endphp
            <div x-data="{ open: true }" class="rounded-lg overflow-hidden shadow-sm" style="border: 2px solid {{ $s['border'] }};">
                {{-- Section header --}}
                <div class="flex w-full items-center justify-between px-4 py-3.5"
                    style="background: {{ $s['bg'] }}; border-left: 5px solid {{ $s['color'] }};">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-md text-white text-sm font-bold shadow-sm"
                              style="background: {{ $s['color'] }};">
                            {{ strtoupper(substr($section, 0, 1)) }}
                        </span>
                        <div>
                            <span class="text-sm font-bold" style="color: {{ $s['color'] }};">{{ $sectionLabel }}</span>
                            <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold text-white" style="background: {{ $s['color'] }};">{{ $sectionPages->count() }}</span>
                        </div>
                    </div>
                    <button type="button" @click="open = !open" class="p-1 rounded hover:bg-black/5 transition-colors" title="Expand/collapse">
                        <svg :class="open ? 'rotate-90' : ''" class="h-5 w-5 transition-transform" style="color: {{ $s['color'] }};" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>

                {{-- Pages within section --}}
                <div x-show="open" x-transition class="surface-0" style="border-top: 2px solid {{ $s['border'] }};">
                    <table class="table-crystal min-w-full divide-y divide-gray-100" style="table-layout: fixed; width: 100%;">
                        <colgroup>
                            <col style="width: 30%;">
                            <col style="width: 30%;">
                            <col style="width: 25%;">
                            <col style="width: 15%;">
                        </colgroup>
                        <thead>
                            <tr style="background: {{ $s['bg'] }};">
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide" style="color: {{ $s['color'] }};">Page</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide" style="color: {{ $s['color'] }};">Path</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide" style="color: {{ $s['color'] }};">Roles</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide" style="color: {{ $s['color'] }};">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($sectionPages as $page)
                                <tr class="hover:bg-white/5/50">
                                    <td class="px-4 py-3 align-top" style="max-width: 0;"
                                        x-data="{ show: false, x: 0, y: 0 }"
                                        @mouseenter="let r=$el.getBoundingClientRect(); x=r.left; y=r.top; show=true"
                                        @mouseleave="show=false">
                                        <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <span class="font-medium text-white text-sm">{{ $page->page_name }}</span>
                                        </div>
                                        @if ($page->description)
                                            <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.75rem; color: #9ca3af; margin-top: 0.125rem;">{{ $page->description }}</div>
                                        @endif
                                        <div x-show="show" x-cloak
                                            :style="'position:fixed;left:'+x+'px;top:'+y+'px;z-index:9999;white-space:nowrap;background:#fff;padding:0.5rem 0.75rem;border-radius:0.375rem;box-shadow:0 4px 12px rgba(0,0,0,0.15);border:1px solid #e5e7eb;user-select:all;'">
                                            <div style="font-weight: 500; font-size: 0.875rem; color: #111827;">{{ $page->page_name }}</div>
                                            @if ($page->description)
                                                <div style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.125rem;">{{ $page->description }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-top" style="max-width: 0;"
                                        x-data="{ show: false, x: 0, y: 0 }"
                                        @mouseenter="let r=$el.getBoundingClientRect(); x=r.left; y=r.top; show=true"
                                        @mouseleave="show=false">
                                        <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <span class="text-xs font-mono" style="color: {{ $s['color'] }};">{{ $page->page_path }}</span>
                                        </div>
                                        <div x-show="show" x-cloak
                                            :style="'position:fixed;left:'+x+'px;top:'+y+'px;z-index:9999;white-space:nowrap;background:#fff;padding:0.5rem 0.75rem;border-radius:0.375rem;box-shadow:0 4px 12px rgba(0,0,0,0.15);border:1px solid #e5e7eb;user-select:all;'">
                                            <span style="font-size: 0.75rem; font-family: monospace; color: {{ $s['color'] }};">{{ $page->page_path }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-top" style="max-width: 0;"
                                        x-data="{ show: false, x: 0, y: 0 }"
                                        @mouseenter="let r=$el.getBoundingClientRect(); x=r.left; y=r.top; show=true"
                                        @mouseleave="show=false">
                                        <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" style="background: {{ $s['bg'] }}; color: {{ $s['color'] }}; border: 1px solid {{ $s['border'] }};">Administrator</span>
                                            @foreach ($page->roles->reject(fn ($r) => $r->isAdministrator()) as $role)
                                                <span class="inline-flex rounded-full bg-white/5 px-2 py-0.5 text-xs font-medium text-gray-300">{{ $role->role_name }}</span>
                                            @endforeach
                                        </div>
                                        <div x-show="show" x-cloak
                                            :style="'position:fixed;left:'+x+'px;top:'+y+'px;z-index:9999;white-space:nowrap;background:#fff;padding:0.5rem 0.75rem;border-radius:0.375rem;box-shadow:0 4px 12px rgba(0,0,0,0.15);border:1px solid #e5e7eb;'">
                                            <div style="display: flex; flex-wrap: wrap; gap: 0.375rem;">
                                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" style="background: {{ $s['bg'] }}; color: {{ $s['color'] }}; border: 1px solid {{ $s['border'] }};">Administrator</span>
                                                @foreach ($page->roles->reject(fn ($r) => $r->isAdministrator()) as $role)
                                                    <span class="inline-flex rounded-full bg-white/5 px-2 py-0.5 text-xs font-medium text-gray-300">{{ $role->role_name }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-top" style="white-space: nowrap;">
                                        <div class="flex justify-end gap-2">
                                            <button type="button" x-data @click="$dispatch('open-modal', 'edit-page-{{ $page->id }}')" class="rounded-md border border-white/10 px-3 py-1.5 text-xs font-medium text-gray-300 hover:bg-white/5">Edit</button>
                                            <form method="POST" action="{{ route('admin.pages.destroy', $page) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-md border border-red-500/30 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-500/10">Delete</button>
                                            </form>
                                        </div>
                                        <x-modal name="edit-page-{{ $page->id }}" maxWidth="lg">
                                            <div class="p-6">
                                                <h2 class="text-lg font-semibold text-white">Edit {{ $page->page_name }}</h2>
                                                <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="mt-4 space-y-4">
                                                    @csrf
                                                    @method('PUT')
                                                    <div>
                                                        <label class="mb-1 block text-sm font-medium text-gray-300">Page name</label>
                                                        <input name="page_name" value="{{ $page->page_name }}" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal" required>
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-sm font-medium text-gray-300">Page path</label>
                                                        <input name="page_path" value="{{ $page->page_path }}" class="w-full rounded-md border-white/10 font-mono text-sm shadow-sm input-crystal" required>
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-sm font-medium text-gray-300">Description</label>
                                                        <textarea name="description" rows="3" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">{{ $page->description }}</textarea>
                                                    </div>
                                                    <div class="flex justify-end gap-3">
                                                        <button type="button" @click="$dispatch('close-modal', 'edit-page-{{ $page->id }}')" class="rounded-md border border-white/10 px-4 py-2 text-sm font-semibold text-gray-300 hover:bg-white/5">Cancel</button>
                                                        <button type="submit" class="btn-crystal px-4 py-2 text-sm font-semibold">Save</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </x-modal>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-dashed border-white/10 px-4 py-10 text-center text-sm text-gray-500">No pages matched the current filter.</div>
        @endforelse
    </div>

    <x-modal name="create-page" maxWidth="lg">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-white">Register Page</h2>
            <form method="POST" action="{{ route('admin.pages.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300">Page name</label>
                    <input name="page_name" value="{{ old('page_name') }}" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300">Page path</label>
                    <input name="page_path" value="{{ old('page_path') }}" placeholder="/custom-page" class="w-full rounded-md border-white/10 font-mono text-sm shadow-sm input-crystal" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-300">Description</label>
                    <textarea name="description" rows="3" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">{{ old('description') }}</textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="$dispatch('close-modal', 'create-page')" class="rounded-md border border-white/10 px-4 py-2 text-sm font-semibold text-gray-300 hover:bg-white/5">Cancel</button>
                    <button type="submit" class="btn-crystal px-4 py-2 text-sm font-semibold">Register</button>
                </div>
            </form>
        </div>
    </x-modal>
</div>
@endsection
