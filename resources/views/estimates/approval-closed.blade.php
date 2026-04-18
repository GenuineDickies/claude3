{{--
    PUBLIC-FACING ESTIMATE APPROVAL CLOSED PAGE
    Preserved features:
      - Layout: @extends('layouts.app') with @section('content')
      - Wrapper: max-w-lg centered card (intentionally narrow; do NOT widen)
      - Branches on $estimate->status: 'accepted' (green check), 'declined' (red X), else expired (amber clock)
      - Shows $estimate->approved_at formatted when applicable
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="surface-1 p-6 text-center">
        @if($estimate->status === 'accepted')
            <div class="text-green-400 mb-4">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h1 class="text-xl font-bold text-white mb-2">Estimate Already Approved</h1>
            <p class="text-sm text-gray-400">This estimate was approved on {{ $estimate->approved_at?->format('M j, Y g:i A') }}.</p>
        @elseif($estimate->status === 'declined')
            <div class="text-red-500 mb-4">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h1 class="text-xl font-bold text-white mb-2">Estimate Declined</h1>
            <p class="text-sm text-gray-400">This estimate was declined on {{ $estimate->approved_at?->format('M j, Y g:i A') }}.</p>
        @else
            <div class="text-amber-500 mb-4">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h1 class="text-xl font-bold text-white mb-2">Link Expired</h1>
            <p class="text-sm text-gray-400">This approval link has expired. Please contact the service provider for a new one.</p>
        @endif
    </div>
</div>
@endsection
