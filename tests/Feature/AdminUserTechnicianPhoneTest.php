<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\Access\PageRegistryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminUserTechnicianPhoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PageRegistryService::class)->sync();
    }

    public function test_admin_cannot_activate_technician_role_without_mobile_phone(): void
    {
        $admin = User::factory()->create();
        $technician = User::factory()->create();
        $technicianRole = Role::query()->firstOrCreate([
            'role_name' => 'Technician',
        ], [
            'description' => 'Field service technician',
            'requires_mobile_phone' => true,
            'requires_sms_consent' => true,
        ]);

        $response = $this->actingAs($admin)
            ->put(route('admin.users.update', $technician), [
                'name' => $technician->name,
                'username' => $technician->username,
                'email' => $technician->email,
                'status' => 'active',
                'role_ids' => [$technicianRole->id],
            ]);

        $response->assertSessionHasErrors('phone');

        $this->assertNull($technician->fresh()->phone);
    }

    public function test_admin_can_store_mobile_phone_when_activating_technician_role(): void
    {
        $admin = User::factory()->create();
        $technician = User::factory()->create();
        $technicianRole = Role::query()->updateOrCreate([
            'role_name' => 'Technician',
        ], [
            'role_name' => 'Technician',
            'description' => 'Field service technician',
            'requires_mobile_phone' => true,
            'requires_sms_consent' => true,
        ]);

        $response = $this->actingAs($admin)
            ->put(route('admin.users.update', $technician), [
                'name' => $technician->name,
                'username' => $technician->username,
                'email' => $technician->email,
                'phone' => '+1 (555) 444-1212',
                'status' => 'active',
                'role_ids' => [$technicianRole->id],
            ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('technician_profiles', [
            'user_id' => $technician->id,
        ]);
        $this->assertSame('5554441212', $technician->fresh()->phone);
    }
}