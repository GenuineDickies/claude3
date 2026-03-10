<?php

namespace Tests\Feature;

use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ServiceRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedUser(): User
    {
        return User::factory()->create();
    }

    /**
     * Common valid payload for store requests.
     */
    private function validPayload(array $overrides = []): array
    {
        $category = CatalogCategory::first() ?? CatalogCategory::create([
            'name' => 'Services',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $catalogItem = CatalogItem::where('catalog_category_id', $category->id)->first()
            ?? CatalogItem::create([
                'catalog_category_id' => $category->id,
                'name' => 'Flat Tire Change',
                'base_cost' => 75.00,
                'unit' => 'each',
                'pricing_type' => 'fixed',
                'sort_order' => 1,
                'is_active' => true,
            ]);

        return array_merge([
            'first_name' => 'John',
            'last_name' => 'Smith',
            'phone' => '(555) 999-0000',
            'customer_action' => 'create_new',
            'vehicle_year' => '2024',
            'vehicle_make' => 'Toyota',
            'vehicle_model' => 'Camry',
            'vehicle_color' => 'Silver',
            'catalog_item_id' => $catalogItem->id,
            'quoted_price' => '75.00',
            'street_address' => '123 Main St',
            'city' => 'Tampa',
            'state' => 'FL',
            'notes' => 'Flat tire, driver side rear',
        ], $overrides);
    }

    // ------------------------------------------------------------------
    // Customer search (GET /api/customers/search)
    // ------------------------------------------------------------------

    public function test_customer_search_returns_null_without_phone(): void
    {
        $response = $this->actingAs($this->authenticatedUser())
            ->getJson('/api/customers/search');

        $response->assertOk()
            ->assertJson(['customer' => null]);
    }

    public function test_customer_search_finds_active_customer_by_phone(): void
    {
        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '5551234567',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->getJson('/api/customers/search?phone=(555) 123-4567');

        $response->assertOk()
            ->assertJsonPath('customer.id', $customer->id)
            ->assertJsonPath('vehicle', null);
    }

    public function test_customer_search_ignores_inactive_customer(): void
    {
        Customer::create([
            'first_name' => 'Old',
            'last_name' => 'Record',
            'phone' => '5551234567',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->getJson('/api/customers/search?phone=(555) 123-4567');

        $response->assertOk()
            ->assertJson(['customer' => null]);
    }

    // ------------------------------------------------------------------
    // Store — create_new flow
    // ------------------------------------------------------------------

    public function test_store_creates_new_customer_and_service_request(): void
    {
        $response = $this->actingAs($this->authenticatedUser())
            ->post(route('service-requests.store'), $this->validPayload());

        $sr = ServiceRequest::first();
        $response->assertRedirect(route('service-requests.show', $sr));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('customers', [
            'first_name' => 'John',
            'last_name' => 'Smith',
            'phone' => '5559990000',
            'is_active' => true,
        ]);

        $this->assertDatabaseCount('service_requests', 1);
        $sr = ServiceRequest::first();
        $this->assertSame('new', $sr->status);
        $this->assertNull($sr->vehicle_id);
        $this->assertSame('2024', $sr->vehicle_year);
        $this->assertSame('Toyota', $sr->vehicle_make);
        $this->assertSame('Camry', $sr->vehicle_model);
        $this->assertNotNull($sr->catalog_item_id);
        $this->assertSame('75.00', $sr->quoted_price);
        $this->assertSame('123 Main St, Tampa, FL', $sr->location);
    }

    public function test_store_deactivates_old_customer_when_creating_new(): void
    {
        $old = Customer::create([
            'first_name' => 'Old',
            'last_name' => 'Owner',
            'phone' => '5559990000',
            'is_active' => true,
        ]);

        $this->actingAs($this->authenticatedUser())
            ->post(route('service-requests.store'), $this->validPayload([
                'first_name' => 'New',
                'last_name' => 'Owner',
                'phone' => '(555) 999-0000',
                'customer_action' => 'create_new',
            ]));

        $this->assertFalse($old->fresh()->is_active);
        $this->assertDatabaseHas('customers', [
            'first_name' => 'New',
            'last_name' => 'Owner',
            'phone' => '5559990000',
            'is_active' => true,
        ]);
    }

    // ------------------------------------------------------------------
    // Store — use_existing flow
    // ------------------------------------------------------------------

    public function test_store_updates_existing_customer_and_creates_service_request(): void
    {
        $existing = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '5551234567',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->post(route('service-requests.store'), $this->validPayload([
                'first_name' => 'Janet',
                'last_name' => 'Doe',
                'phone' => '(555) 123-4567',
                'customer_action' => 'use_existing',
            ]));

        $response->assertRedirect(route('service-requests.show', ServiceRequest::first()));
        $existing->refresh();
        $this->assertSame('Janet', $existing->first_name);
        $this->assertDatabaseCount('service_requests', 1);
    }

    public function test_store_falls_back_to_create_when_existing_not_found(): void
    {
        // No customer exists — "use_existing" should still create one
        $this->actingAs($this->authenticatedUser())
            ->post(route('service-requests.store'), $this->validPayload([
                'first_name' => 'Ghost',
                'last_name' => 'Customer',
                'phone' => '(555) 000-0000',
                'customer_action' => 'use_existing',
            ]));

        $this->assertDatabaseHas('customers', [
            'first_name' => 'Ghost',
            'last_name' => 'Customer',
            'phone' => '5550000000',
            'is_active' => true,
        ]);
        $this->assertDatabaseCount('service_requests', 1);
    }

    // ------------------------------------------------------------------
    // Validation
    // ------------------------------------------------------------------

    public function test_store_rejects_missing_required_fields(): void
    {
        $response = $this->actingAs($this->authenticatedUser())
            ->post(route('service-requests.store'), []);

        $response->assertSessionHasErrors([
            'first_name', 'last_name', 'phone', 'customer_action',
            'vehicle_year', 'vehicle_make', 'vehicle_model',
            'catalog_item_id', 'quoted_price',
        ]);
    }

    public function test_store_rejects_invalid_customer_action(): void
    {
        $response = $this->actingAs($this->authenticatedUser())
            ->post(route('service-requests.store'), [
                'first_name' => 'Test',
                'last_name' => 'User',
                'phone' => '(555) 111-2222',
                'customer_action' => 'invalid_value',
            ]);

        $response->assertSessionHasErrors(['customer_action']);
    }

    // ------------------------------------------------------------------
    // Phone normalisation
    // ------------------------------------------------------------------

    public function test_phone_is_stored_as_digits_only(): void
    {
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name' => 'Phone',
            'phone' => '(800) 555-1234',
            'is_active' => true,
        ]);

        $this->assertSame('8005551234', $customer->phone);
    }

    // ------------------------------------------------------------------
    // Page loads
    // ------------------------------------------------------------------

    public function test_create_page_loads(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('service-requests.create'));

        $response->assertOk();
        $response->assertSee('New Service Request');
        $response->assertSee('Street');
        $response->assertSee('City');
        $response->assertSee('State');
        $response->assertDontSee('Location / Address');
    }

    public function test_store_allows_location_fields_to_remain_blank(): void
    {
        $response = $this->actingAs($this->authenticatedUser())
            ->post(route('service-requests.store'), $this->validPayload([
                'street_address' => '',
                'city' => '',
                'state' => '',
            ]));

        $response->assertRedirect(route('service-requests.show', ServiceRequest::first()));
        $this->assertNull(ServiceRequest::first()->location);
    }

    public function test_create_page_includes_customer_search_url(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('service-requests.create'));

        $response->assertOk();
        $response->assertSee('data-customer-search-url="/api/customers/search"', false);
    }

    // ------------------------------------------------------------------
    // Index page
    // ------------------------------------------------------------------

    public function test_index_page_loads(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('service-requests.index'));

        $response->assertOk();
        $response->assertSee('All Tickets');
    }

    public function test_index_shows_service_requests(): void
    {
        $this->withoutVite();

        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '5550001111',
            'is_active' => true,
        ]);

        $sr1 = ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'new']);
        $sr2 = ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'completed']);

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('service-requests.index'));

        $response->assertOk();
        $response->assertSee('#' . $sr1->id);
        $response->assertSee('#' . $sr2->id);
    }

    public function test_index_filters_by_status(): void
    {
        $this->withoutVite();

        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '5550001111',
            'is_active' => true,
        ]);

        $srNew = ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'new']);
        $srDone = ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'completed']);

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('service-requests.index', ['status' => 'new']));

        $response->assertOk();
        $response->assertSee('#' . $srNew->id);
        $response->assertDontSee('#' . $srDone->id);
    }

    public function test_index_searches_by_customer_name(): void
    {
        $this->withoutVite();

        $alice = Customer::create([
            'first_name' => 'Alice',
            'last_name' => 'Wonderland',
            'phone' => '5550002222',
            'is_active' => true,
        ]);

        $sr = ServiceRequest::create(['customer_id' => $alice->id, 'status' => 'new']);

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('service-requests.index', ['search' => 'Alice']));

        $response->assertOk();
        $response->assertSee('#' . $sr->id);
    }

    // ------------------------------------------------------------------
    // Show page
    // ------------------------------------------------------------------

    public function test_show_page_loads(): void
    {
        $this->withoutVite();

        $customer = Customer::create([
            'first_name' => 'Bob',
            'last_name' => 'Builder',
            'phone' => '5550003333',
            'is_active' => true,
        ]);

        $sr = ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'new']);

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('service-requests.show', $sr));

        $response->assertOk();
        $response->assertSee('Service Request #' . $sr->id);
    }
}
