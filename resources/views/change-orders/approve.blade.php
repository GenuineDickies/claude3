@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-xs p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Change Authorization</h1>
        <p class="text-sm text-gray-600 mb-6">{{ $companyName }} requested your approval for a service change.</p>

        <div class="mb-6 p-4 rounded-lg bg-gray-50">
            <p class="text-sm text-gray-700"><span class="font-semibold">Work Order:</span> {{ $changeOrder->workOrder->work_order_number }}</p>
            <p class="text-sm text-gray-700 mt-1"><span class="font-semibold">Change:</span> {{ $changeOrder->description }}</p>
            <p class="text-sm text-gray-700 mt-1"><span class="font-semibold">Price Impact:</span> ${{ number_format((float) $changeOrder->price_impact, 2) }}</p>
        </div>

        <form method="POST" action="{{ route('change-orders.approve', $changeOrder->approval_token) }}" class="space-y-4">
            @csrf
            <div>
                <label for="approved_by_name" class="block text-sm font-medium text-gray-800">Your Name</label>
                <input id="approved_by_name" name="approved_by_name" type="text" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm" required>
            </div>

            <div>
                <label for="signature_data" class="block text-sm font-medium text-gray-800">Signature (optional base64 data URL)</label>
                <textarea id="signature_data" name="signature_data" rows="3" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-xs font-mono" placeholder="data:image/png;base64,..."></textarea>
            </div>

            <div class="flex gap-2">
                <button type="submit" name="decision" value="approved" class="bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-700">Approve</button>
                <button type="submit" name="decision" value="rejected" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700">Reject</button>
            </div>
        </form>
    </div>
</div>
@endsection
