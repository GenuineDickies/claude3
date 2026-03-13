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

    public function test_admin_update_creates_technician_profile_without_collecting_phone(): void
    {
        $admin = User::factory()->create();
        $technician = User::factory()->create();
        $technicianRole = Role::query()->firstOrCreate([
            'role_name' => 'Technician',
        ], [
            'description' => 'Field service technician',
        ]);

        $response = $this->actingAs($admin)
            ->put(route('admin.users.update', $technician), [
                'name' => $technician->name,
                'username' => $technician->username,
                'email' => $technician->email,
                'status' => 'active',
                'role_ids' => [$technicianRole->id],
                'technician_sms_phone' => '+1 (555) 444-1212',
            ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('technician_profiles', [
            'user_id' => $technician->id,
        ]);
        $this->assertNull($technician->fresh()->phone);
        $this->assertNull($technician->fresh()->technicianProfile?->sms_phone);
    }
}