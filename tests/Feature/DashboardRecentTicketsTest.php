<?php

namespace Tests\Feature;

use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardRecentTicketsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_recent_tickets_link_to_ticket_show_page(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Driver',
            'phone' => '5551239876',
            'is_active' => true,
        ]);

        $category = CatalogCategory::create([
            'name' => 'Services',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $item = CatalogItem::create([
            'catalog_category_id' => $category->id,
            'name' => 'Jump Start',
            'base_cost' => 95.00,
            'unit' => 'each',
            'pricing_type' => 'fixed',
            'sort_order' => 1,
            'is_active' => true,
            'core_amount' => 0,
        ]);

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $customer->id,
            'catalog_item_id' => $item->id,
            'status' => 'new',
            'location' => 'Main St',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('service-requests.show', $serviceRequest), false);
        $response->assertSee('>#' . $serviceRequest->id . '<', false);
        $response->assertSee('title="View ticket"', false);
    }
}