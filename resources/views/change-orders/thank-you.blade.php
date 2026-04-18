{{--
    PUBLIC-FACING CHANGE ORDER THANK YOU PAGE
    Preserved features:
      - Layout: @extends('layouts.app') with @section('content')
      - Wrapper: max-w-xl centered card (intentionally narrow; do NOT widen)
      - Confirmation message echoing $decision back to the signer
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="surface-1 p-6">
        <h1 class="text-xl font-bold text-white mb-2">Response Recorded</h1>
        <p class="text-sm text-gray-400">Thank you. Your decision was recorded as <span class="font-semibold">{{ $decision }}</span>.</p>
    </div>
</div>
@endsection
