<?php

namespace Tests\Feature;

use App\Models\ApiMonitorEndpoint;
use App\Models\ApiMonitorRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ApiMonitorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_monitor_marks_healthy_on_successful_response(): void
    {
        ApiMonitorEndpoint::create([
            'name' => 'Test API',
            'url' => 'https://example.com/health',
            'method' => 'GET',
            'is_active' => true,
        ]);

        Http::fake([
            'https://example.com/health' => Http::response(['ok' => true], 200),
        ]);

        $this->artisan('api:monitor')->assertExitCode(0);

        $endpoint = ApiMonitorEndpoint::firstOrFail();
        $this->assertSame('healthy', $endpoint->last_status);
        $this->assertSame(0, $endpoint->consecutive_failures);

        $run = ApiMonitorRun::firstOrFail();
        $this->assertSame('healthy', $run->status);
        $this->assertTrue($run->is_success);
        $this->assertSame(200, $run->status_code);
    }

    public function test_api_monitor_marks_degraded_on_client_errors(): void
    {
        ApiMonitorEndpoint::create([
            'name' => 'Client Error API',
            'url' => 'https://example.com/not-found',
            'method' => 'GET',
            'is_active' => true,
        ]);

        Http::fake([
            'https://example.com/not-found' => Http::response([], 404),
        ]);

        $this->artisan('api:monitor')->assertExitCode(0);

        $endpoint = ApiMonitorEndpoint::firstOrFail();
        $this->assertSame('degraded', $endpoint->last_status);
        $this->assertSame(1, $endpoint->consecutive_failures);
    }

    public function test_api_monitor_marks_down_when_request_throws(): void
    {
        ApiMonitorEndpoint::create([
            'name' => 'Down API',
            'url' => 'https://down.example.test',
            'method' => 'GET',
            'is_active' => true,
        ]);

        Http::fake(function () {
            throw new \RuntimeException('connection timeout');
        });

        $this->artisan('api:monitor')->assertExitCode(0);

        $endpoint = ApiMonitorEndpoint::firstOrFail();
        $this->assertSame('down', $endpoint->last_status);
        $this->assertSame(1, $endpoint->consecutive_failures);

        $run = ApiMonitorRun::firstOrFail();
        $this->assertSame('down', $run->status);
        $this->assertFalse($run->is_success);
        $this->assertSame('connection timeout', $run->error_message);
    }
}
