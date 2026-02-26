@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Service Types</h1>
    </div>

    {{-- Success banner --}}
    @if (session('success'))
        <div class="rounded-md bg-green-50 p-4">
            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Add new service type --}}
    <div class="bg-white rounded-lg shadow-sm p-4">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">Add Service Type</h2>
        <form method="POST" action="{{ route('service-types.store') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="flex-1 min-w-[180px]">
                <label for="name" class="block text-xs font-medium text-gray-500 mb-1">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       placeholder="e.g. Flat Tire Change"
                       class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="w-36">
                <label for="default_price" class="block text-xs font-medium text-gray-500 mb-1">Default Price ($)</label>
                <input type="number" name="default_price" id="default_price" value="{{ old('default_price') }}" required
                       step="0.01" min="0" placeholder="75.00"
                       class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('default_price')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add
            </button>
        </form>
    </div>

    {{-- Service types list (drag-and-drop) --}}
    <div class="bg-white rounded-lg shadow-sm" x-data="serviceTypeReorder()">
        @if ($serviceTypes->isEmpty())
            <div class="p-6 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.049.58.025 1.193-.14 1.743"/>
                </svg>
                <p class="mt-2 text-sm text-gray-500">No service types yet. Add one above.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-10 px-2 py-3"></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Default Price</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200"
                           x-ref="sortable">
                        @foreach ($serviceTypes as $type)
                            <tr class="hover:bg-gray-50 group" data-id="{{ $type->id }}"
                                x-data="{ editing: false }">
                                {{-- Drag handle --}}
                                <td class="px-2 py-3 text-center cursor-grab active:cursor-grabbing drag-handle">
                                    <svg class="w-5 h-5 text-gray-400 mx-auto" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                                    </svg>
                                </td>

                                {{-- Name --}}
                                <td class="px-4 py-3 text-sm">
                                    <template x-if="!editing">
                                        <span class="font-medium text-gray-900" x-on:dblclick="editing = true">{{ $type->name }}</span>
                                    </template>
                                    <template x-if="editing">
                                        <form method="POST" action="{{ route('service-types.update', $type) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="name" value="{{ $type->name }}" required
                                                   class="rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 w-full"
                                                   x-init="$nextTick(() => $el.focus())">
                                            <input type="hidden" name="default_price" value="{{ $type->default_price }}">
                                            <input type="hidden" name="is_active" value="{{ $type->is_active ? '1' : '0' }}">
                                            <button type="submit" class="text-blue-600 hover:text-blue-800">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                            </button>
                                            <button type="button" x-on:click="editing = false" class="text-gray-400 hover:text-gray-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                            </button>
                                        </form>
                                    </template>
                                </td>

                                {{-- Price --}}
                                <td class="px-4 py-3 text-sm" x-data="{ editingPrice: false }">
                                    <template x-if="!editingPrice">
                                        <span class="text-gray-600 font-mono" x-on:dblclick="editingPrice = true">${{ number_format($type->default_price, 2) }}</span>
                                    </template>
                                    <template x-if="editingPrice">
                                        <form method="POST" action="{{ route('service-types.update', $type) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="name" value="{{ $type->name }}">
                                            <input type="hidden" name="is_active" value="{{ $type->is_active ? '1' : '0' }}">
                                            <div class="flex items-center">
                                                <span class="text-gray-500 text-sm mr-1">$</span>
                                                <input type="number" name="default_price" value="{{ $type->default_price }}" required
                                                       step="0.01" min="0"
                                                       class="w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                       x-init="$nextTick(() => $el.focus())">
                                            </div>
                                            <button type="submit" class="text-blue-600 hover:text-blue-800">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                            </button>
                                            <button type="button" x-on:click="editingPrice = false" class="text-gray-400 hover:text-gray-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                            </button>
                                        </form>
                                    </template>
                                </td>

                                {{-- Active toggle --}}
                                <td class="px-4 py-3 text-sm">
                                    <form method="POST" action="{{ route('service-types.toggle', $type) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" title="{{ $type->is_active ? 'Click to deactivate' : 'Click to activate' }}">
                                            @if ($type->is_active)
                                                <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-green-600/20 ring-inset hover:bg-green-100 transition-colors">
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-gray-500/10 ring-inset hover:bg-gray-100 transition-colors">
                                                    Inactive
                                                </span>
                                            @endif
                                        </button>
                                    </form>
                                </td>

                                {{-- Edit hint --}}
                                <td class="px-4 py-3 text-xs text-gray-400 text-right whitespace-nowrap">
                                    <span class="opacity-0 group-hover:opacity-100 transition-opacity">double-click to edit</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-gray-200 text-xs text-gray-400 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
                Drag rows to reorder &middot; Double-click name or price to edit inline
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
    function serviceTypeReorder() {
        return {
            init() {
                const tbody = this.$refs.sortable;
                if (!tbody) return;

                new Sortable(tbody, {
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'bg-blue-50',
                    onEnd: () => {
                        const ids = [...tbody.querySelectorAll('tr[data-id]')]
                            .map(row => parseInt(row.dataset.id));

                        fetch('{{ route("service-types.reorder") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ ids }),
                        });
                    },
                });
            },
        };
    }
</script>
@endpush
@endsection
