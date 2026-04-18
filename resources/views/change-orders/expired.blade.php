{{--
    PUBLIC-FACING CHANGE ORDER EXPIRED PAGE
    Preserved features:
      - Layout: @extends('layouts.app') with @section('content')
      - Wrapper: max-w-xl centered card (intentionally narrow; do NOT widen)
      - Static "link expired" message directing user to contact dispatch
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="surface-1 p-6">
        <h1 class="text-xl font-bold text-white mb-2">Approval Link Expired</h1>
        <p class="text-sm text-gray-400">This change authorization link is no longer valid. Please contact dispatch for a new link.</p>
    </div>
</div>
@endsection
