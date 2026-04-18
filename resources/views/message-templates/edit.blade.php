{{--
  Edit Message Template — message-templates.edit
  Preserved features: CSRF, @method('PUT'), shared form include, update +
  cancel actions, separate delete form (@method('DELETE'), confirm).
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('message-templates.index') }}" class="text-sm text-cyan-400 hover:text-cyan-300">&larr; Back to Templates</a>
        <h1 class="text-2xl font-bold text-white mt-2">Edit: {{ $messageTemplate->name }}</h1>
    </div>

    <div class="surface-1 p-6">
        <form action="{{ route('message-templates.update', $messageTemplate) }}" method="POST">
            @csrf
            @method('PUT')
            @include('message-templates._form', ['template' => $messageTemplate])

            <div class="mt-6 flex items-center gap-3">
                <button type="submit"
                        class="px-5 py-2 btn-crystal text-sm font-semibold rounded-lg  transition-colors">
                    Update Template
                </button>
                <a href="{{ route('message-templates.index') }}"
                   class="px-5 py-2 text-sm text-gray-400 hover:text-white">Cancel</a>
            </div>
        </form>

        {{-- Delete (separate form to avoid nesting) --}}
        <div class="mt-4 pt-4 border-t border-white/10 flex justify-end">
            <form action="{{ route('message-templates.destroy', $messageTemplate) }}" method="POST"
                  onsubmit="return confirm('Delete this template?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-4 py-2 text-sm text-red-400 hover:text-red-800 hover:bg-red-500/10 rounded-lg transition-colors">
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
