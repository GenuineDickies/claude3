<?php

namespace App\Console\Commands;

use App\Models\ApiMonitorEndpoint;
use App\Models\ApiMonitorRun;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class ApiMonitorCommand extends Command
{
    protected $signature = 'api:monitor {--only-id= : Run checks for a single endpoint id}';

    protected $description = 'Run API health checks and persist endpoint status history';

    public function handle(): int
    {
        $this->seedDefaultEndpoints();

        $query = ApiMonitorEndpoint::query()->where('is_active', true);

        $onlyId = $this->option('only-id');
        if ($onlyId !== null && $onlyId !== '') {
            $query->where('id', (int) $onlyId);
        }

        $endpoints = $query->get();

        if ($endpoints->isEmpty()) {
            $this->warn('No active API monitor endpoints found.');
            return self::SUCCESS;
        }

        foreach ($endpoints as $endpoint) {
            $result = $this->checkEndpoint($endpoint);

            ApiMonitorRun::create([
                'endpoint_id' => $endpoint->id,
                'status_code' => $result['status_code'],
                'response_time_ms' => $result['response_time_ms'],
                'is_success' => $result['is_success'],
                'status' => $result['status'],
                'error_message' => $result['error_message'],
                'checked_at' => now(),
            ]);

            $consecutiveFailures = $result['is_success']
                ? 0
                : ($endpoint->consecutive_failures + 1);

            $endpoint->update([
                'last_checked_at' => now(),
                'last_status' => $result['status'],
                'last_response_time_ms' => $result['response_time_ms'],
                'last_error' => $result['error_message'],
                'consecutive_failures' => $consecutiveFailures,
            ]);

            $message = sprintf(
                '[%s] %s (%s ms)',
                strtoupper($result['status']),
                $endpoint->name,
                (string) ($result['response_time_ms'] ?? 'n/a')
            );

            if ($result['status'] === 'down') {
                $this->error($message);
            } elseif ($result['status'] === 'degraded') {
                $this->warn($message);
            } else {
                $this->info($message);
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return array{status: string, status_code: int|null, response_time_ms: int|null, is_success: bool, error_message: string|null}
     */
    private function checkEndpoint(ApiMonitorEndpoint $endpoint): array
    {
        $headers = is_array($endpoint->headers) ? $endpoint->headers : [];

        $start = microtime(true);

        try {
            $method = strtoupper($endpoint->method ?: 'GET');
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->send($method, $endpoint->url);

            $elapsedMs = (int) round((microtime(true) - $start) * 1000);
            $statusCode = $response->status();

            $status = $this->classifyStatus($statusCode, $endpoint->expected_status_code);

            return [
                'status' => $status,
                'status_code' => $statusCode,
                'response_time_ms' => $elapsedMs,
                'is_success' => $status === 'healthy',
                'error_message' => null,
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'down',
                'status_code' => null,
                'response_time_ms' => null,
                'is_success' => false,
                'error_message' => $e->getMessage(),
            ];
        }
    }

    private function classifyStatus(int $statusCode, ?int $expectedStatus): string
    {
        if ($expectedStatus !== null) {
            return $statusCode === $expectedStatus ? 'healthy' : 'down';
        }

        if ($statusCode >= 200 && $statusCode < 400) {
            return 'healthy';
        }

        if ($statusCode >= 400 && $statusCode < 500) {
            return 'degraded';
        }

        return 'down';
    }

    private function seedDefaultEndpoints(): void
    {
        $telnyxApiKey = (string) Setting::getValue('telnyx_api_key', (string) config('services.telnyx.api_key', ''));
        if ($telnyxApiKey !== '') {
            ApiMonitorEndpoint::query()->firstOrCreate(
                ['name' => 'Telnyx API', 'url' => 'https://api.telnyx.com/v2/messaging_profiles'],
                [
                    'method' => 'GET',
                    'headers' => ['Authorization' => 'Bearer ' . $telnyxApiKey],
                    'check_interval_minutes' => 5,
                    'is_active' => true,
                ]
            );
        }

        $googleMapsApiKey = (string) Setting::getValue('google_maps_api_key', (string) config('services.google_maps.api_key', ''));
        if ($googleMapsApiKey !== '') {
            $googleUrl = 'https://maps.googleapis.com/maps/api/geocode/json?address=New+York&key=' . urlencode($googleMapsApiKey);
            ApiMonitorEndpoint::query()->firstOrCreate(
                ['name' => 'Google Maps Geocoding API', 'url' => $googleUrl],
                [
                    'method' => 'GET',
                    'headers' => null,
                    'check_interval_minutes' => 5,
                    'is_active' => true,
                ]
            );
        }
    }
}
