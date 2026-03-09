<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\PageRegistryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBasedPageAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PageRegistryService::class)->sync();
    }

    public function test_user_can_access_pages_from_multiple_roles(): void
    {
        $dashboardPage = Page::query()->where('page_path', '/dashboard')->firstOrFail();
        $customersPage = Page::query()->where('page_path', '/customers')->firstOrFail();

        $dispatcher = Role::query()->create([
            'role_name' => 'Dispatcher',
            'description' => 'Dashboard access',
        ]);
        $dispatcher->pages()->sync([$dashboardPage->id]);

        $customerCare = Role::query()->create([
            'role_name' => 'Customer Care',
            'description' => 'Customer page access',
        ]);
        $customerCare->pages()->sync([$customersPage->id]);

        $user = User::factory()->create([
            'status' => 'active',
        ]);
        $user->roles()->detach();
        $user->roles()->sync([$dispatcher->id, $customerCare->id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('customers.index'))
            ->assertOk();
    }

    public function test_user_without_page_access_is_redirected_to_access_denied(): void
    {
        $dashboardPage = Page::query()->where('page_path', '/dashboard')->firstOrFail();

        $dispatcher = Role::query()->create([
            'role_name' => 'Dispatcher',
            'description' => 'Dashboard access only',
        ]);
        $dispatcher->pages()->sync([$dashboardPage->id]);

        $user = User::factory()->create([
            'status' => 'active',
        ]);
        $user->roles()->detach();
        $user->roles()->sync([$dispatcher->id]);

        $this->actingAs($user)
            ->get(route('customers.index'))
            ->assertRedirect(route('access.denied', ['page' => '/customers']));
    }

    public function test_last_administrator_cannot_be_stripped_of_admin_role(): void
    {
        $admin = User::factory()->create([
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'username' => $admin->username,
                'email' => $admin->email,
                'status' => 'active',
                'role_ids' => [],
            ])
            ->assertSessionHasErrors('role_ids');

        $this->assertTrue($admin->fresh()->isAdministrator());
    }

    public function test_disabled_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'disabled@example.com',
            'status' => 'disabled',
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_can_update_role_page_assignments(): void
    {
        $admin = User::factory()->create();
        $role = Role::query()->create([
            'role_name' => 'Reports',
            'description' => 'Financial reporting access',
        ]);

        $dashboardPage = Page::query()->where('page_path', '/dashboard')->firstOrFail();
        $reportsPage = Page::query()->where('page_path', '/reports')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.access.update', $role), [
                'page_ids' => [$dashboardPage->id, $reportsPage->id],
            ])
            ->assertSessionHas('success');

        $this->assertEqualsCanonicalizing(
            [$dashboardPage->id, $reportsPage->id],
            $role->fresh()->pages->pluck('id')->all(),
        );
    }
}