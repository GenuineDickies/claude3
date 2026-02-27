<?php

namespace App\Http\Controllers;

use App\Models\Correspondence;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CorrespondenceController extends Controller
{
    public function store(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $customer = $serviceRequest->customer;

        if (! $customer) {
            return back()->with('error', 'No customer associated with this service request.');
        }

        $validated = $request->validate([
            'channel' => ['required', Rule::in(Correspondence::CHANNELS)],
            'direction' => ['required', Rule::in(Correspondence::DIRECTIONS)],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:5000'],
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'outcome' => ['nullable', 'string', 'max:100'],
        ]);

        Correspondence::create([
            'customer_id' => $customer->id,
            'service_request_id' => $serviceRequest->id,
            'channel' => $validated['channel'],
            'direction' => $validated['direction'],
            'subject' => $validated['subject'] ?? null,
            'body' => $validated['body'] ?? null,
            'logged_by' => Auth::id(),
            'logged_at' => now(),
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'outcome' => $validated['outcome'] ?? null,
        ]);

        return back()->with('success', 'Correspondence logged.');
    }
}
