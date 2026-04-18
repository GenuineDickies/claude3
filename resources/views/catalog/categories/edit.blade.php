{{--
  Edit Service Category — catalog.categories.edit
  Preserved features: CSRF, @method('PUT'), shared form include, update +
  cancel actions, separate delete form (@method('DELETE'), confirm).
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('catalog.index') }}" class="text-sm text-cyan-400 hover:text-cyan-300">&larr; Back to Catalog</a>
        <h1 class="text-2xl font-bold text-white mt-2">Edit: {{ $category->name }}</h1>
    </div>

    <div class="surface-1 p-6">
        <form action="{{ route('catalog.categories.update', $category) }}" method="POST">
            @csrf
            @method('PUT')
            @include('catalog.categories._form', ['category' => $category])

            <div class="mt-6 flex items-center gap-3">
                <button type="submit"
                        class="px-5 py-2 btn-crystal text-sm font-semibold rounded-lg  transition-colors">
                    Update Service Category
                </button>
                <a href="{{ route('catalog.index') }}"
                   class="px-5 py-2 text-sm text-gray-400 hover:text-white">Cancel</a>
            </div>
        </form>

        <div class="mt-4 pt-4 border-t border-white/10 flex justify-end">
            <form action="{{ route('catalog.categories.destroy', $category) }}" method="POST"
                  onsubmit="return confirm('Delete this category and all its services?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-4 py-2 text-sm text-red-400 hover:text-red-800 hover:bg-red-500/10 rounded-lg transition-colors">
                    Delete Service Category
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
