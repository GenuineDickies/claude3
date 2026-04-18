{{--
  Role Access — admin.access.edit
  Preserved features: CSRF, @method('PUT'), search GET form, grouped page
  sections with section styles, Alpine open/toggleAll, page checkboxes
  (page_ids[]), administrator special-case (always granted), save action.
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">
    <div>
        <a href="{{ route('admin.roles.index') }}" class="text-sm text-cyan-400 hover:text-cyan-300">&larr; Back to roles</a>
        <h1 class="mt-2 text-2xl font-bold text-white">Role Access: {{ $role->role_name }}</h1>
        <p class="mt-1 text-sm text-gray-500">Select which registered pages this role can open.</p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-500/30 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="surface-1 p-4">
        <form method="GET" action="{{ route('admin.access.edit', $role) }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="search" class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Search pages</label>
                <input id="search" name="search" value="{{ $search }}" placeholder="Page name or path" class="w-full rounded-md border-white/10 text-sm shadow-sm input-crystal">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Filter</button>
                <a href="{{ route('admin.access.edit', $role) }}" class="rounded-md border border-white/10 px-4 py-2 text-sm font-semibold text-gray-300 hover:bg-white/5">Clear</a>
            </div>
        </form>
    </div>

    @if ($role->isAdministrator())
        <div class="rounded-lg border border-cyan-500/30 bg-cyan-500/10 px-4 py-3 text-sm text-blue-800">
            Administrator is a reserved role and always has access to every registered page.
        </div>
    @endif

    <div class="surface-1 p-6">
        <form method="POST" action="{{ route('admin.access.update', $role) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @php
                $grouped = $pages->groupBy(function ($page) {
                    $segments = explode('/', trim($page->page_path, '/'));
                    return $segments[0] ?? 'other';
                })->sortKeys();

                // Bold, distinct colors using inline styles so they always render.
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

            @forelse ($grouped as $section => $sectionPages)
                @php
                    $sectionLabel = ucwords(str_replace('-', ' ', $section));
                    $checkedCount = $sectionPages->filter(fn ($p) => in_array($p->id, $assignedPageIds, true))->count();
                    $totalCount = $sectionPages->count();
                    $allChecked = $checkedCount === $totalCount;
                    $noneChecked = $checkedCount === 0;
                    $s = $sectionStyles[$section] ?? $defaultStyle;
                @endphp
                <div x-data="{
                        open: true,
                        toggleAll(event) {
                            const checked = event.target.checked;
                            this.$refs.pages_{{ Str::slug($section, '_') }}.querySelectorAll('input[type=checkbox]:not(:disabled)').forEach(cb => cb.checked = checked);
                        }
                    }" class="rounded-lg overflow-hidden shadow-sm" style="border: 2px solid {{ $s['border'] }};">
                    {{-- Section header --}}
                    <div class="flex w-full items-center justify-between px-4 py-3.5"
                        style="background: {{ $s['bg'] }}; border-left: 5px solid {{ $s['color'] }};">
                        <div class="flex items-center gap-3">
                            @unless ($role->isAdministrator())
                                <label class="flex items-center gap-1.5 rounded-md px-2 py-1.5 cursor-pointer hover:bg-white/60 transition-colors" style="border: 2px solid {{ $s['color'] }}; background: rgba(255,255,255,0.5);" title="Select/deselect all {{ $sectionLabel }} pages">
                                    <input type="checkbox"
                                        @change="toggleAll($event)"
                                        @checked($allChecked)
                                        class="h-5 w-5 rounded cursor-pointer"
                                        style="border-color: {{ $s['color'] }}; color: {{ $s['color'] }};">
                                    <span class="text-xs font-bold select-none" style="color: {{ $s['color'] }};">All</span>
                                </label>
                            @endunless
                            <span class="flex h-8 w-8 items-center justify-center rounded-md text-white text-sm font-bold shadow-sm"
                                  style="background: {{ $s['color'] }};">
                                {{ strtoupper(substr($section, 0, 1)) }}
                            </span>
                            <div>
                                <span class="text-sm font-bold" style="color: {{ $s['color'] }};">{{ $sectionLabel }}</span>
                                <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold text-white" style="background: {{ $s['color'] }};">{{ $totalCount }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            @if ($allChecked)
                                <span class="rounded-full bg-green-100 border border-green-300 px-3 py-1 text-xs font-semibold text-green-800">All granted</span>
                            @elseif ($noneChecked)
                                <span class="rounded-full bg-red-100 border border-red-500/30 px-3 py-1 text-xs font-semibold text-red-700">None</span>
                            @else
                                <span class="rounded-full bg-amber-100 border border-amber-300 px-3 py-1 text-xs font-semibold text-amber-700">{{ $checkedCount }}/{{ $totalCount }}</span>
                            @endif
                            <button type="button" @click="open = !open" class="p-1 rounded hover:bg-black/5 transition-colors" title="Expand/collapse">
                                <svg :class="open ? 'rotate-90' : ''" class="h-5 w-5 transition-transform" style="color: {{ $s['color'] }};" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Pages within section --}}
                    <div x-show="open" x-transition x-ref="pages_{{ Str::slug($section, '_') }}" class="surface-0 px-4 py-3" style="border-top: 2px solid {{ $s['border'] }};">
                        <div class="grid gap-2 md:grid-cols-2">
                            @foreach ($sectionPages as $page)
                                @php
                                    $relativePath = '/' . ltrim(substr($page->page_path, strlen('/' . $section)), '/');
                                    if ($relativePath === '/') $relativePath = '(index)';
                                    $isChecked = in_array($page->id, $assignedPageIds, true);
                                @endphp
                                <label class="flex items-start gap-3 rounded-md px-3 py-2.5 cursor-pointer transition-all hover:shadow-sm"
                                    style="{{ $isChecked
                                        ? 'background:' . $s['bg'] . '; border: 1.5px solid ' . $s['border'] . '; border-left: 4px solid ' . $s['color'] . ';'
                                        : 'background: #fff; border: 1.5px solid #e5e7eb; border-left: 4px solid #e5e7eb;' }}">
                                    <input
                                        type="checkbox"
                                        name="page_ids[]"
                                        value="{{ $page->id }}"
                                        @checked($isChecked)
                                        @disabled($role->isAdministrator())
                                        class="mt-0.5 rounded border-white/10 text-cyan-400 focus:ring-cyan-500 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-white truncate" title="{{ $page->page_name }}">{{ $page->page_name }}</span>
                                        <span class="block text-xs font-mono truncate" title="{{ $page->page_path }}"
                                              style="color: {{ $isChecked ? $s['color'] : '#9ca3af' }};">{{ $relativePath }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-white/10 px-4 py-10 text-center text-sm text-gray-500">No pages matched the current filter.</div>
            @endforelse

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('admin.roles.index') }}" class="rounded-md border border-white/10 px-4 py-2 text-sm font-semibold text-gray-300 hover:bg-white/5">Back</a>
                @unless ($role->isAdministrator())
                    <button type="submit" class="btn-crystal px-4 py-2 text-sm font-semibold">Save access</button>
                @endunless
            </div>
        </form>
    </div>
</div>
@endsection
