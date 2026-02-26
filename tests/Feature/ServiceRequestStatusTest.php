<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MessageTemplate;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatusLog;
use App\Models\User;
use App\Services\SmsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestStatusTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create();
    }

    private function createServiceRequest(string $status = 'new'): ServiceRequest
    {
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name'  => 'Customer',
            'phone'      => '5551234567',
            'is_active'  => true,
        ]);

        return ServiceRequest::create([
            'customer_id' => $customer->id,
            'status'      => $status,
        ]);
    }

    // ── Model constants & helpers ─────────────────────────────

    public function test_statuses_constant_contains_all_expected_values(): void
    {
        $this->assertEquals(
            ['new', 'dispatched', 'en_route', 'on_scene', 'completed', 'cancelled'],
            ServiceRequest::STATUSES,
        );
    }

    public function test_transitions_map_defines_linear_progression(): void
    {
        $this->assertEquals('dispatched', ServiceRequest::TRANSITIONS['new']);
        $this->assertEquals('en_route', ServiceRequest::TRANSITIONS['dispatched']);
        $this->assertEquals('on_scene', ServiceRequest::TRANSITIONS['en_route']);
        $this->assertEquals('completed', ServiceRequest::TRANSITIONS['on_scene']);
        $this->assertArrayNotHasKey('completed', ServiceRequest::TRANSITIONS);
        $this->assertArrayNotHasKey('cancelled', ServiceRequest::TRANSITIONS);
    }

    public function test_next_status_returns_correct_forward_status(): void
    {
        $sr = $this->createServiceRequest('new');
        $this->assertEquals('dispatched', $sr->nextStatus());

        $sr->status = 'on_scene';
        $this->assertEquals('completed', $sr->nextStatus());
    }

    public function test_next_status_returns_null_for_terminal(): void
    {
        $sr = $this->createServiceRequest('completed');
        $this->assertNull($sr->nextStatus());

        $sr->status = 'cancelled';
        $this->assertNull($sr->nextStatus());
    }

    public function test_can_transition_to_allows_forward_step(): void
    {
        $sr = $this->createServiceRequest('new');
        $this->assertTrue($sr->canTransitionTo('dispatched'));
        $this->assertFalse($sr->canTransitionTo('completed'));
    }

    public function test_can_transition_to_allows_cancel_from_non_terminal(): void
    {
        $sr = $this->createServiceRequest('dispatched');
        $this->assertTrue($sr->canTransitionTo('cancelled'));
    }

    public function test_cannot_cancel_from_terminal_status(): void
    {
        $sr = $this->createServiceRequest('completed');
        $this->assertFalse($sr->canTransitionTo('cancelled'));
    }

    public function test_status_label_returns_human_readable_string(): void
    {
        $sr = $this->createServiceRequest('en_route');
        $this->assertEquals('En Route', $sr->statusLabel());
    }

    // ── Controller: successful transitions ────────────────────

    public function test_update_advances_status_forward(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest('new');

        $response = $this->actingAs($user)->patch(
            route('service-requests.update', $sr),
            ['status' => 'dispatched'],
        );

        $response->assertRedirect(route('service-requests.show', $sr));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('service_requests', ['id' => $sr->id, 'status' => 'dispatched']);
    }

    public function test_update_creates_status_log(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest('new');

        $this->actingAs($user)->patch(
            route('service-requests.update', $sr),
            ['status' => 'dispatched', 'notes' => 'Driver confirmed'],
        );

        $this->assertDatabaseHas('service_request_status_logs', [
            'service_request_id' => $sr->id,
            'old_status'         => 'new',
            'new_status'         => 'dispatched',
            'changed_by'         => $user->id,
            'notes'              => 'Driver confirmed',
        ]);
    }

    public function test_update_allows_cancel_from_non_terminal(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest('en_route');

        $response = $this->actingAs($user)->patch(
            route('service-requests.update', $sr),
            ['status' => 'cancelled'],
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('service_requests', ['id' => $sr->id, 'status' => 'cancelled']);
    }

    // ── Controller: blocked transitions ───────────────────────

    public function test_update_blocks_invalid_transition(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest('new');

        $response = $this->actingAs($user)->patch(
            route('service-requests.update', $sr),
            ['status' => 'completed'],
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('service_requests', ['id' => $sr->id, 'status' => 'new']);
    }

    public function test_update_blocks_transition_from_completed(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest('completed');

        $response = $this->actingAs($user)->patch(
            route('service-requests.update', $sr),
            ['status' => 'cancelled'],
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('service_requests', ['id' => $sr->id, 'status' => 'completed']);
    }

    public function test_update_validates_status_value(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest('new');

        $response = $this->actingAs($user)->patch(
            route('service-requests.update', $sr),
            ['status' => 'invalid_status'],
        );

        $response->assertSessionHasErrors('status');
    }

    // ── Auth requirement ──────────────────────────────────────

    public function test_update_requires_authentication(): void
    {
        $sr = $this->createServiceRequest('new');

        $response = $this->patch(
            route('service-requests.update', $sr),
            ['status' => 'dispatched'],
        );

        $response->assertRedirect(route('login'));
    }

    // ── Full lifecycle ────────────────────────────────────────

    public function test_full_status_lifecycle(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest('new');

        $transitions = ['dispatched', 'en_route', 'on_scene', 'completed'];

        foreach ($transitions as $status) {
            $this->actingAs($user)->patch(
                route('service-requests.update', $sr),
                ['status' => $status],
            );
        }

        $sr->refresh();
        $this->assertEquals('completed', $sr->status);
        $this->assertEquals(4, $sr->statusLogs()->count());
    }

    // ── SMS notification ──────────────────────────────────────

    public function test_notify_customer_sends_sms_when_template_exists(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest('new');

        // Grant SMS consent
        $sr->customer->grantSmsConsent();

        // Create the matching template
        MessageTemplate::create([
            'slug'     => 'dispatch-confirmation',
            'name'     => 'Dispatch Confirmation',
            'category' => 'dispatch',
            'body'     => '{{ company_name }}: Dispatched! Ticket #{{ service_request_id }}',
        ]);

        $mock = $this->mock(SmsServiceInterface::class);
        $mock->shouldReceive('sendTemplate')->once();

        $this->actingAs($user)->patch(
            route('service-requests.update', $sr),
            ['status' => 'dispatched', 'notify_customer' => '1'],
        );
    }

    public function test_no_sms_when_notify_not_checked(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest('new');

        $sr->customer->grantSmsConsent();

        MessageTemplate::create([
            'slug'     => 'dispatch-confirmation',
            'name'     => 'Dispatch Confirmation',
            'category' => 'dispatch',
            'body'     => 'test',
        ]);

        $mock = $this->mock(SmsServiceInterface::class);
        $mock->shouldNotReceive('sendTemplate');

        $this->actingAs($user)->patch(
            route('service-requests.update', $sr),
            ['status' => 'dispatched'],
        );
    }

    // ── Status badge component ────────────────────────────────

    public function test_status_badge_renders_correct_label(): void
    {
        $view = $this->blade('<x-status-badge status="en_route" />');
        $view->assertSee('En Route');
    }

    public function test_status_badge_uses_correct_color_classes(): void
    {
        $view = $this->blade('<x-status-badge status="completed" />');
        $view->assertSee('bg-green-100');
        $view->assertSee('text-green-800');
    }

    // ── Show page renders status controls ─────────────────────

    public function test_show_page_renders_advance_button_for_non_terminal(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest('new');

        $response = $this->actingAs($user)->get(route('service-requests.show', $sr));

        $response->assertOk();
        $response->assertSee('Mark as Dispatched');
    }

    public function test_show_page_hides_controls_for_completed(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest('completed');

        $response = $this->actingAs($user)->get(route('service-requests.show', $sr));

        $response->assertOk();
        $response->assertDontSee('Mark as');
    }

    // ── Status history on show page ───────────────────────────

    public function test_show_page_displays_status_history(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest('dispatched');

        ServiceRequestStatusLog::create([
            'service_request_id' => $sr->id,
            'old_status'         => 'new',
            'new_status'         => 'dispatched',
            'changed_by'         => $user->id,
            'notes'              => 'On the way',
        ]);

        $response = $this->actingAs($user)->get(route('service-requests.show', $sr));

        $response->assertOk();
        $response->assertSee('Status History');
        $response->assertSee('On the way');
    }
}
