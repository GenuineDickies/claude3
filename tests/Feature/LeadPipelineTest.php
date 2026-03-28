<?php

namespace Tests\Feature;

use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\Lead;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LeadPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_index_requires_authentication(): void
    {
        $this->get(route('leads.index'))
            ->assertRedirect(route('login'));
    }

    public function test_lead_index_loads_for_authenticated_users(): void
    {
        $this->withoutVite();

        $this->actingAs(User::factory()->create())
            ->get(route('leads.index'))
            ->assertOk()
            ->assertSee('Inbound Queue');
    }

    public function test_user_can_create_lead(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('leads.store'), [
            'first_name' => 'Ava',
            'last_name' => 'Mason',
            'phone' => '(555) 444-1122',
            'email' => 'ava@example.com',
            'stage' => Lead::STAGE_NEW,
            'source' => 'inbound_call',
            'service_needed' => 'Towing',
            'location' => 'Downtown',
            'estimated_value' => '145.50',
            'assigned_user_id' => $user->id,
            'notes' => 'Customer asked for ETA under 30 minutes.',
        ]);

        $response->assertRedirect();

        $lead = Lead::query()->first();
        $this->assertNotNull($lead);
        $this->assertSame('5554441122', $lead->phone);
        $this->assertSame('Ava', $lead->first_name);
    }

    public function test_start_intake_prefills_service_request_form(): void
    {
        $this->withoutVite();

        $lead = Lead::query()->create([
            'first_name' => 'Nora',
            'last_name' => 'Lee',
            'phone' => '5558881234',
            'stage' => Lead::STAGE_DISPATCH_READY,
            'source' => 'inbound_call',
            'location' => '123 Main St',
            'notes' => 'Battery issue',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->post(route('leads.start-intake', $lead));

        $response->assertRedirect();
        $response->assertRedirectContains('first_name=Nora');
        $response->assertRedirectContains('lead_id=' . $lead->id);
    }

    public function test_service_request_creation_marks_lead_as_converted(): void
    {
        $user = User::factory()->create();

        $lead = Lead::query()->create([
            'first_name' => 'Mia',
            'last_name' => 'Stone',
            'phone' => '5551214545',
            'stage' => Lead::STAGE_DISPATCH_READY,
            'source' => 'inbound_call',
        ]);

        $category = CatalogCategory::query()->create([
            'name' => 'Roadside',
            'is_active' => true,
        ]);

        $item = CatalogItem::query()->create([
            'catalog_category_id' => $category->id,
            'name' => 'Jump Start',
            'base_cost' => 89.00,
            'unit' => 'job',
            'pricing_type' => 'fixed',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('service-requests.store'), [
            'lead_id' => $lead->id,
            'customer_action' => 'create_new',
            'first_name' => 'Mia',
            'last_name' => 'Stone',
            'phone' => '5551214545',
            'vehicle_year' => '2020',
            'vehicle_make' => 'Toyota',
            'vehicle_model' => 'Corolla',
            'vehicle_color' => 'White',
            'catalog_item_id' => $item->id,
            'quoted_price' => '89.00',
            'street_address' => '120 Harbor Dr',
            'city' => 'Tampa',
            'state' => 'FL',
            'notes' => 'No crank',
        ]);

        $response->assertRedirect();

        $lead->refresh();
        $serviceRequest = ServiceRequest::query()->first();

        $this->assertNotNull($serviceRequest);
        $this->assertNotNull($lead->converted_at);
        $this->assertSame(Lead::STAGE_CONVERTED, $lead->stage);
        $this->assertSame($serviceRequest->id, $lead->converted_service_request_id);
        $this->assertNotNull($lead->converted_customer_id);
    }
}
