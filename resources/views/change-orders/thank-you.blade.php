@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-lg shadow-xs p-6">
        <h1 class="text-xl font-bold text-gray-900 mb-2">Response Recorded</h1>
        <p class="text-sm text-gray-600">Thank you. Your decision was recorded as <span class="font-semibold">{{ $decision }}</span>.</p>
    </div>
</div>
@endsection
