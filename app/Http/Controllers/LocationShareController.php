<?php

namespace App\Http\Controllers;

use App\Events\LocationShared;
use App\Jobs\ReverseGeocodeJob;
use App\Models\MessageTemplate;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Services\SmsServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocationShareController extends Controller
{
    /**
     * POST /service-requests/{serviceRequest}/request-location
     *
     * Generate a location token and send the SMS.
     * If the customer has not opted in, send the opt-in message first.
     */
    public function request(ServiceRequest $serviceRequest, SmsServiceInterface $sms): RedirectResponse
    {
        $customer = $serviceRequest->customer;

        if (! $customer) {
            return back()->with('error', 'No customer associated with this service request.');
        }

        if (! $customer->wantsNotification('location_requests')) {
            return back()->with('warning', 'Customer has disabled location request notifications.');
        }

        // ── Consent gate ──────────────────────────────────────────
        if (! $customer->hasSmsConsent()) {
            // Send the opt-in / welcome message and tell operator to wait
            $optInTemplate = MessageTemplate::where('slug', 'welcome-message')->first();

            if ($optInTemplate) {
                $sms->sendTemplate(
                    template: $optInTemplate,
                    to: $customer->phone,
                    customer: $customer,
                    serviceRequest: $serviceRequest,
                );
            }

            return back()->with('warning', 'Customer has not opted in to SMS. An opt-in message was sent to ' . $customer->phone . '. Once they reply START, you can request their location.');
        }

        // ── Generate token & send location link ───────────────────
        $serviceRequest->generateLocationToken();

        $template = MessageTemplate::where('slug', 'location-request')->first();

        if (! $template) {
            // Fallback if template hasn't been seeded
            $link = $serviceRequest->locationShareUrl();
            $companyName = Setting::getValue('company_name', config('app.name'));
            $rawText = $companyName . ': Hi ' . $customer->first_name . ', please tap this link so we can locate you: ' . $link . ' Reply STOP to opt out.';
            $sms->sendRawWithLog(
                to: $customer->phone,
                text: $rawText,
                customer: $customer,
                serviceRequest: $serviceRequest,
                subject: 'Location request',
                loggedBy: Auth::id(),
            );
        } else {
            $sms->sendTemplate(
                template: $template,
                to: $customer->phone,
                customer: $customer,
                serviceRequest: $serviceRequest,
                overrides: ['location_link' => $serviceRequest->locationShareUrl()],
            );
        }

        return back()->with('success', 'Location request SMS sent to ' . $customer->phone . '.');
    }

    /**
     * GET /locate/{token}
     *
     * Public page — no auth. Shows the geolocation capture UI.
     */
    public function show(string $token)
    {
        $serviceRequest = ServiceRequest::where('location_token', $token)->firstOrFail();

        if (! $serviceRequest->isLocationTokenValid()) {
            return response()->view('locate', [
                'expired' => true,
                'serviceRequest' => $serviceRequest,
            ], 410);
        }

        return view('locate', [
            'expired' => false,
            'serviceRequest' => $serviceRequest,
            'token' => $token,
            'mapsApiKey' => Setting::getValue('google_maps_api_key', (string) config('services.google_maps.api_key', '')),
        ]);
    }

    /**
     * POST /api/locate/{token}
     *
     * Receive GPS coordinates from the customer's browser.
     */
    public function store(Request $request, string $token): JsonResponse
    {
        $serviceRequest = ServiceRequest::where('location_token', $token)->first();

        if (! $serviceRequest || ! $serviceRequest->isLocationTokenValid()) {
            return response()->json(['error' => 'Invalid or expired token.'], 422);
        }

        $validated = $request->validate([
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy'  => ['nullable', 'numeric', 'min:0'],
        ]);

        $serviceRequest->update([
            'latitude'          => $validated['latitude'],
            'longitude'         => $validated['longitude'],
            'location_shared_at' => now(),
        ]);

        LocationShared::dispatch(
            $serviceRequest,
            (float) $validated['latitude'],
            (float) $validated['longitude'],
        );

        // Enrich with a street address asynchronously (or inline with sync driver)
        ReverseGeocodeJob::dispatch($serviceRequest);

        return response()->json([
            'ok'      => true,
            'message' => 'Location received. Thank you!',
        ]);
    }
}
