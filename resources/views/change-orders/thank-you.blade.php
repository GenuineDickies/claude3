@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="surface-1 p-6">
        <h1 class="text-xl font-bold text-white mb-2">Response Recorded</h1>
        <p class="text-sm text-gray-400">Thank you. Your decision was recorded as <span class="font-semibold">{{ $decision }}</span>.</p>
    </div>
</div>
@endsection
