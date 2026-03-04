@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Service Catalog</h1>
            <p class="text-sm text-gray-500 mt-1">Manage service categories and the individual services you offer.</p>
        </div>
        <a href="{{ route('catalog.categories.create') }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            New Service Category
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($categories->isEmpty())
        <div class="bg-white rounded-lg shadow-xs p-8 text-center">
            <p class="text-gray-500">No service categories yet. Create one to get started.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($categories as $category)
                <div class="bg-white rounded-lg shadow-xs p-5 flex flex-col">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <a href="{{ route('catalog.categories.show', $category) }}"
                               class="text-base font-semibold text-gray-900 hover:text-blue-600">
                                {{ $category->name }}
                            </a>
                        </div>
                        @if(!$category->is_active)
                            <span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-sm">Inactive</span>
                        @endif
                    </div>

                    @if($category->description)
                        <p class="text-sm text-gray-500 mb-3">{{ $category->description }}</p>
                    @endif

                    <div class="mt-auto flex items-center justify-between pt-3 border-t border-gray-100">
                        <span class="text-sm text-gray-500">{{ $category->items_count }} {{ Str::plural('service', $category->items_count) }}</span>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('catalog.categories.show', $category) }}"
                               class="text-sm text-blue-600 hover:text-blue-700">View</a>
                            <a href="{{ route('catalog.categories.edit', $category) }}"
                               class="text-sm text-gray-400 hover:text-blue-600">Edit</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
