<?php

namespace Tests\Feature;

use App\Models\Correspondence;
use App\Models\Customer;
use App\Models\ServiceLog;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Models\TechnicianProfile;
use App\Models\User;
use App\Services\SmsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TechnicianLocationSmsTest extends TestCase
{
    use RefreshDatabase;

    private function createAssignedRequest(bool $withAddress = true, bool $withTechnicianPhone = true, bool $withTechnicianConsent = true): array
    {
        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '5551234567',
            'is_active' => true,
        ]);

        $technician = User::factory()->create([
            'name' => 'Alex Tech',
            'phone' => $withTechnicianPhone ? '(555) 987-6543' : null,
        ]);

        TechnicianProfile::create([
            'user_id' => $technician->id,
            'sms_consent_at' => $withTechnicianConsent ? now() : null,
            'sms_consent_meta' => $withTechnicianConsent ? ['source' => 'test'] : null,
        ]);

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $customer->id,
            'status' => 'new',
            'location' => $withAddress ? '123 Main St, Dallas, TX 75001' : null,
            'latitude' => 32.7767000,
            'longitude' => -96.7970000,
            'assigned_user_id' => $technician->id,
        ]);

        return [$customer, $technician, $serviceRequest];
    }

    public function test_send_location_to_technician_sends_sms_and_logs_it(): void
    {
        Setting::setValue('company_name', 'WKR Roadside');

        [, $technician, $serviceRequest] = $this->createAssignedRequest();

        $sms = $this->mock(SmsServiceInterface::class);
        $sms->shouldReceive('sendRaw')
            ->once()
            ->withArgs(function (string $to, string $text) use ($serviceRequest): bool {
                return $to === '5559876543'
                    && str_contains($text, 'WKR Roadside dispatch')
                    && str_contains($text, 'Ticket #' . $serviceRequest->id)
                    && str_contains($text, '123 Main St, Dallas, TX 75001')
                    && str_contains($text, 'Reply STOP')
                    && str_contains($text, 'HELP');
            })
            ->andReturn(['success' => true, 'message_id' => 'msg-tech-1', 'error' => null]);

        $response = $this->actingAs(User::factory()->create())
            ->post(route('service-requests.send-technician-location', $serviceRequest));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Location sent to technician ' . $technician->name . '.');

        $this->assertDatabaseHas('correspondences', [
            'service_request_id' => $serviceRequest->id,
            'customer_id' => $serviceRequest->customer_id,
            'channel' => Correspondence::CHANNEL_SMS,
            'subject' => 'Technician location SMS',
            'outcome' => 'sent_to_technician',
        ]);

        $this->assertDatabaseHas('service_logs', [
            'service_request_id' => $serviceRequest->id,
            'event' => 'technician_location_sent',
        ]);
    }

    public function test_send_location_to_technician_uses_canonical_user_phone_instead_of_legacy_profile_sms_phone(): void
    {
        Setting::setValue('company_name', 'WKR Roadside');

        [, $technician, $serviceRequest] = $this->createAssignedRequest();

        $technician->update(['phone' => '5551112222']);
        $technician->technicianProfile->update(['sms_phone' => '5553334444']);

        $sms = $this->mock(SmsServiceInterface::class);
        $sms->shouldReceive('sendRaw')
            ->once()
            ->withArgs(function (string $to, string $text): bool {
                return $to === '5551112222'
                    && ! str_contains($text, '5553334444');
            })
            ->andReturn(['success' => true, 'message_id' => 'msg-tech-2', 'error' => null]);

        $response = $this->actingAs(User::factory()->create())
            ->post(route('service-requests.send-technician-location', $serviceRequest));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Location sent to technician ' . $technician->name . '.');
    }

    public function test_send_location_to_technician_requires_service_address(): void
    {
        [, , $serviceRequest] = $this->createAssignedRequest(withAddress: false);

        $sms = $this->mock(SmsServiceInterface::class);
        $sms->shouldNotReceive('sendRaw');

        $response = $this->actingAs(User::factory()->create())
            ->post(route('service-requests.send-technician-location', $serviceRequest));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Add a service address before sending the location to a technician.');
        $this->assertDatabaseCount('correspondences', 0);
    }

    public function test_send_location_to_technician_requires_dispatch_sms_number(): void
    {
        [, , $serviceRequest] = $this->createAssignedRequest(withAddress: true, withTechnicianPhone: false);

        $sms = $this->mock(SmsServiceInterface::class);
        $sms->shouldNotReceive('sendRaw');

        $response = $this->actingAs(User::factory()->create())
            ->post(route('service-requests.send-technician-location', $serviceRequest));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'The assigned technician does not have a mobile phone number yet.');
    }

    public function test_send_location_to_technician_requires_technician_sms_consent(): void
    {
        [, , $serviceRequest] = $this->createAssignedRequest(withAddress: true, withTechnicianPhone: true, withTechnicianConsent: false);

        $sms = $this->mock(SmsServiceInterface::class);
        $sms->shouldNotReceive('sendRaw');

        $response = $this->actingAs(User::factory()->create())
            ->post(route('service-requests.send-technician-location', $serviceRequest));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'The assigned technician must grant SMS consent before dispatch texts can be sent.');
    }

    public function test_show_page_explains_when_technician_sms_number_is_missing(): void
    {
        [, $technician, $serviceRequest] = $this->createAssignedRequest(withAddress: true, withTechnicianPhone: false);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('service-requests.show', $serviceRequest));

        $response->assertOk();
        $response->assertSee('Send Location to Technician');
        $response->assertSee('Add a mobile phone number to the assigned technician account.');
        $response->assertDontSee('Technician SMS will be sent to ' . $technician->name);
    }
}