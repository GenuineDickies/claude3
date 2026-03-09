<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Troubleshooting test for Catalog Item (Service) creation 500 errors.
 */
final class CatalogItemCreationTroubleshootTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private CatalogCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        $this->category = CatalogCategory::create([
            'name' => 'Services',
            'sort_order' => 0,
            'is_active' => true,
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Flat Tire Change',
            'description' => 'Change a flat tire on-site',
            'base_cost' => '75.00',
            'unit' => 'each',
            'pricing_type' => 'fixed',
            'sort_order' => 1,
            'is_active' => true,
            'taxable' => true,
        ], $overrides);
    }

    public function test_basic_catalog_item_creation_succeeds(): void
    {
        Log::info('TEST: Basic catalog item (service) creation');
        
        $response = $this->actingAs($this->user)
            ->post(route('catalog.items.store'), $this->validPayload());

        $response->assertStatus(302);
        $response->assertRedirect(route('catalog.index'));
        
        $this->assertDatabaseHas('catalog_items', [
            'name' => 'Flat Tire Change',
            'base_cost' => '75.00',
        ]);
    }

    public function test_missing_required_fields_returns_validation_error(): void
    {
        Log::info('TEST: Missing required fields for catalog item');
        
        $requiredFields = ['name', 'base_cost', 'unit', 'pricing_type'];

        foreach ($requiredFields as $field) {
            $payload = $this->validPayload();
            unset($payload[$field]);
            
            $response = $this->actingAs($this->user)
                ->post(route('catalog.items.store'), $payload);

            $response->assertSessionHasErrors($field);
            Log::info("TEST: Missing {$field} - validation error returned correctly");
        }
    }

    public function test_invalid_pricing_type(): void
    {
        Log::info('TEST: Invalid pricing_type');
        
        $response = $this->actingAs($this->user)
            ->post(route('catalog.items.store'), $this->validPayload([
                'pricing_type' => 'invalid_type',
            ]));

        $response->assertSessionHasErrors('pricing_type');
    }

    public function test_invalid_unit(): void
    {
        Log::info('TEST: Invalid unit');
        
        $response = $this->actingAs($this->user)
            ->post(route('catalog.items.store'), $this->validPayload([
                'unit' => 'invalid_unit',
            ]));

        $response->assertSessionHasErrors('unit');
    }

    public function test_negative_base_cost_fails_validation(): void
    {
        Log::info('TEST: Negative base cost');
        
        $response = $this->actingAs($this->user)
            ->post(route('catalog.items.store'), $this->validPayload([
                'base_cost' => '-50.00',
            ]));

        $response->assertSessionHasErrors('base_cost');
    }

    public function test_with_revenue_account(): void
    {
        Log::info('TEST: Creating catalog item with revenue account');
        
        $revenueAccount = Account::firstOrCreate(
            ['code' => '4000', 'scope' => 'general'],
            [
                'name' => 'Service Income',
                'type' => 'revenue',
                'is_active' => true,
            ]
        );

        $response = $this->actingAs($this->user)
            ->post(route('catalog.items.store'), $this->validPayload([
                'revenue_account_id' => $revenueAccount->id,
            ]));

        $response->assertStatus(302);
        
        $item = CatalogItem::where('name', 'Flat Tire Change')->first();
        $this->assertEquals($revenueAccount->id, $item->revenue_account_id);
    }

    public function test_with_cogs_account(): void
    {
        Log::info('TEST: Creating catalog item with COGS account');
        
        $cogsAccount = Account::firstOrCreate(
            ['code' => '5000', 'scope' => 'general'],
            [
                'name' => 'Parts & Supplies Used',
                'type' => 'cogs',
                'is_active' => true,
            ]
        );

        $response = $this->actingAs($this->user)
            ->post(route('catalog.items.store'), $this->validPayload([
                'cogs_account_id' => $cogsAccount->id,
            ]));

        $response->assertStatus(302);
        
        $item = CatalogItem::where('name', 'Flat Tire Change')->first();
        $this->assertEquals($cogsAccount->id, $item->cogs_account_id);
    }

    public function test_with_core_charge(): void
    {
        Log::info('TEST: Creating catalog item with core charge');
        
        $response = $this->actingAs($this->user)
            ->post(route('catalog.items.store'), $this->validPayload([
                'core_required' => true,
                'core_amount' => '25.00',
            ]));

        $response->assertStatus(302);
        
        $item = CatalogItem::where('name', 'Flat Tire Change')->first();
        $this->assertTrue($item->core_required);
        $this->assertEquals('25.00', $item->core_amount);
    }

    public function test_invalid_revenue_account_id(): void
    {
        Log::info('TEST: Invalid revenue_account_id');
        
        $response = $this->actingAs($this->user)
            ->post(route('catalog.items.store'), $this->validPayload([
                'revenue_account_id' => 99999,
            ]));

        $response->assertSessionHasErrors('revenue_account_id');
    }

    public function test_invalid_cogs_account_id(): void
    {
        Log::info('TEST: Invalid cogs_account_id');
        
        $response = $this->actingAs($this->user)
            ->post(route('catalog.items.store'), $this->validPayload([
                'cogs_account_id' => 99999,
            ]));

        $response->assertSessionHasErrors('cogs_account_id');
    }

    public function test_optional_fields_can_be_omitted(): void
    {
        Log::info('TEST: Optional fields can be omitted');
        
        $payload = [
            'name' => 'Basic Service',
            'base_cost' => '50.00',
            'unit' => 'each',
            'pricing_type' => 'fixed',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('catalog.items.store'), $payload);

        $response->assertStatus(302);
        
        $item = CatalogItem::where('name', 'Basic Service')->first();
        $this->assertNotNull($item);
        $this->assertNull($item->description);
        $this->assertNull($item->revenue_account_id);
        $this->assertNull($item->cogs_account_id);
    }

    public function test_all_pricing_types_are_valid(): void
    {
        Log::info('TEST: All pricing types');
        
        foreach (array_keys(CatalogItem::pricingTypes()) as $pricingType) {
            $response = $this->actingAs($this->user)
                ->post(route('catalog.items.store'), $this->validPayload([
                    'name' => "Service with {$pricingType}",
                    'pricing_type' => $pricingType,
                ]));

            $response->assertStatus(302);
            $this->assertDatabaseHas('catalog_items', [
                'name' => "Service with {$pricingType}",
                'pricing_type' => $pricingType,
            ]);
        }
    }

    public function test_all_units_are_valid(): void
    {
        Log::info('TEST: All units');
        
        foreach (array_keys(CatalogItem::units()) as $unit) {
            $response = $this->actingAs($this->user)
                ->post(route('catalog.items.store'), $this->validPayload([
                    'name' => "Service with {$unit}",
                    'unit' => $unit,
                ]));

            $response->assertStatus(302);
            $this->assertDatabaseHas('catalog_items', [
                'name' => "Service with {$unit}",
                'unit' => $unit,
            ]);
        }
    }

    public function test_unauthenticated_user_cannot_create_item(): void
    {
        Log::info('TEST: Unauthenticated access');
        
        $response = $this->post(
            route('catalog.items.store'),
            $this->validPayload()
        );

        $response->assertRedirect('/login');
    }

    public function test_database_structure_is_correct(): void
    {
        Log::info('TEST: Database structure verification for catalog_items');
        
        $this->assertTrue(\Schema::hasTable('catalog_items'));
        
        $requiredColumns = [
            'id',
            'catalog_category_id',
            'name',
            'base_cost',
            'unit',
            'pricing_type',
            'is_active',
            'created_at',
            'updated_at',
        ];
        
        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                \Schema::hasColumn('catalog_items', $column),
                "catalog_items table missing column: {$column}"
            );
        }
    }
}
