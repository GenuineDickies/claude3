<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ServiceRequestVehicleRecordTest extends TestCase
{
    use RefreshDatabase;

    private function createServiceRequest(): ServiceRequest
    {
        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '5559876543',
            'is_active' => true,
        ]);

        return ServiceRequest::create([
            'customer_id' => $customer->id,
            'status' => 'new',
            'vehicle_year' => '2020',
            'vehicle_make' => 'Toyota',
            'vehicle_model' => 'Camry',
            'vehicle_color' => 'Blue',
        ]);
    }

    public function test_service_request_vehicle_record_requires_plate_or_vin(): void
    {
        $serviceRequest = $this->createServiceRequest();
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('service-requests.show', $serviceRequest))
            ->patch(route('service-requests.sync-vehicle', $serviceRequest), [
                'vehicle_year' => '2020',
                'vehicle_make' => 'Toyota',
                'vehicle_model' => 'Camry',
            ]);

        $response->assertSessionHasErrors(['license_plate', 'vin']);
        $this->assertNull($serviceRequest->fresh()->vehicle_id);
    }

    public function test_service_request_vehicle_record_can_be_attached_early(): void
    {
        $serviceRequest = $this->createServiceRequest();
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patch(route('service-requests.sync-vehicle', $serviceRequest), [
                'vehicle_year' => '2020',
                'vehicle_make' => 'Toyota',
                'vehicle_model' => 'Camry',
                'vehicle_color' => 'Blue',
                'license_plate' => 'abc123',
            ]);

        $response->assertRedirect(route('service-requests.show', $serviceRequest));

        $serviceRequest->refresh();
        $this->assertNotNull($serviceRequest->vehicle_id);
        $this->assertEquals('ABC123', $serviceRequest->vehicle?->license_plate);
    }
}