<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test to replicate exact form submission from the web interface.
 */
final class CatalogItemFormSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_submission_with_empty_account_strings(): void
    {
        $user = User::factory()->create();
        
        $category = CatalogCategory::create([
            'name' => 'Services',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        // This replicates EXACTLY what the form sends when no accounts are selected
        $formData = [
            'name' => 'Test Service',
            'description' => '',  // Empty string from textarea
            'base_cost' => '75.00',
            'unit' => 'each',
            'pricing_type' => 'fixed',
            'sort_order' => '0',
            'is_active' => '1',
            'revenue_account_id' => '',  // Empty string from select with value=""
            'cogs_account_id' => '',     // Empty string from select with value=""
            'core_required' => '0',
            'core_amount' => '',         // Empty string from input
            'taxable' => '1',
        ];

        $response = $this->actingAs($user)
            ->post(route('catalog.items.store'), $formData);

        // Dump any errors
        if ($response->status() !== 302) {
            dump('Response Status:', $response->status());
            dump('Session Errors:', session('errors'));
            dump('Response Content:', $response->getContent());
        }

        $response->assertStatus(302);
        $this->assertDatabaseHas('catalog_items', [
            'name' => 'Test Service',
        ]);
    }

    public function test_all_checkboxes_unchecked(): void
    {
        $user = User::factory()->create();
        
        $category = CatalogCategory::create([
            'name' => 'Services',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        // When checkboxes are UNCHECKED, hidden inputs send '0'
        $formData = [
            'name' => 'Inactive Untaxed Service',
            'description' => '',
            'base_cost' => '50.00',
            'unit' => 'hour',
            'pricing_type' => 'variable',
            'sort_order' => '5',
            'is_active' => '0',      // Unchecked
            'revenue_account_id' => '',
            'cogs_account_id' => '',
            'core_required' => '0',  // Unchecked
            'core_amount' => '',
            'taxable' => '0',        // Unchecked
        ];

        $response = $this->actingAs($user)
            ->post(route('catalog.items.store'), $formData);

        if ($response->status() !== 302) {
            dump('Response Status:', $response->status());
            dump('Session Errors:', session('errors'));
        }

        $response->assertStatus(302);
        
        $item = CatalogItem::where('name', 'Inactive Untaxed Service')->first();
        $this->assertFalse($item->is_active);
        $this->assertFalse($item->core_required);
        $this->assertFalse($item->taxable);
    }

    public function test_with_populated_accounts(): void
    {
        $user = User::factory()->create();
        
        $category = CatalogCategory::create([
            'name' => 'Services',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        // Create accounts first
        $revenueAccount = Account::firstOrCreate(
            ['code' => '4000', 'scope' => 'general'],
            [
                'name' => 'Service Income',
                'type' => 'revenue',
                'is_active' => true,
            ]
        );

        $cogsAccount = Account::firstOrCreate(
            ['code' => '5000', 'scope' => 'general'],
            [
                'name' => 'Parts & Supplies Used',
                'type' => 'cogs',
                'is_active' => true,
            ]
        );

        $formData = [
            'name' => 'Service with Accounts',
            'description' => 'Test description',
            'base_cost' => '100.00',
            'unit' => 'each',
            'pricing_type' => 'fixed',
            'sort_order' => '1',
            'is_active' => '1',
            'revenue_account_id' => (string)$revenueAccount->id,  // Form sends as string
            'cogs_account_id' => (string)$cogsAccount->id,        // Form sends as string
            'core_required' => '1',
            'core_amount' => '25.00',
            'taxable' => '1',
        ];

        $response = $this->actingAs($user)
            ->post(route('catalog.items.store'), $formData);

        if ($response->status() !== 302) {
            dump('Response Status:', $response->status());
            dump('Session Errors:', session('errors'));
            dump('Exception:', $response->exception);
        }

        $response->assertStatus(302);
        
        $item = CatalogItem::where('name', 'Service with Accounts')->first();
        $this->assertEquals($revenueAccount->id, $item->revenue_account_id);
        $this->assertEquals($cogsAccount->id, $item->cogs_account_id);
    }
}
