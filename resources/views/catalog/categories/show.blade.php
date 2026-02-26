@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('catalog.index') }}" class="text-sm text-blue-600 hover:text-blue-700">&larr; Back to Catalog</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ $category->name }}</h1>
            <div class="flex items-center gap-2 mt-1">
                <span @class([
                    'inline-block px-2 py-0.5 text-xs font-medium rounded-full',
                    'bg-blue-100 text-blue-700' => $category->type === 'service',
                    'bg-amber-100 text-amber-700' => $category->type === 'part',
                ])>
                    {{ ucfirst($category->type) }}
                </span>
                @if(!$category->is_active)
                    <span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-sm">Inactive</span>
                @endif
                @if($category->description)
                    <span class="text-sm text-gray-500">— {{ $category->description }}</span>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('catalog.categories.edit', $category) }}"
               class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Edit Category
            </a>
            <a href="{{ route('catalog.items.create', $category) }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add Item
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($category->items->isEmpty())
        <div class="bg-white rounded-lg shadow-xs p-8 text-center">
            <p class="text-gray-500">No items in this category yet.</p>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-xs divide-y divide-gray-100">
            @foreach($category->items as $item)
                <div class="flex items-center justify-between gap-4 px-6 py-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-900">{{ $item->name }}</span>
                            @if($item->sku)
                                <span class="text-xs text-gray-400 font-mono">{{ $item->sku }}</span>
                            @endif
                            @if(!$item->is_active)
                                <span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-sm">Inactive</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-sm font-semibold text-gray-700">${{ number_format($item->unit_price, 2) }}</span>
                            <span class="text-xs text-gray-500">/ {{ $item->unit }}</span>
                            <span @class([
                                'text-xs px-1.5 py-0.5 rounded-sm font-medium',
                                'bg-green-50 text-green-700' => $item->pricing_type === 'fixed',
                                'bg-purple-50 text-purple-700' => $item->pricing_type === 'variable',
                            ])>
                                {{ ucfirst($item->pricing_type) }}
                            </span>
                        </div>
                        @if($item->description)
                            <p class="text-xs text-gray-500 mt-1 truncate">{{ $item->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('catalog.items.edit', [$category, $item]) }}"
                           class="text-sm text-gray-400 hover:text-blue-600" title="Edit">
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
