@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('catalog.index') }}" class="text-sm text-cyan-400 hover:text-cyan-300">&larr; Back to Catalog</a>
        <h1 class="text-2xl font-bold text-white mt-2">Add Service</h1>
    </div>

    <div class="surface-1 p-6">
        <form action="{{ route('catalog.items.store') }}" method="POST">
            @csrf
            @include('catalog.items._form', ['item' => null])

            <div class="mt-6 flex items-center gap-3">
                <button type="submit"
                        class="px-5 py-2 btn-crystal text-sm font-semibold rounded-lg  transition-colors">
                    Create Service
                </button>
                <a href="{{ route('catalog.index') }}"
                   class="px-5 py-2 text-sm text-gray-400 hover:text-white">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
