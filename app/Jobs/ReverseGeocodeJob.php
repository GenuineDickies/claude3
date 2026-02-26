<?php

namespace App\Jobs;

use App\Models\ServiceRequest;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Reverse-geocode a service request's lat/lng to a street address
 * via the Google Maps Geocoding API.
 *
 * Safe to queue — the customer has already received their "Location
 * received" response; this enriches the record asynchronously.
 */
class ReverseGeocodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public ServiceRequest $serviceRequest,
    ) {}

    public function handle(): void
    {
        $lat = $this->serviceRequest->latitude;
        $lng = $this->serviceRequest->longitude;

        if ($lat === null || $lng === null) {
            return;
        }

        $apiKey = Setting::getValue(
            'google_maps_api_key',
            (string) config('services.google_maps.api_key', ''),
        );

        if ($apiKey === '') {
            Log::info('ReverseGeocodeJob skipped — no Google Maps API key configured.');

            return;
        }

        try {
            $url = sprintf(
                'https://maps.googleapis.com/maps/api/geocode/json?latlng=%s,%s&key=%s',
                $lat,
                $lng,
                urlencode($apiKey),
            );

            $response = file_get_contents($url);

            if ($response === false) {
                Log::warning('ReverseGeocodeJob: file_get_contents returned false', compact('lat', 'lng'));

                return;
            }

            $data = json_decode($response, true);
            $address = $data['results'][0]['formatted_address'] ?? null;

            if ($address) {
                $this->serviceRequest->update(['location' => $address]);
            }
        } catch (\Throwable $e) {
            Log::error('ReverseGeocodeJob failed', [
                'service_request_id' => $this->serviceRequest->id,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Let the queue retry
        }
    }
}
