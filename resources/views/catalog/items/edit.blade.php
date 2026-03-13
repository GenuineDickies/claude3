@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('catalog.index') }}" class="text-sm text-cyan-400 hover:text-cyan-300">&larr; Back to Catalog</a>
        <h1 class="text-2xl font-bold text-white mt-2">Edit: {{ $item->name }}</h1>
    </div>

    <div class="surface-1 p-6">
        <form action="{{ route('catalog.items.update', $item) }}" method="POST">
            @csrf
            @method('PUT')
            @include('catalog.items._form', ['item' => $item])

            <div class="mt-6 flex items-center gap-3">
                <button type="submit"
                        class="px-5 py-2 btn-crystal text-sm font-semibold rounded-lg  transition-colors">
                    Update Service
                </button>
                <a href="{{ route('catalog.index') }}"
                   class="px-5 py-2 text-sm text-gray-400 hover:text-white">Cancel</a>
            </div>
        </form>

        <div class="mt-4 pt-4 border-t border-white/10 flex justify-end">
            <form action="{{ route('catalog.items.destroy', $item) }}" method="POST"
                  onsubmit="return confirm('Delete this service?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-4 py-2 text-sm text-red-400 hover:text-red-800 hover:bg-red-500/10 rounded-lg transition-colors">
                    Delete Service
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
