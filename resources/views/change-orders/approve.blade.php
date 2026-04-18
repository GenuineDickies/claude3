{{--
    PUBLIC-FACING CHANGE ORDER APPROVAL PAGE
    Preserved features:
      - Layout: @extends('layouts.app') with @section('content')
      - Wrapper: max-w-2xl centered card (intentionally narrow; do NOT widen)
      - Displays: work_order_number, change description, price_impact
      - Form POST to route('change-orders.approve', $changeOrder->approval_token) with @csrf
      - Inputs: approved_by_name (required), signature_data textarea (optional base64 data URL)
      - Dual submit buttons: decision=approved and decision=rejected
--}}
@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="surface-1 p-6">
        <h1 class="text-2xl font-bold text-white mb-2">Change Authorization</h1>
        <p class="text-sm text-gray-400 mb-6">{{ $companyName }} requested your approval for a service change.</p>

        <div class="mb-6 p-4 rounded-lg bg-white/5">
            <p class="text-sm text-gray-300"><span class="font-semibold">Work Order:</span> {{ $changeOrder->workOrder->work_order_number }}</p>
            <p class="text-sm text-gray-300 mt-1"><span class="font-semibold">Change:</span> {{ $changeOrder->description }}</p>
            <p class="text-sm text-gray-300 mt-1"><span class="font-semibold">Price Impact:</span> ${{ number_format((float) $changeOrder->price_impact, 2) }}</p>
        </div>

        <form method="POST" action="{{ route('change-orders.approve', $changeOrder->approval_token) }}" class="space-y-4">
            @csrf
            <div>
                <label for="approved_by_name" class="block text-sm font-medium text-white">Your Name</label>
                <input id="approved_by_name" name="approved_by_name" type="text" class="mt-1 w-full border border-white/10 rounded-md px-3 py-2 text-sm" required>
            </div>

            <div>
                <label for="signature_data" class="block text-sm font-medium text-white">Signature (optional base64 data URL)</label>
                <textarea id="signature_data" name="signature_data" rows="3" class="mt-1 w-full border border-white/10 rounded-md px-3 py-2 text-xs font-mono" placeholder="data:image/png;base64,..."></textarea>
            </div>

            <div class="flex gap-2">
                <button type="submit" name="decision" value="approved" class="bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-700">Approve</button>
                <button type="submit" name="decision" value="rejected" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700">Reject</button>
            </div>
        </form>
    </div>
</div>
@endsection
