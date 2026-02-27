<?php

namespace Tests\Feature;

use App\Models\ApiMonitorEndpoint;
use App\Models\ApiMonitorRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ApiMonitorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_monitor_settings_page_requires_authentication(): void
    {
        $this->get(route('settings.api-monitor.index'))
            ->assertRedirect(route('login'));
    }

    public function test_api_monitor_settings_page_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.api-monitor.index'))
            ->assertOk()
            ->assertSee('API Health Monitoring');
    }

    public function test_user_can_create_api_monitor_endpoint(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.api-monitor.store'), [
                'name' => 'Custom Health API',
                'url' => 'https://example.com/health',
                'method' => 'GET',
                'expected_status_code' => 200,
                'check_interval_minutes' => 10,
                'is_active' => 1,
            ])
            ->assertRedirect(route('settings.api-monitor.index'));

        $this->assertDatabaseHas('api_monitor_endpoints', [
            'name' => 'Custom Health API',
            'url' => 'https://example.com/health',
            'method' => 'GET',
            'expected_status_code' => 200,
            'check_interval_minutes' => 10,
            'is_active' => 1,
        ]);
    }

    public function test_user_can_update_endpoint_configuration(): void
    {
        $user = User::factory()->create();

        $endpoint = ApiMonitorEndpoint::create([
            'name' => 'Mutable API',
            'url' => 'https://example.com/health',
            'method' => 'GET',
            'check_interval_minutes' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->put(route('settings.api-monitor.update', $endpoint), [
                'expected_status_code' => 204,
                'check_interval_minutes' => 30,
            ])
            ->assertRedirect(route('settings.api-monitor.index'));

        $endpoint->refresh();

        $this->assertSame(204, $endpoint->expected_status_code);
        $this->assertSame(30, $endpoint->check_interval_minutes);
        $this->assertFalse($endpoint->is_active);
    }

    public function test_user_can_run_endpoint_check_from_management_page(): void
    {
        $user = User::factory()->create();

        $endpoint = ApiMonitorEndpoint::create([
            'name' => 'Runnable API',
            'url' => 'https://example.com/health',
            'method' => 'GET',
            'check_interval_minutes' => 5,
            'is_active' => true,
        ]);

        Http::fake([
            'https://example.com/health' => Http::response(['ok' => true], 200),
        ]);

        $this->actingAs($user)
            ->post(route('settings.api-monitor.run', $endpoint))
            ->assertRedirect(route('settings.api-monitor.index'));

        $endpoint->refresh();

        $this->assertSame('healthy', $endpoint->last_status);
        $this->assertDatabaseHas('api_monitor_runs', [
            'endpoint_id' => $endpoint->id,
            'status' => 'healthy',
            'is_success' => 1,
            'status_code' => 200,
        ]);

        $this->assertGreaterThan(0, ApiMonitorRun::count());
    }

    public function test_dashboard_displays_api_health_widget(): void
    {
        $user = User::factory()->create();

        ApiMonitorEndpoint::create([
            'name' => 'Healthy API',
            'url' => 'https://healthy.example.test',
            'method' => 'GET',
            'check_interval_minutes' => 5,
            'is_active' => true,
            'last_status' => 'healthy',
        ]);

        ApiMonitorEndpoint::create([
            'name' => 'Degraded API',
            'url' => 'https://degraded.example.test',
            'method' => 'GET',
            'check_interval_minutes' => 5,
            'is_active' => true,
            'last_status' => 'degraded',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('API Health')
            ->assertSeeText('Active Endpoints')
            ->assertSeeText('Healthy')
            ->assertSeeText('Degraded');
    }
}
