{{--
  Create Message Template — message-templates.create
  Preserved features: CSRF, shared @include('message-templates._form'),
  back link, create + cancel actions.
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('message-templates.index') }}" class="text-sm text-cyan-400 hover:text-cyan-300">&larr; Back to Templates</a>
        <h1 class="text-2xl font-bold text-white mt-2">Create Template</h1>
    </div>

    <div class="surface-1 p-6">
        <form action="{{ route('message-templates.store') }}" method="POST">
            @csrf
            @include('message-templates._form', ['template' => null])

            <div class="mt-6 flex items-center gap-3">
                <button type="submit"
                        class="px-5 py-2 btn-crystal text-sm font-semibold rounded-lg  transition-colors">
                    Create Template
                </button>
                <a href="{{ route('message-templates.index') }}"
                   class="px-5 py-2 text-sm text-gray-400 hover:text-white">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
