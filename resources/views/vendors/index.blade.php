@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">Vendors</h1>
        <a href="{{ route('vendors.create') }}"
           class="inline-flex items-center px-4 py-2 btn-crystal text-sm font-semibold rounded-md  transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Vendor
        </a>
    </div>

    {{-- Filters --}}
    <div class="surface-1 p-4">
        <form method="GET" action="{{ route('vendors.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="search" class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" name="search" id="search" value="{{ $currentSearch ?? '' }}"
                       placeholder="Name, contact, email…"
                       class="rounded-md border-white/10 text-sm shadow-xs input-crystal">
            </div>

            <div>
                <label for="active" class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select name="active" id="active"
                        class="rounded-md border-white/10 text-sm shadow-xs input-crystal">
                    <option value="">All</option>
                    <option value="1" {{ ($currentActive ?? '') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ($currentActive ?? '') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <button type="submit"
                    class="inline-flex items-center px-4 py-2 btn-crystal text-sm">
                Filter
            </button>

            @if ($currentSearch || $currentActive !== null && $currentActive !== '')
                <a href="{{ route('vendors.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-300 underline">Clear</a>
            @endif
        </form>
    </div>

    {{-- Results --}}
    <div class="surface-1">
        @if ($vendors->isEmpty())
            <div class="p-6 text-center">
                <p class="text-sm text-gray-500">No vendors found.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table-crystal min-w-full divide-y divide-white/5">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-transparent divide-y divide-white/5">
                        @foreach ($vendors as $vendor)
                            <tr class="hover:bg-white/5">
                                <td class="px-4 py-3 text-sm">
                                    <a href="{{ route('vendors.show', $vendor) }}"
                                       class="text-cyan-400 hover:text-cyan-300 font-medium">{{ $vendor->name }}</a>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-400">
                                    {{ $vendor->contact_name ?: '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-400">
                                    {{ $vendor->phone ?: '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($vendor->is_active)
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Active</span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-white/5 text-gray-500">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <a href="{{ route('vendors.show', $vendor) }}"
                                       class="text-cyan-400 hover:text-cyan-300 text-sm font-medium">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($vendors->hasPages())
                <div class="px-4 py-3 border-t border-white/10">
                    {{ $vendors->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
