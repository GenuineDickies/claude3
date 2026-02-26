<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ServiceTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedUser(): User
    {
        return User::factory()->create();
    }

    // ── Index ────────────────────────────────────────────────

    public function test_index_page_loads(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('service-types.index'));

        $response->assertOk();
        $response->assertSee('Service Types');
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->get(route('service-types.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_index_displays_service_types(): void
    {
        $this->withoutVite();

        ServiceType::create([
            'name' => 'Jump Start',
            'default_price' => 55.00,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('service-types.index'));

        $response->assertOk();
        $response->assertSee('Jump Start');
        $response->assertSee('55.00');
    }

    public function test_index_shows_empty_state(): void
    {
        $this->withoutVite();

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('service-types.index'));

        $response->assertOk();
        $response->assertSee('No service types yet');
    }

    // ── Store ────────────────────────────────────────────────

    public function test_store_creates_service_type(): void
    {
        $response = $this->actingAs($this->authenticatedUser())
            ->post(route('service-types.store'), [
                'name' => 'Lockout Service',
                'default_price' => 65.00,
            ]);

        $response->assertRedirect(route('service-types.index'));
        $this->assertDatabaseHas('service_types', [
            'name' => 'Lockout Service',
            'default_price' => 65.00,
            'is_active' => true,
        ]);
    }

    public function test_store_requires_name(): void
    {
        $response = $this->actingAs($this->authenticatedUser())
            ->post(route('service-types.store'), [
                'default_price' => 65.00,
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_store_requires_price(): void
    {
        $response = $this->actingAs($this->authenticatedUser())
            ->post(route('service-types.store'), [
                'name' => 'Lockout Service',
            ]);

        $response->assertSessionHasErrors('default_price');
    }

    public function test_store_rejects_duplicate_name(): void
    {
        ServiceType::create([
            'name' => 'Flat Tire Change',
            'default_price' => 75.00,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->post(route('service-types.store'), [
                'name' => 'Flat Tire Change',
                'default_price' => 80.00,
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_store_auto_increments_sort_order(): void
    {
        ServiceType::create([
            'name' => 'Tow',
            'default_price' => 125.00,
            'sort_order' => 3,
        ]);

        $this->actingAs($this->authenticatedUser())
            ->post(route('service-types.store'), [
                'name' => 'Winch Out',
                'default_price' => 150.00,
            ]);

        $this->assertDatabaseHas('service_types', [
            'name' => 'Winch Out',
            'sort_order' => 4,
        ]);
    }

    // ── Update ───────────────────────────────────────────────

    public function test_update_modifies_service_type(): void
    {
        $type = ServiceType::create([
            'name' => 'Jump Start',
            'default_price' => 55.00,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->put(route('service-types.update', $type), [
                'name' => 'Battery Jump Start',
                'default_price' => 60.00,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('service-types.index'));
        $this->assertDatabaseHas('service_types', [
            'id' => $type->id,
            'name' => 'Battery Jump Start',
            'default_price' => 60.00,
        ]);
    }

    public function test_update_allows_same_name_for_same_record(): void
    {
        $type = ServiceType::create([
            'name' => 'Tow',
            'default_price' => 125.00,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->put(route('service-types.update', $type), [
                'name' => 'Tow',
                'default_price' => 130.00,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('service-types.index'));
        $response->assertSessionHasNoErrors();
    }

    // ── Toggle ───────────────────────────────────────────────

    public function test_toggle_deactivates_service_type(): void
    {
        $type = ServiceType::create([
            'name' => 'Fuel Delivery',
            'default_price' => 60.00,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->patch(route('service-types.toggle', $type));

        $response->assertRedirect(route('service-types.index'));
        $this->assertDatabaseHas('service_types', [
            'id' => $type->id,
            'is_active' => false,
        ]);
    }

    public function test_toggle_activates_service_type(): void
    {
        $type = ServiceType::create([
            'name' => 'Fuel Delivery',
            'default_price' => 60.00,
            'sort_order' => 1,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->patch(route('service-types.toggle', $type));

        $response->assertRedirect(route('service-types.index'));
        $this->assertDatabaseHas('service_types', [
            'id' => $type->id,
            'is_active' => true,
        ]);
    }

    // ── Reorder ──────────────────────────────────────────────

    public function test_reorder_updates_sort_order(): void
    {
        $a = ServiceType::create(['name' => 'A', 'default_price' => 10, 'sort_order' => 1]);
        $b = ServiceType::create(['name' => 'B', 'default_price' => 20, 'sort_order' => 2]);
        $c = ServiceType::create(['name' => 'C', 'default_price' => 30, 'sort_order' => 3]);

        $response = $this->actingAs($this->authenticatedUser())
            ->postJson(route('service-types.reorder'), [
                'ids' => [$c->id, $a->id, $b->id],
            ]);

        $response->assertOk();
        $this->assertEquals(1, $c->fresh()->sort_order);
        $this->assertEquals(2, $a->fresh()->sort_order);
        $this->assertEquals(3, $b->fresh()->sort_order);
    }

    public function test_reorder_validates_ids(): void
    {
        $response = $this->actingAs($this->authenticatedUser())
            ->postJson(route('service-types.reorder'), [
                'ids' => [999],
            ]);

        $response->assertUnprocessable();
    }
}
