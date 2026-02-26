@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('message-templates.index') }}" class="text-sm text-blue-600 hover:text-blue-700">&larr; Back to Templates</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Create Template</h1>
    </div>

    <div class="bg-white rounded-lg shadow-xs p-6">
        <form action="{{ route('message-templates.store') }}" method="POST">
            @csrf
            @include('message-templates._form', ['template' => null])

            <div class="mt-6 flex items-center gap-3">
                <button type="submit"
                        class="px-5 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                    Create Template
                </button>
                <a href="{{ route('message-templates.index') }}"
                   class="px-5 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
