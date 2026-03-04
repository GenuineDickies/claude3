<?php

namespace Tests\Feature;

use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Models\StateTaxRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class EstimateTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedUser(): User
    {
        return User::factory()->create();
    }

    private function createServiceRequest(array $attrs = []): ServiceRequest
    {
        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '5551234567',
            'is_active' => true,
        ]);

        $category = CatalogCategory::create([
            'name' => 'Services',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $catalogItem = CatalogItem::create([
            'catalog_category_id' => $category->id,
            'name' => 'Flat Tire Change',
            'base_cost' => 75.00,
            'unit' => 'each',
            'pricing_type' => 'fixed',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        return ServiceRequest::create(array_merge([
            'customer_id' => $customer->id,
            'catalog_item_id' => $catalogItem->id,
            'quoted_price' => 75.00,
            'status' => 'new',
        ], $attrs));
    }

    private function seedTaxRate(string $code, string $name, float $rate): void
    {
        StateTaxRate::create([
            'state_code' => $code,
            'state_name' => $name,
            'tax_rate' => $rate,
        ]);
    }

    // ------------------------------------------------------------------
    // Create page loads
    // ------------------------------------------------------------------

    public function test_create_estimate_page_loads(): void
    {
        $sr = $this->createServiceRequest();

        $response = $this->actingAs($this->authenticatedUser())
            ->get("/service-requests/{$sr->id}/estimates/create");

        $response->assertOk();
        $response->assertViewIs('estimates.create');
        $response->assertViewHas('serviceRequest');
    }

    // ------------------------------------------------------------------
    // State auto-detection from text address
    // ------------------------------------------------------------------

    public function test_state_detected_from_address_with_zip(): void
    {
        $this->seedTaxRate('TX', 'Texas', 6.25);

        $sr = $this->createServiceRequest([
            'location' => '123 Main St, Dallas, TX 75201',
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->get("/service-requests/{$sr->id}/estimates/create");

        $response->assertOk();
        $response->assertViewHas('stateCode', 'TX');
        $response->assertViewHas('taxRate', 6.25);
        $response->assertViewHas('stateAutoDetected', true);
    }

    public function test_state_detected_from_address_with_state_name(): void
    {
        $this->seedTaxRate('CA', 'California', 7.25);

        $sr = $this->createServiceRequest([
            'location' => '456 Sunset Blvd, Los Angeles, California',
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->get("/service-requests/{$sr->id}/estimates/create");

        $response->assertOk();
        $response->assertViewHas('stateCode', 'CA');
        $response->assertViewHas('taxRate', 7.25);
        $response->assertViewHas('stateAutoDetected', true);
    }

    // ------------------------------------------------------------------
    // State auto-detection from lat/lng (reverse geocode)
    // ------------------------------------------------------------------

    public function test_state_detected_from_latlng_when_location_is_null(): void
    {
        $this->seedTaxRate('OR', 'Oregon', 0);

        // Mock file_get_contents via a stream wrapper would be complex;
        // instead, use a partial mock on the controller.
        $googleResponse = json_encode([
            'status' => 'OK',
            'results' => [[
                'formatted_address' => '123 SE Main St, Portland, OR 97214, USA',
                'address_components' => [
                    ['long_name' => '123', 'short_name' => '123', 'types' => ['street_number']],
                    ['long_name' => 'SE Main St', 'short_name' => 'SE Main St', 'types' => ['route']],
                    ['long_name' => 'Portland', 'short_name' => 'Portland', 'types' => ['locality']],
                    ['long_name' => 'Oregon', 'short_name' => 'OR', 'types' => ['administrative_area_level_1']],
                    ['long_name' => 'United States', 'short_name' => 'US', 'types' => ['country']],
                    ['long_name' => '97214', 'short_name' => '97214', 'types' => ['postal_code']],
                ],
            ]],
        ]);

        // Ensure a Google Maps API key exists
        Setting::setValue('google_maps_api_key', 'test-key');

        // Intercept the HTTP call by overriding file_get_contents via a stream wrapper
        // We'll use a simpler approach: override via namespace function
        // Instead, we use Http::fake or refactor. For now, test via the controller directly.

        $sr = $this->createServiceRequest([
            'location' => null,
            'latitude' => '45.5347511',
            'longitude' => '-122.6575223',
        ]);

        // Mock the external Google API call via Http::fake
        Http::fake([
            'maps.googleapis.com/*' => Http::response(json_decode($googleResponse, true)),
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->get("/service-requests/{$sr->id}/estimates/create");

        $response->assertOk();
        $response->assertViewHas('stateCode', 'OR');
        $response->assertViewHas('taxRate', 0.0);
        $response->assertViewHas('stateAutoDetected', true);

        // Location should be backfilled
        $sr->refresh();
        $this->assertNotNull($sr->location);
        $this->assertStringContainsString('Portland', $sr->location);
    }

    // ------------------------------------------------------------------
    // No state detected — falls back to manual
    // ------------------------------------------------------------------

    public function test_no_state_when_no_location_and_no_coordinates(): void
    {
        $sr = $this->createServiceRequest([
            'location' => null,
            'latitude' => null,
            'longitude' => null,
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->get("/service-requests/{$sr->id}/estimates/create");

        $response->assertOk();
        $response->assertViewHas('stateCode', null);
        $response->assertViewHas('taxRate', null);
        $response->assertViewHas('stateAutoDetected', false);
    }

    public function test_no_state_when_no_api_key_and_no_location_text(): void
    {
        // Ensure no Google Maps API key
        Setting::where('key', 'google_maps_api_key')->delete();
        config(['services.google_maps.api_key' => '']);

        $sr = $this->createServiceRequest([
            'location' => null,
            'latitude' => '45.5347511',
            'longitude' => '-122.6575223',
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->get("/service-requests/{$sr->id}/estimates/create");

        $response->assertOk();
        $response->assertViewHas('stateCode', null);
        $response->assertViewHas('stateAutoDetected', false);
    }

    // ------------------------------------------------------------------
    // Tax rate API endpoint
    // ------------------------------------------------------------------

    public function test_tax_rate_api_returns_rate(): void
    {
        $this->seedTaxRate('TX', 'Texas', 6.25);

        $response = $this->actingAs($this->authenticatedUser())
            ->getJson('/api/state-tax-rate/TX');

        $response->assertOk()
            ->assertJson(['rate' => 6.25]);
    }

    public function test_tax_rate_api_returns_null_for_unknown_state(): void
    {
        $response = $this->actingAs($this->authenticatedUser())
            ->getJson('/api/state-tax-rate/ZZ');

        $response->assertOk()
            ->assertJson(['rate' => null]);
    }

    // ------------------------------------------------------------------
    // Store estimate
    // ------------------------------------------------------------------

    public function test_store_creates_estimate_with_items(): void
    {
        $sr = $this->createServiceRequest();

        $category = CatalogCategory::create([
            'name' => 'Labor',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $catalogItem = CatalogItem::create([
            'catalog_category_id' => $category->id,
            'name' => 'Standard Labor',
            'base_cost' => 85.00,
            'unit' => 'hour',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->post("/service-requests/{$sr->id}/estimates", [
                'state_code' => 'TX',
                'tax_rate' => 6.25,
                'notes' => 'Test estimate',
                'items' => [
                    [
                        'catalog_item_id' => $catalogItem->id,
                        'name' => 'Standard Labor',
                        'description' => '1 hour labor',
                        'unit_price' => 85.00,
                        'quantity' => 2,
                        'unit' => 'hour',
                    ],
                ],
            ]);

        $response->assertRedirect();

        $estimate = Estimate::where('service_request_id', $sr->id)->first();
        $this->assertNotNull($estimate);
        $this->assertEquals('TX', $estimate->state_code);
        $this->assertEquals(6.25, (float) $estimate->tax_rate);
        $this->assertEquals(170.00, (float) $estimate->subtotal);
        $this->assertEquals(10.63, (float) $estimate->tax_amount);  // 170 * 6.25%
        $this->assertEquals(180.63, (float) $estimate->total);
        $this->assertCount(1, $estimate->items);
    }

    public function test_store_rejects_empty_items(): void
    {
        $sr = $this->createServiceRequest();

        $response = $this->actingAs($this->authenticatedUser())
            ->post("/service-requests/{$sr->id}/estimates", [
                'state_code' => 'TX',
                'tax_rate' => 6.25,
                'items' => [],
            ]);

        $response->assertSessionHasErrors('items');
    }

    // ------------------------------------------------------------------
    // Helper: mock Google Geocode API via stream wrapper
    // ------------------------------------------------------------------
}
