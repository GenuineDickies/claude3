<?php

namespace App\Jobs;

use App\Models\ServiceRequest;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
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
            Log::warning('ReverseGeocodeJob skipped — Google Maps API key not configured', [
                'service_request_id' => $this->serviceRequest->id,
                'hint' => 'Set google_maps_api_key in Settings or GOOGLE_MAPS_API_KEY env var',
            ]);

            return;
        }

        try {
            $response = Http::timeout(10)
                ->retry(2, 100)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'latlng' => "{$lat},{$lng}",
                    'key' => $apiKey,
                ]);

            if ($response->failed()) {
                // Check for rate limit errors
                $status = $response->status();
                $data = $response->json();
                $apiStatus = $data['status'] ?? null;
                
                if ($status === 429 || $apiStatus === 'OVER_QUERY_LIMIT') {
                    Log::warning('ReverseGeocodeJob: Rate limit exceeded', [
                        'service_request_id' => $this->serviceRequest->id,
                        'lat' => $lat,
                        'lng' => $lng,
                        'http_status' => $status,
                        'api_status' => $apiStatus,
                    ]);
                    
                    // Use exponential backoff: fail with exception to trigger queue retry
                    throw new \RuntimeException('Google Maps API rate limit exceeded');
                }
                
                Log::warning('ReverseGeocodeJob: Google Maps API request failed', [
                    'service_request_id' => $this->serviceRequest->id,
                    'lat' => $lat,
                    'lng' => $lng,
                    'status' => $status,
                    'body' => substr($response->body(), 0, 500),
                ]);

                throw new \RuntimeException('Google Maps API returned status ' . $status);
            }

            $data = $response->json();

            if (!is_array($data)) {
                Log::warning('ReverseGeocodeJob: Invalid JSON response from Google Maps API', [
                    'service_request_id' => $this->serviceRequest->id,
                    'response' => substr($response->body(), 0, 500),
                ]);

                throw new \RuntimeException('Invalid JSON response from Google Maps API');
            }

            $address = $data['results'][0]['formatted_address'] ?? null;

            if ($address) {
                $this->serviceRequest->update(['location' => $address]);
            } else {
                Log::info('ReverseGeocodeJob: No address found for coordinates', [
                    'service_request_id' => $this->serviceRequest->id,
                    'lat' => $lat,
                    'lng' => $lng,
                    'status' => $data['status'] ?? 'unknown',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('ReverseGeocodeJob failed', [
                'service_request_id' => $this->serviceRequest->id,
                'lat' => $lat,
                'lng' => $lng,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Let the queue retry
        }
    }
}
