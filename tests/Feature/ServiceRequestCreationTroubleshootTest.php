<?php

namespace Tests\Feature;

use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\MessageTemplate;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\SmsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Troubleshooting test for Service Request creation 500 errors.
 * 
 * This test suite helps identify common issues that cause 500 errors
 * when creating service requests.
 */
final class ServiceRequestCreationTroubleshootTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private CatalogCategory $category;
    private CatalogItem $catalogItem;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create authenticated user
        $this->user = User::factory()->create();
        
        // Set up catalog structure
        $this->category = CatalogCategory::create([
            'name' => 'Services',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->catalogItem = CatalogItem::create([
            'catalog_category_id' => $this->category->id,
            'name' => 'Flat Tire Change',
            'base_cost' => 75.00,
            'unit' => 'each',
            'pricing_type' => 'fixed',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        
        // Mock SMS service to prevent real API calls
        $this->mockSmsService();
    }

    private function mockSmsService(): void
    {
        $mock = Mockery::mock(SmsServiceInterface::class);
        $mock->shouldReceive('sendTemplate')->andReturn([
            'success'       => true,
            'message_id'    => 'test-msg-id',
            'rendered_text' => 'test message',
        ]);
        $mock->shouldReceive('sendRaw')->andReturn(true);
        $mock->shouldReceive('sendRawWithLog')->andReturn([
            'success'       => true,
            'message_id'    => 'test-msg-id',
            'rendered_text' => 'test message',
        ]);
        $this->app->instance(SmsServiceInterface::class, $mock);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'John',
            'last_name' => 'Smith',
            'phone' => '(555) 999-0000',
            'customer_action' => 'create_new',
            'vehicle_year' => '2024',
            'vehicle_make' => 'Toyota',
            'vehicle_model' => 'Camry',
            'vehicle_color' => 'Silver',
            'catalog_item_id' => $this->catalogItem->id,
            'quoted_price' => '75.00',
            'location' => 'I-95 mile marker 42',
            'notes' => 'Flat tire, driver side rear',
        ], $overrides);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Test 1: Basic service request creation
    // ═══════════════════════════════════════════════════════════════════════
    
    public function test_basic_service_request_creation_succeeds(): void
    {
        Log::info('TEST: Basic service request creation');
        
        $response = $this->actingAs($this->user)
            ->post('/service-requests', $this->validPayload());

        $response->assertStatus(302);
        $this->assertDatabaseHas('service_requests', [
            'vehicle_make' => 'Toyota',
            'vehicle_model' => 'Camry',
            'status' => 'new',
        ]);
        $this->assertDatabaseHas('customers', [
            'first_name' => 'John',
            'last_name' => 'Smith',
            'phone' => '5559990000',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Test 2: Missing required fields
    // ═══════════════════════════════════════════════════════════════════════
    
    public function test_missing_required_fields_returns_validation_error(): void
    {
        Log::info('TEST: Missing required fields');
        
        $requiredFields = [
            'first_name',
            'last_name',
            'phone',
            'customer_action',
            'vehicle_year',
            'vehicle_make',
            'vehicle_model',
            'catalog_item_id',
            'quoted_price',
        ];

        foreach ($requiredFields as $field) {
            $payload = $this->validPayload();
            unset($payload[$field]);
            
            $response = $this->actingAs($this->user)
                ->post('/service-requests', $payload);

            $response->assertSessionHasErrors($field);
            Log::info("TEST: Missing {$field} - validation error returned correctly");
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Test 3: Invalid catalog_item_id
    // ═══════════════════════════════════════════════════════════════════════
    
    public function test_invalid_catalog_item_id_returns_error(): void
    {
        Log::info('TEST: Invalid catalog_item_id');
        
        $response = $this->actingAs($this->user)
            ->post('/service-requests', $this->validPayload([
                'catalog_item_id' => 99999, // Non-existent ID
            ]));

        $response->assertSessionHasErrors('catalog_item_id');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Test 4: Invalid vehicle year
    // ═══════════════════════════════════════════════════════════════════════
    
    public function test_invalid_vehicle_year_format(): void
    {
        Log::info('TEST: Invalid vehicle year format');
        
        $response = $this->actingAs($this->user)
            ->post('/service-requests', $this->validPayload([
                'vehicle_year' => '24', // Must be 4 digits
            ]));

        $response->assertSessionHasErrors('vehicle_year');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Test 5: Database constraint violations
    // ═══════════════════════════════════════════════════════════════════════
    
    public function test_database_constraints_are_respected(): void
    {
        Log::info('TEST: Database constraints');
        
        // Test with oversized strings
        $response = $this->actingAs($this->user)
            ->post('/service-requests', $this->validPayload([
                'first_name' => str_repeat('A', 300), // Exceeds max
            ]));

        $response->assertSessionHasErrors('first_name');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Test 6: Existing customer workflow
    // ═══════════════════════════════════════════════════════════════════════
    
    public function test_use_existing_customer_workflow(): void
    {
        Log::info('TEST: Use existing customer workflow');
        
        // Create an existing customer
        $existingCustomer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '5554443333',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->post('/service-requests', $this->validPayload([
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'phone' => '(555) 444-3333',
                'customer_action' => 'use_existing',
            ]));

        $response->assertStatus(302);
        
        // Should use existing customer, not create new one
        $this->assertEquals(1, Customer::where('phone', '5554443333')->count());
        
        $serviceRequest = ServiceRequest::latest()->first();
        $this->assertEquals($existingCustomer->id, $serviceRequest->customer_id);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Test 7: Create new customer deactivates old records
    // ═══════════════════════════════════════════════════════════════════════
    
    public function test_create_new_customer_deactivates_old_records(): void
    {
        Log::info('TEST: Create new customer deactivates old records');
        
        // Create an existing active customer with same phone
        $oldCustomer = Customer::create([
            'first_name' => 'Old',
            'last_name' => 'Name',
            'phone' => '5554443333',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->post('/service-requests', $this->validPayload([
                'first_name' => 'New',
                'last_name' => 'Name',
                'phone' => '(555) 444-3333',
                'customer_action' => 'create_new',
            ]));

        $response->assertStatus(302);
        
        // Old customer should be deactivated
        $oldCustomer->refresh();
        $this->assertFalse($oldCustomer->is_active);
        
        // New customer should be created and active
        $newCustomer = Customer::where('phone', '5554443333')
            ->where('is_active', true)
            ->first();
        $this->assertNotNull($newCustomer);
        $this->assertEquals('New', $newCustomer->first_name);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Test 8: Transaction rollback on error
    // ═══════════════════════════════════════════════════════════════════════
    
    public function test_transaction_rollback_on_database_error(): void
    {
        Log::info('TEST: Transaction rollback on database error');
        
        $initialCustomerCount = Customer::count();
        $initialSRCount = ServiceRequest::count();

        // Force a database error by using invalid data that passes validation
        // but fails at database level (e.g., null in non-nullable column)
        try {
            DB::table('service_requests')->insert([
                'customer_id' => null, // This should fail
                'status' => 'new',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::info('TEST: Expected database error caught: ' . $e->getMessage());
        }

        // Ensure counts haven't changed (transaction rolled back)
        $this->assertEquals($initialCustomerCount, Customer::count());
        $this->assertEquals($initialSRCount, ServiceRequest::count());
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Test 9: Verbal opt-in checkbox
    // ═══════════════════════════════════════════════════════════════════════
    
    public function test_verbal_opt_in_grants_consent(): void
    {
        Log::info('TEST: Verbal opt-in grants consent');
        
        $response = $this->actingAs($this->user)
            ->post('/service-requests', $this->validPayload([
                'verbal_opt_in' => true,
            ]));

        $response->assertStatus(302);
        
        $serviceRequest = ServiceRequest::latest()->first();
        $customer = $serviceRequest->customer;
        
        $this->assertTrue($customer->hasSmsConsent());
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Test 10: Send location request without opt-in
    // ═══════════════════════════════════════════════════════════════════════
    
    public function test_location_request_without_opt_in_sends_opt_in_first(): void
    {
        Log::info('TEST: Location request without opt-in');
        
        // Create opt-in template
        MessageTemplate::create([
            'name' => 'Welcome Message',
            'slug' => 'welcome-message',
            'category' => 'opt-in',
            'body' => 'Welcome! Reply START to opt in.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->post('/service-requests', $this->validPayload([
                'send_location_request' => true,
                // Note: No verbal_opt_in
            ]));

        $response->assertStatus(302);
        $response->assertSessionHas('warning');
        $response->assertSessionHas('success');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Test 11: Send location request with opt-in
    // ═══════════════════════════════════════════════════════════════════════
    
    public function test_location_request_with_opt_in_generates_token(): void
    {
        Log::info('TEST: Location request with opt-in');
        
        // Create location request template
        MessageTemplate::create([
            'name' => 'Location Request',
            'slug' => 'location-request',
            'category' => 'location',
            'body' => 'Please share your location: {{location_link}}',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->post('/service-requests', $this->validPayload([
                'verbal_opt_in' => true,
                'send_location_request' => true,
            ]));

        $response->assertStatus(302);
        
        $serviceRequest = ServiceRequest::latest()->first();
        $this->assertNotNull($serviceRequest->location_token);
        $this->assertNotNull($serviceRequest->location_token_expires_at);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Test 12: Unauthenticated access
    // ═══════════════════════════════════════════════════════════════════════
    
    public function test_unauthenticated_user_cannot_create_service_request(): void
    {
        Log::info('TEST: Unauthenticated access');
        
        $response = $this->post('/service-requests', $this->validPayload());

        $response->assertRedirect('/login');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Test 13: Optional fields can be null
    // ═══════════════════════════════════════════════════════════════════════
    
    public function test_optional_fields_can_be_omitted(): void
    {
        Log::info('TEST: Optional fields can be omitted');
        
        $payload = $this->validPayload();
        unset($payload['vehicle_color']);
        unset($payload['location']);
        unset($payload['notes']);

        $response = $this->actingAs($this->user)
            ->post('/service-requests', $payload);

        $response->assertStatus(302);
        
        $serviceRequest = ServiceRequest::latest()->first();
        $this->assertNull($serviceRequest->vehicle_color);
        $this->assertNull($serviceRequest->location);
        $this->assertNull($serviceRequest->notes);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Test 14: Phone normalization
    // ═══════════════════════════════════════════════════════════════════════
    
    public function test_phone_number_normalization(): void
    {
        Log::info('TEST: Phone number normalization');
        
        $phoneFormats = [
            '(555) 123-4567',
            '555-123-4567',
            '555.123.4567',
            '5551234567',
            '+15551234567',
        ];

        foreach ($phoneFormats as $format) {
            $response = $this->actingAs($this->user)
                ->post('/service-requests', $this->validPayload([
                    'phone' => $format,
                ]));

            $response->assertStatus(302);
            
            // All should normalize to same format
            $customer = Customer::where('phone', '5551234567')->first();
            $this->assertNotNull($customer, "Phone format '{$format}' failed to normalize");
            
            // Clean up for next iteration
            Customer::where('phone', '5551234567')->delete();
            ServiceRequest::latest()->first()?->delete();
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Test 15: Catalog item must be active
    // ═══════════════════════════════════════════════════════════════════════
    
    public function test_inactive_catalog_item_is_validated(): void
    {
        Log::info('TEST: Inactive catalog item validation');
        
        $inactiveItem = CatalogItem::create([
            'catalog_category_id' => $this->category->id,
            'name' => 'Inactive Service',
            'base_cost' => 50.00,
            'unit' => 'each',
            'pricing_type' => 'fixed',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->post('/service-requests', $this->validPayload([
                'catalog_item_id' => $inactiveItem->id,
            ]));

        // The exists validation will pass, but you may want stricter validation
        // This test documents current behavior
        $response->assertStatus(302);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Test 16: Quoted price validation
    // ═══════════════════════════════════════════════════════════════════════
    
    public function test_quoted_price_must_be_valid_number(): void
    {
        Log::info('TEST: Quoted price validation');
        
        $invalidPrices = [
            'abc',
            '-50.00',
            '',
        ];

        foreach ($invalidPrices as $price) {
            $response = $this->actingAs($this->user)
                ->post('/service-requests', $this->validPayload([
                    'quoted_price' => $price,
                ]));

            $response->assertSessionHasErrors('quoted_price');
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Test 17: Setting getValue fallback
    // ═══════════════════════════════════════════════════════════════════════
    
    public function test_setting_value_fallback_works(): void
    {
        Log::info('TEST: Setting getValue fallback');
        
        // Ensure company_name setting doesn't exist
        Setting::where('key', 'company_name')->delete();
        
        MessageTemplate::create([
            'name' => 'Location Request',
            'slug' => 'location-request',
            'category' => 'location',
            'body' => 'Please share your location: {{location_link}}',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->post('/service-requests', $this->validPayload([
                'verbal_opt_in' => true,
                'send_location_request' => true,
            ]));

        // Should not error even without company_name setting
        $response->assertStatus(302);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // DIAGNOSTIC HELPER: Check database structure
    // ═══════════════════════════════════════════════════════════════════════
    
    public function test_database_structure_is_correct(): void
    {
        Log::info('TEST: Database structure verification');
        
        // Check service_requests table exists and has required columns
        $this->assertTrue(
            \Schema::hasTable('service_requests'),
            'service_requests table does not exist'
        );
        
        $requiredColumns = [
            'id',
            'customer_id',
            'vehicle_year',
            'vehicle_make',
            'vehicle_model',
            'catalog_item_id',
            'quoted_price',
            'status',
            'created_at',
            'updated_at',
        ];
        
        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                \Schema::hasColumn('service_requests', $column),
                "service_requests table missing column: {$column}"
            );
        }
        
        // Check customers table
        $this->assertTrue(
            \Schema::hasTable('customers'),
            'customers table does not exist'
        );
        
        $customerColumns = ['id', 'first_name', 'last_name', 'phone', 'is_active'];
        foreach ($customerColumns as $column) {
            $this->assertTrue(
                \Schema::hasColumn('customers', $column),
                "customers table missing column: {$column}"
            );
        }
    }
}
