{{--
  Service Catalog — catalog.index
  Controller vars: $categories (with items)
  Features preserved:
    - Add Category and Add Item buttons
    - Stats bar (Categories, Total Items, Active, Products)
    - Flash messages (success, inventory_warnings)
    - Per-category sections with Add item, Edit category, item rows
    - Item details: name, Product/Inactive badges, inventory qty, base_cost, unit, pricing_type, description
    - Edit + Delete (with confirm) per item
    - Empty state
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">

    {{-- Page header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">Service Catalog</h1>
            <p class="text-sm text-gray-500 mt-1">Services and products offered to customers.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('catalog.categories.create') }}"
               class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-300 hover:text-white border border-white/10 rounded-lg hover:bg-white/5 transition-colors">
                <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                Add Category
            </a>
            <a href="{{ route('catalog.items.create') }}"
               class="inline-flex items-center px-4 py-2 btn-crystal text-sm font-semibold rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add Item
            </a>
        </div>
    </div>

    {{-- Stats bar --}}
    @php
        $totalItems     = $categories->sum(fn ($c) => $c->items->count());
        $activeItems    = $categories->sum(fn ($c) => $c->items->where('is_active', true)->count());
        $productCount   = $categories->sum(fn ($c) => $c->items->where('type', 'product')->count());
        $categoryCount  = $categories->count();
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="surface-1 px-4 py-3 rounded-lg">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Categories</p>
            <p class="text-xl font-bold text-white mt-0.5">{{ $categoryCount }}</p>
        </div>
        <div class="surface-1 px-4 py-3 rounded-lg">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Items</p>
            <p class="text-xl font-bold text-white mt-0.5">{{ $totalItems }}</p>
        </div>
        <div class="surface-1 px-4 py-3 rounded-lg">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Active</p>
            <p class="text-xl font-bold text-green-400 mt-0.5">{{ $activeItems }}</p>
        </div>
        <div class="surface-1 px-4 py-3 rounded-lg">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Products</p>
            <p class="text-xl font-bold text-blue-400 mt-0.5">{{ $productCount }}</p>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('inventory_warnings'))
        <div class="bg-amber-500/10 border border-amber-500/30 text-amber-400 px-4 py-3 rounded-lg text-sm">
            ⚠ {{ session('inventory_warnings') }}
        </div>
    @endif

    {{-- Category sections --}}
    @if($categories->isEmpty())
        <div class="surface-1 p-10 text-center rounded-xl">
            <svg class="w-10 h-10 mx-auto text-gray-600 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM4 7V5a2 2 0 012-2h3l2 2h7a2 2 0 012 2v2"/>
            </svg>
            <p class="text-gray-400 font-medium">No catalog content yet.</p>
            <p class="text-sm text-gray-600 mt-1">
                <a href="{{ route('catalog.categories.create') }}" class="text-cyan-400 hover:underline">Create a category</a>
                to get started.
            </p>
        </div>
    @else
        <div class="space-y-6">
            @foreach($categories as $category)
                <div>
                    {{-- Category header --}}
                    <div class="flex items-center justify-between mb-2 px-1">
                        <div class="flex items-center gap-3">
                            @if(!$category->is_active)
                                <span class="text-xs bg-white/5 text-gray-600 border border-white/5 px-1.5 py-0.5 rounded">Inactive</span>
                            @endif
                            <h2 class="text-sm font-semibold text-gray-300 uppercase tracking-wider">{{ $category->name }}</h2>
                            <span class="text-xs bg-white/5 text-gray-500 px-1.5 py-0.5 rounded">
                                {{ $category->items->count() }} {{ Str::plural('item', $category->items->count()) }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('catalog.items.create') }}?category={{ $category->id }}"
                               class="text-xs text-gray-500 hover:text-cyan-400 transition-colors inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add item
                            </a>
                            <span class="text-gray-700">·</span>
                            <a href="{{ route('catalog.categories.edit', $category) }}"
                               class="text-xs text-gray-500 hover:text-cyan-400 transition-colors">Edit category</a>
                        </div>
                    </div>

                    @if($category->items->isEmpty())
                        <div class="surface-1 px-6 py-5 rounded-lg">
                            <p class="text-sm text-gray-600 italic">No items in this category yet. <a href="{{ route('catalog.items.create') }}" class="text-cyan-500 hover:text-cyan-400">Add one</a>.</p>
                        </div>
                    @else
                        <div class="surface-1 rounded-lg divide-y divide-white/5">
                            @foreach($category->items as $item)
                                <div class="flex items-center justify-between gap-4 px-5 py-3.5 @if(!$item->is_active) opacity-50 @endif">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-medium text-white">{{ $item->name }}</span>
                                            @if($item->isProduct())
                                                <span class="text-xs bg-blue-500/10 text-blue-400 border border-blue-500/20 px-1.5 py-0.5 rounded">Product</span>
                                            @endif
                                            @if(!$item->is_active)
                                                <span class="text-xs bg-white/5 text-gray-500 border border-white/10 px-1.5 py-0.5 rounded">Inactive</span>
                                            @endif
                                            @if($item->track_inventory)
                                                <span @class([
                                                    'text-xs px-1.5 py-0.5 rounded border font-medium',
                                                    'bg-amber-500/10 text-amber-400 border-amber-500/20' => $item->qty_available <= 0,
                                                    'bg-green-500/10 text-green-400 border-green-500/20' => $item->qty_available > 0,
                                                ])>
                                                    {{ number_format($item->qty_available, 0) }} avail
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1">
                                            <span class="text-sm font-semibold text-gray-200">${{ number_format($item->base_cost, 2) }}</span>
                                            <span class="text-xs text-gray-500">/ {{ $item->unit }}</span>
                                            <span @class([
                                                'text-xs px-1.5 py-0.5 rounded border font-medium',
                                                'bg-green-500/10 text-green-500 border-green-500/15' => $item->pricing_type === 'fixed',
                                                'bg-purple-500/10 text-purple-400 border-purple-500/15' => $item->pricing_type !== 'fixed',
                                            ])>
                                                {{ ucfirst($item->pricing_type) }}
                                            </span>
                                            @if($item->description)
                                                <span class="text-xs text-gray-500 truncate max-w-xs">{{ $item->description }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <a href="{{ route('catalog.items.edit', $item) }}"
                                           class="p-1.5 text-gray-500 hover:text-cyan-400 rounded hover:bg-white/5 transition-colors" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        <form action="{{ route('catalog.items.destroy', $item) }}" method="POST"
                                              onsubmit="return confirm('Delete {{ addslashes($item->name) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-1.5 text-gray-600 hover:text-red-400 rounded hover:bg-white/5 transition-colors" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
