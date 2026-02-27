@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-lg shadow-xs p-6 text-center">
        @if($decision === 'approved')
            <div class="text-green-600 mb-4">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900 mb-2">Estimate Approved</h1>
            <p class="text-sm text-gray-600">Thank you! You have approved estimate {{ $estimate->displayNumber() }} for <strong>${{ number_format($estimate->approved_total, 2) }}</strong>.</p>
        @else
            <div class="text-red-500 mb-4">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900 mb-2">Estimate Declined</h1>
            <p class="text-sm text-gray-600">You have declined estimate {{ $estimate->displayNumber() }}. The service provider has been notified.</p>
        @endif
    </div>
</div>
@endsection
