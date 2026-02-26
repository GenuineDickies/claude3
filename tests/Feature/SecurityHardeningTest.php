<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Security headers
    // ------------------------------------------------------------------

    public function test_responses_include_security_headers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');
    }

    public function test_guest_pages_include_security_headers(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    // ------------------------------------------------------------------
    // Rate limiting on auth routes
    // ------------------------------------------------------------------

    public function test_login_is_rate_limited(): void
    {
        $payload = ['email' => 'test@example.com', 'password' => 'wrong'];

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', $payload);
        }

        $response = $this->post('/login', $payload);

        $response->assertStatus(429);
    }

    public function test_register_is_rate_limited(): void
    {
        // Each registration attempt uses a unique email so it doesn't
        // succeed (duplicate) or auto-authenticate and redirect.
        for ($i = 0; $i < 5; $i++) {
            $this->post('/register', [
                'name' => 'Test',
                'email' => "user{$i}@example.com",
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);
            // Log out after successful registration so next request is a guest
            auth()->logout();
            session()->flush();
        }

        $response = $this->post('/register', [
            'name' => 'Test',
            'email' => 'final@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(429);
    }

    public function test_forgot_password_is_rate_limited(): void
    {
        $payload = ['email' => 'test@example.com'];

        for ($i = 0; $i < 5; $i++) {
            $this->post('/forgot-password', $payload);
        }

        $response = $this->post('/forgot-password', $payload);

        $response->assertStatus(429);
    }

    // ------------------------------------------------------------------
    // Settings input validation
    // ------------------------------------------------------------------

    public function test_settings_update_rejects_invalid_url(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('settings.update-single', 'location_base_url'), [
            'value' => 'not-a-valid-url',
        ]);

        $response->assertSessionHasErrors('value');
    }

    public function test_settings_update_rejects_invalid_email(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('settings.update-single', 'company_email'), [
            'value' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('value');
    }

    public function test_settings_update_rejects_non_numeric_for_number_field(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('settings.update-single', 'location_link_expiry_hours'), [
            'value' => 'abc',
        ]);

        $response->assertSessionHasErrors('value');
    }

    public function test_settings_update_accepts_valid_url(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('settings.update-single', 'location_base_url'), [
            'value' => 'https://example.com/locate.php',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('settings.edit'));
    }

    public function test_settings_update_accepts_null_value(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('settings.update-single', 'company_name'), [
            'value' => null,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('settings.edit'));
    }

    public function test_settings_update_rejects_unknown_key(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('settings.update-single', 'nonexistent_key'), [
            'value' => 'test',
        ]);

        $response->assertStatus(404);
    }

    public function test_bulk_settings_update_validates_all_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('settings.update'), [
            'settings' => [
                'company_email' => 'bad-email',
                'location_base_url' => 'not-a-url',
            ],
        ]);

        $response->assertSessionHasErrors(['settings.company_email', 'settings.location_base_url']);
    }
}
