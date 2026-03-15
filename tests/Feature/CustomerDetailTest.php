<?php

namespace Tests\Feature;

use App\Models\Correspondence;
use App\Models\Customer;
use App\Models\Message;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CustomerDetailTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedUser(): User
    {
        return User::factory()->create();
    }

    public function test_customer_detail_page_loads(): void
    {
        $this->withoutVite();

        $customer = Customer::create([
            'first_name' => 'Alice',
            'last_name' => 'Wonderland',
            'phone' => '5550001111',
            'is_active' => true,
            'notification_preferences' => Customer::DEFAULT_NOTIFICATION_PREFERENCES,
        ]);

        Vehicle::create([
            'customer_id' => $customer->id,
            'year' => '2020',
            'make' => 'Toyota',
            'model' => 'Camry',
            'color' => 'Blue',
            'license_plate' => 'PLT100',
        ]);

        ServiceRequest::create([
            'customer_id' => $customer->id,
            'status' => 'new',
            'vehicle_year' => '2020',
            'vehicle_make' => 'Toyota',
            'vehicle_model' => 'Camry',
        ]);

        Message::create([
            'customer_id' => $customer->id,
            'direction' => 'outbound',
            'body' => 'Status update',
        ]);

        Correspondence::create([
            'customer_id' => $customer->id,
            'channel' => Correspondence::CHANNEL_PHONE,
            'direction' => Correspondence::DIRECTION_OUTBOUND,
            'subject' => 'Callback',
            'logged_at' => now(),
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('Notification Preferences');
        $response->assertSee('Persistent Vehicles');
        $response->assertSee('PLT100');
        $response->assertSee('Status update');
        $response->assertSee('Callback');
    }

    public function test_customer_detail_update_saves_preferences(): void
    {
        $customer = Customer::create([
            'first_name' => 'Alice',
            'last_name' => 'Wonderland',
            'phone' => '5550001111',
            'is_active' => true,
            'notification_preferences' => Customer::DEFAULT_NOTIFICATION_PREFERENCES,
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->put(route('customers.update', $customer), [
                'first_name' => 'Alicia',
                'last_name' => 'Wonderland',
                'phone' => '5550001111',
                'is_active' => '1',
                'notification_preferences' => [
                    'status_updates' => '1',
                    'signature_requests' => '1',
                ],
            ]);

        $response->assertRedirect(route('customers.show', $customer));

        $customer->refresh();
        $this->assertSame('Alicia', $customer->first_name);
        $this->assertTrue($customer->notification_preferences['status_updates']);
        $this->assertFalse($customer->notification_preferences['location_requests']);
        $this->assertTrue($customer->notification_preferences['signature_requests']);
        $this->assertFalse($customer->notification_preferences['marketing']);
    }
}