@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('catalog.index') }}" class="text-sm text-cyan-400 hover:text-cyan-300">&larr; Back to Catalog</a>
            <h1 class="text-2xl font-bold text-white mt-2">{{ $category->name }}</h1>
            <div class="flex items-center gap-2 mt-1">
                @if(!$category->is_active)
                    <span class="text-xs bg-white/5 text-gray-500 px-1.5 py-0.5 rounded-sm">Inactive</span>
                @endif
                @if($category->description)
                    <span class="text-sm text-gray-500">{{ $category->description }}</span>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('catalog.categories.edit', $category) }}"
               class="px-4 py-2 text-sm text-gray-400 hover:text-white border border-white/10 rounded-lg hover:bg-white/5 transition-colors">
                Edit Category
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

    @if(session('success'))
        <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg mb-6 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($category->items->isEmpty())
        <div class="surface-1 p-8 text-center">
            <p class="text-gray-500">No services in this category yet.</p>
        </div>
    @else
        <div class="surface-1 divide-y divide-white/5">
            @foreach($category->items as $item)
                <div class="flex items-center justify-between gap-4 px-6 py-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-white">{{ $item->name }}</span>
                            @if(!$item->is_active)
                                <span class="text-xs bg-white/5 text-gray-500 px-1.5 py-0.5 rounded-sm">Inactive</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-sm font-semibold text-gray-300">${{ number_format($item->base_cost, 2) }}</span>
                            <span class="text-xs text-gray-500">/ {{ $item->unit }}</span>
                            <span @class([
                                'text-xs px-1.5 py-0.5 rounded-sm font-medium',
                                'bg-green-500/10 text-green-500 border border-green-500/15' => $item->pricing_type === 'fixed',
                                'bg-purple-500/10 text-purple-400 border border-purple-500/15' => $item->pricing_type === 'variable',
                            ])>
                                {{ ucfirst($item->pricing_type) }}
                            </span>
                        </div>
                        @if($item->description)
                            <p class="text-xs text-gray-500 mt-1 truncate">{{ $item->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('catalog.items.edit', $item) }}"
                           class="text-sm text-gray-400 hover:text-cyan-400" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
