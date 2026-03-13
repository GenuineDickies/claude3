<?php

namespace Tests\Feature;

use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\User;
use App\Services\SmsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class WorkflowContextGuardrailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_self_consent_route_is_not_available(): void
    {
        $this->get('/sms/consent/example-token')->assertNotFound();
    }

    public function test_customer_location_request_is_blocked_without_verbal_consent_and_sends_no_sms(): void
    {
        $user = User::factory()->create();

        $category = CatalogCategory::create([
            'name' => 'Services',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $item = CatalogItem::create([
            'catalog_category_id' => $category->id,
            'name' => 'Flat Tire Change',
            'base_cost' => 75.00,
            'unit' => 'each',
            'pricing_type' => 'fixed',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $mock = Mockery::mock(SmsServiceInterface::class);
        $mock->shouldNotReceive('sendTemplate');
        $mock->shouldNotReceive('sendRaw');
        $mock->shouldNotReceive('sendRawWithLog');
        $this->app->instance(SmsServiceInterface::class, $mock);

        $response = $this->actingAs($user)
            ->post('/service-requests', [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'phone' => '(555) 999-0000',
                'customer_action' => 'create_new',
                'vehicle_year' => '2024',
                'vehicle_make' => 'Toyota',
                'vehicle_model' => 'Camry',
                'vehicle_color' => 'Silver',
                'catalog_item_id' => $item->id,
                'quoted_price' => '75.00',
                'street_address' => '123 Main St',
                'city' => 'Tampa',
                'state' => 'FL',
                'notes' => 'Flat tire, driver side rear',
                'send_location_request' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('warning', 'Customer has not opted in to SMS. Record verbal consent before sending location or status text messages.');
    }
}