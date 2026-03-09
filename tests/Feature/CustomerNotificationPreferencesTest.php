<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Estimate;
use App\Models\MessageTemplate;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\SmsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CustomerNotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function createOptedInCustomer(array $prefs = null): Customer
    {
        return Customer::create([
            'first_name'               => 'Jane',
            'last_name'                => 'Doe',
            'phone'                    => '5551234567',
            'is_active'                => true,
            'sms_consent_at'           => now(),
            'notification_preferences' => $prefs,
        ]);
    }

    private function seedStatusTemplates(): void
    {
        foreach (['dispatch-confirmation', 'technician-en-route', 'technician-arrived', 'service-completed', 'cancellation-confirmation'] as $slug) {
            MessageTemplate::create([
                'slug'     => $slug,
                'name'     => $slug,
                'category' => 'dispatch',
                'body'     => 'Status update for {{ customer_first_name }}.',
            ]);
        }
    }

    private function makeDispatchReady(ServiceRequest $serviceRequest): void
    {
        Estimate::create([
            'service_request_id' => $serviceRequest->id,
            'estimate_number' => 'EST-' . str_pad((string) (Estimate::count() + 1), 4, '0', STR_PAD_LEFT),
            'state_code' => 'WA',
            'tax_rate' => 0,
            'subtotal' => 250,
            'tax_amount' => 0,
            'total' => 250,
            'status' => 'accepted',
            'version' => 1,
            'is_locked' => false,
            'approved_at' => now(),
        ]);

        WorkOrder::create([
            'service_request_id' => $serviceRequest->id,
            'work_order_number' => 'WO-NOTIFY-' . str_pad((string) (WorkOrder::count() + 1), 4, '0', STR_PAD_LEFT),
            'status' => WorkOrder::STATUS_PENDING,
            'priority' => 'normal',
            'assigned_to' => 'Driver One',
            'subtotal' => 0,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ]);
    }

    // ------------------------------------------------------------------
    // Model: defaults
    // ------------------------------------------------------------------

    public function test_default_preferences_allow_all_notifications(): void
    {
        $customer = $this->createOptedInCustomer();

        $this->assertTrue($customer->wantsNotification('status_updates'));
        $this->assertTrue($customer->wantsNotification('location_requests'));
        $this->assertTrue($customer->wantsNotification('signature_requests'));
        $this->assertTrue($customer->wantsNotification('marketing'));
    }

    public function test_unknown_notification_type_defaults_to_true(): void
    {
        $customer = $this->createOptedInCustomer();

        $this->assertTrue($customer->wantsNotification('some_future_type'));
    }

    public function test_custom_preferences_are_respected(): void
    {
        $customer = $this->createOptedInCustomer([
            'status_updates'     => false,
            'location_requests'  => true,
            'signature_requests' => false,
            'marketing'          => true,
        ]);

        $this->assertFalse($customer->wantsNotification('status_updates'));
        $this->assertTrue($customer->wantsNotification('location_requests'));
        $this->assertFalse($customer->wantsNotification('signature_requests'));
        $this->assertTrue($customer->wantsNotification('marketing'));
    }

    public function test_preferences_persist_through_save_reload(): void
    {
        $customer = $this->createOptedInCustomer([
            'status_updates' => false,
            'marketing'      => false,
        ]);

        $reloaded = Customer::find($customer->id);

        $this->assertFalse($reloaded->wantsNotification('status_updates'));
        $this->assertFalse($reloaded->wantsNotification('marketing'));
    }

    public function test_partial_preferences_default_missing_keys_to_true(): void
    {
        $customer = $this->createOptedInCustomer([
            'status_updates' => false,
        ]);

        $this->assertFalse($customer->wantsNotification('status_updates'));
        $this->assertTrue($customer->wantsNotification('location_requests'));
        $this->assertTrue($customer->wantsNotification('signature_requests'));
    }

    // ------------------------------------------------------------------
    // ServiceRequestController: status_updates preference
    // ------------------------------------------------------------------

    public function test_status_sms_sent_when_preference_enabled(): void
    {
        $this->seedStatusTemplates();

        $customer = $this->createOptedInCustomer(['status_updates' => true]);
        $sr = ServiceRequest::create([
            'customer_id' => $customer->id,
            'status'      => 'new',
        ]);
        $this->makeDispatchReady($sr);

        $smsMock = $this->mock(SmsServiceInterface::class);
        $smsMock->shouldReceive('sendTemplate')
            ->once()
            ->andReturn(['success' => true, 'message_id' => 'msg-1', 'rendered_text' => 'text', 'error' => null]);

        $this->actingAs(User::factory()->create())
            ->patch(route('service-requests.update', $sr), [
                'status'          => 'dispatched',
                'notify_customer' => true,
            ]);

        $this->assertDatabaseHas('service_requests', ['id' => $sr->id, 'status' => 'dispatched']);
    }

    public function test_status_sms_blocked_when_preference_disabled(): void
    {
        $this->seedStatusTemplates();

        $customer = $this->createOptedInCustomer(['status_updates' => false]);
        $sr = ServiceRequest::create([
            'customer_id' => $customer->id,
            'status'      => 'new',
        ]);
        $this->makeDispatchReady($sr);

        $smsMock = $this->mock(SmsServiceInterface::class);
        $smsMock->shouldNotReceive('sendTemplate');
        $smsMock->shouldNotReceive('sendRaw');

        $this->actingAs(User::factory()->create())
            ->patch(route('service-requests.update', $sr), [
                'status'          => 'dispatched',
                'notify_customer' => true,
            ]);

        $this->assertDatabaseHas('service_requests', ['id' => $sr->id, 'status' => 'dispatched']);
    }

    // ------------------------------------------------------------------
    // LocationShareController: location_requests preference
    // ------------------------------------------------------------------

    public function test_location_sms_sent_when_preference_enabled(): void
    {
        MessageTemplate::create([
            'slug'     => 'location-request',
            'name'     => 'Location Request',
            'category' => 'dispatch',
            'body'     => 'Share your location: {{ location_link }}',
        ]);

        $customer = $this->createOptedInCustomer(['location_requests' => true]);
        $sr = ServiceRequest::create([
            'customer_id' => $customer->id,
            'status'      => 'new',
        ]);

        $smsMock = $this->mock(SmsServiceInterface::class);
        $smsMock->shouldReceive('sendTemplate')
            ->once()
            ->andReturn(['success' => true, 'message_id' => 'msg-1', 'rendered_text' => 'text', 'error' => null]);

        $response = $this->actingAs(User::factory()->create())
            ->post(route('service-requests.request-location', $sr));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_location_sms_blocked_when_preference_disabled(): void
    {
        $customer = $this->createOptedInCustomer(['location_requests' => false]);
        $sr = ServiceRequest::create([
            'customer_id' => $customer->id,
            'status'      => 'new',
        ]);

        $smsMock = $this->mock(SmsServiceInterface::class);
        $smsMock->shouldNotReceive('sendTemplate');
        $smsMock->shouldNotReceive('sendRaw');

        $response = $this->actingAs(User::factory()->create())
            ->post(route('service-requests.request-location', $sr));

        $response->assertRedirect();
        $response->assertSessionHas('warning', 'Customer has disabled location request notifications.');
    }

    // ------------------------------------------------------------------
    // SignatureController: signature_requests preference
    // ------------------------------------------------------------------

    public function test_signature_sms_sent_when_preference_enabled(): void
    {
        $customer = $this->createOptedInCustomer(['signature_requests' => true]);
        $sr = ServiceRequest::create([
            'customer_id' => $customer->id,
            'status'      => 'on_scene',
        ]);

        $smsMock = $this->mock(SmsServiceInterface::class);
        $smsMock->shouldReceive('sendRawWithLog')
            ->once()
            ->andReturn(['success' => true, 'message_id' => 'msg-1', 'rendered_text' => 'text', 'error' => null]);

        $response = $this->actingAs(User::factory()->create())
            ->post(route('signatures.request', $sr), [
                'send_sms' => true,
            ]);

        $response->assertRedirect();
    }

    public function test_signature_sms_blocked_when_preference_disabled(): void
    {
        $customer = $this->createOptedInCustomer(['signature_requests' => false]);
        $sr = ServiceRequest::create([
            'customer_id' => $customer->id,
            'status'      => 'on_scene',
        ]);

        $smsMock = $this->mock(SmsServiceInterface::class);
        $smsMock->shouldNotReceive('sendRaw');
        $smsMock->shouldNotReceive('sendTemplate');

        $response = $this->actingAs(User::factory()->create())
            ->post(route('signatures.request', $sr), [
                'send_sms' => true,
            ]);

        $response->assertRedirect();
    }

    // ------------------------------------------------------------------
    // Null preferences (no column set) default to all enabled
    // ------------------------------------------------------------------

    public function test_null_preferences_allow_all_sms(): void
    {
        $this->seedStatusTemplates();

        $customer = $this->createOptedInCustomer(null);
        $sr = ServiceRequest::create([
            'customer_id' => $customer->id,
            'status'      => 'new',
        ]);
        $this->makeDispatchReady($sr);

        $smsMock = $this->mock(SmsServiceInterface::class);
        $smsMock->shouldReceive('sendTemplate')
            ->once()
            ->andReturn(['success' => true, 'message_id' => 'msg-1', 'rendered_text' => 'text', 'error' => null]);

        $this->actingAs(User::factory()->create())
            ->patch(route('service-requests.update', $sr), [
                'status'          => 'dispatched',
                'notify_customer' => true,
            ]);
    }

    // ------------------------------------------------------------------
    // DEFAULT_NOTIFICATION_PREFERENCES constant
    // ------------------------------------------------------------------

    public function test_default_preferences_constant_has_expected_keys(): void
    {
        $defaults = Customer::DEFAULT_NOTIFICATION_PREFERENCES;

        $this->assertArrayHasKey('status_updates', $defaults);
        $this->assertArrayHasKey('location_requests', $defaults);
        $this->assertArrayHasKey('signature_requests', $defaults);
        $this->assertArrayHasKey('marketing', $defaults);
        $this->assertTrue($defaults['status_updates']);
        $this->assertTrue($defaults['location_requests']);
        $this->assertTrue($defaults['signature_requests']);
        $this->assertTrue($defaults['marketing']);
    }
}
