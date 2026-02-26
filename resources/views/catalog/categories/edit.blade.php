@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('catalog.index') }}" class="text-sm text-blue-600 hover:text-blue-700">&larr; Back to Catalog</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Edit: {{ $category->name }}</h1>
    </div>

    <div class="bg-white rounded-lg shadow-xs p-6">
        <form action="{{ route('catalog.categories.update', $category) }}" method="POST">
            @csrf
            @method('PUT')
            @include('catalog.categories._form', ['category' => $category])

            <div class="mt-6 flex items-center gap-3">
                <button type="submit"
                        class="px-5 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                    Update Category
                </button>
                <a href="{{ route('catalog.index') }}"
                   class="px-5 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</a>
            </div>
        </form>

        <div class="mt-4 pt-4 border-t border-gray-200 flex justify-end">
            <form action="{{ route('catalog.categories.destroy', $category) }}" method="POST"
                  onsubmit="return confirm('Delete this category and all its items?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-4 py-2 text-sm text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors">
                    Delete Category
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
