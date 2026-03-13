<?php

namespace Tests\Feature;

use App\Jobs\ReverseGeocodeJob;
use App\Models\Customer;
use App\Models\MessageTemplate;
use App\Models\Setting;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\SmsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class LocationShareTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function createCustomerWithRequest(bool $optedIn = true, bool $withToken = false): array
    {
        $customer = Customer::create([
            'first_name'     => 'Jane',
            'last_name'      => 'Doe',
            'phone'          => '5551234567',
            'is_active'      => true,
            'sms_consent_at' => $optedIn ? now() : null,
        ]);

        $sr = ServiceRequest::create([
            'customer_id' => $customer->id,
            'status'      => 'new',
        ]);

        if ($withToken) {
            $sr->generateLocationToken();
            $sr->refresh();
        }

        return [$customer, $sr];
    }

    private function seedLocationTemplate(): MessageTemplate
    {
        return MessageTemplate::create([
            'slug'     => 'location-request',
            'name'     => 'Location Finder',
            'category' => 'dispatch',
            'body'     => 'Hi {{ customer_first_name }}, share your location: {{ location_link }}',
        ]);
    }

    // ------------------------------------------------------------------
    // POST /service-requests/{id}/request-location
    // ------------------------------------------------------------------

    public function test_request_location_generates_token_and_sends_sms(): void
    {
        $this->seedLocationTemplate();
        [$customer, $sr] = $this->createCustomerWithRequest(optedIn: true);

        $smsMock = $this->mock(SmsServiceInterface::class);
        $smsMock->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(fn ($template) => $template->slug === 'location-request')
            ->andReturn(['success' => true, 'message_id' => 'msg-1', 'rendered_text' => 'Hi Jane...', 'error' => null]);

        $response = $this->actingAs(User::factory()->create())
            ->post(route('service-requests.request-location', $sr));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $sr->refresh();
        $this->assertNotNull($sr->location_token);
        $this->assertNotNull($sr->location_token_expires_at);
        $this->assertTrue($sr->isLocationTokenValid());
    }

    public function test_request_location_uses_raw_fallback_when_no_template(): void
    {
        // No location-request template seeded
        [$customer, $sr] = $this->createCustomerWithRequest(optedIn: true);

        $smsMock = $this->mock(SmsServiceInterface::class);
        $smsMock->shouldReceive('sendRawWithLog')
            ->once()
            ->andReturn(['success' => true, 'message_id' => 'msg-2', 'error' => null]);

        $response = $this->actingAs(User::factory()->create())
            ->post(route('service-requests.request-location', $sr));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_request_location_requires_verbal_opt_in_when_no_consent(): void
    {
        [, $sr] = $this->createCustomerWithRequest(optedIn: false);

        $smsMock = $this->mock(SmsServiceInterface::class);
        $smsMock->shouldNotReceive('sendTemplate');
        $smsMock->shouldNotReceive('sendRawWithLog');

        $response = $this->actingAs(User::factory()->create())
            ->post(route('service-requests.request-location', $sr));

        $response->assertRedirect();
        $response->assertSessionHas('warning', 'Customer has not opted in to SMS. Record verbal consent before sending location request messages.');

        $sr->refresh();
        $this->assertNull($sr->location_token, 'Token should NOT be generated when customer lacks consent');
    }

    // ------------------------------------------------------------------
    // GET /locate/{token}  (public customer-facing page)
    // ------------------------------------------------------------------

    public function test_locate_page_loads_with_valid_token(): void
    {
        [, $sr] = $this->createCustomerWithRequest(withToken: true);

        $response = $this->get('/locate/' . $sr->location_token);

        $response->assertOk();
        $response->assertViewIs('locate');
        $response->assertViewHas('expired', false);
        $response->assertViewHas('token', $sr->location_token);
    }

    public function test_locate_page_uses_generated_api_url_instead_of_root_relative_path(): void
    {
        [, $sr] = $this->createCustomerWithRequest(withToken: true);

        $response = $this->get('/locate/' . $sr->location_token);

        $response->assertOk();
        $response->assertSee(json_encode(route('locate.store', ['token' => $sr->location_token])), false);
        $response->assertDontSee("fetch('/api/locate/" . $sr->location_token, false);
    }

    public function test_locate_page_uses_company_branding_settings(): void
    {
        Setting::setValue('company_name', 'Signal Roadside');
        Setting::setValue('company_tagline', 'Fast help, clearly tracked');

        [, $sr] = $this->createCustomerWithRequest(withToken: true);

        $response = $this->get('/locate/' . $sr->location_token);

        $response->assertOk();
        $response->assertSeeText('Signal Roadside');
        $response->assertSeeText('Fast help, clearly tracked');
        $response->assertSee('<title>Share Your Location | Signal Roadside</title>', false);
    }

    public function test_locate_page_uses_uploaded_company_logo_when_present(): void
    {
        Storage::fake('local');

        $path = UploadedFile::fake()->image('logo.png', 300, 300)->store('branding', 'local');
        Setting::setValue('company_logo', $path);

        [, $sr] = $this->createCustomerWithRequest(withToken: true);

        $response = $this->get('/locate/' . $sr->location_token);

        $response->assertOk();
        $response->assertSee(route('branding.logo'), false);
    }

    public function test_locate_page_returns_410_for_expired_token(): void
    {
        [, $sr] = $this->createCustomerWithRequest(withToken: true);

        // Expire the token
        $sr->update(['location_token_expires_at' => now()->subHour()]);

        $response = $this->get('/locate/' . $sr->location_token);

        $response->assertStatus(410);
        $response->assertViewHas('expired', true);
    }

    public function test_locate_page_returns_410_when_location_already_shared(): void
    {
        [, $sr] = $this->createCustomerWithRequest(withToken: true);

        // Mark location as already shared
        $sr->update(['location_shared_at' => now()]);

        $response = $this->get('/locate/' . $sr->location_token);

        $response->assertStatus(410);
        $response->assertViewHas('expired', true);
    }

    public function test_locate_page_returns_404_for_invalid_token(): void
    {
        $response = $this->get('/locate/nonexistent-token-abc123');

        $response->assertStatus(404);
    }

    public function test_legacy_locate_php_route_redirects_to_current_route(): void
    {
        [, $sr] = $this->createCustomerWithRequest(withToken: true);

        $response = $this->get('/locate.php?t=' . $sr->location_token);

        $response->assertRedirect('/locate/' . $sr->location_token);
    }

    public function test_legacy_locate_php_route_returns_404_without_token(): void
    {
        $response = $this->get('/locate.php');

        $response->assertStatus(404);
    }

    // ------------------------------------------------------------------
    // POST /api/locate/{token}  (GPS coordinate submission)
    // ------------------------------------------------------------------

    public function test_store_location_saves_coordinates(): void
    {
        Queue::fake();

        [, $sr] = $this->createCustomerWithRequest(withToken: true);

        $response = $this->postJson(route('locate.store', ['token' => $sr->location_token]), [
            'latitude'  => 33.7490,
            'longitude' => -84.3880,
            'accuracy'  => 15.0,
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true]);

        $sr->refresh();
        $this->assertSame('33.7490000', $sr->latitude);
        $this->assertSame('-84.3880000', $sr->longitude);
        $this->assertNotNull($sr->location_shared_at);

        Queue::assertPushed(ReverseGeocodeJob::class, fn (ReverseGeocodeJob $job): bool => $job->serviceRequest->is($sr));
    }

    public function test_store_location_rejects_expired_token(): void
    {
        [, $sr] = $this->createCustomerWithRequest(withToken: true);
        $sr->update(['location_token_expires_at' => now()->subHour()]);

        $response = $this->postJson(route('locate.store', ['token' => $sr->location_token]), [
            'latitude'  => 33.7490,
            'longitude' => -84.3880,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Invalid or expired token.']);
    }

    public function test_store_location_rejects_invalid_token(): void
    {
        $response = $this->postJson(route('locate.store', ['token' => 'bad-token']), [
            'latitude'  => 33.7490,
            'longitude' => -84.3880,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Invalid or expired token.']);
    }

    public function test_store_location_rejects_already_used_token(): void
    {
        [, $sr] = $this->createCustomerWithRequest(withToken: true);
        $sr->update(['location_shared_at' => now()]);

        $response = $this->postJson(route('locate.store', ['token' => $sr->location_token]), [
            'latitude'  => 33.7490,
            'longitude' => -84.3880,
        ]);

        $response->assertStatus(422);
    }

    public function test_store_location_validates_latitude_range(): void
    {
        [, $sr] = $this->createCustomerWithRequest(withToken: true);

        $response = $this->postJson(route('locate.store', ['token' => $sr->location_token]), [
            'latitude'  => 91.0,
            'longitude' => -84.3880,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('latitude');
    }

    public function test_store_location_validates_longitude_range(): void
    {
        [, $sr] = $this->createCustomerWithRequest(withToken: true);

        $response = $this->postJson(route('locate.store', ['token' => $sr->location_token]), [
            'latitude'  => 33.7490,
            'longitude' => -200.0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('longitude');
    }

    public function test_store_location_requires_latitude_and_longitude(): void
    {
        [, $sr] = $this->createCustomerWithRequest(withToken: true);

        $response = $this->postJson(route('locate.store', ['token' => $sr->location_token]), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_store_location_accuracy_is_optional(): void
    {
        Queue::fake();

        [, $sr] = $this->createCustomerWithRequest(withToken: true);

        $response = $this->postJson(route('locate.store', ['token' => $sr->location_token]), [
            'latitude'  => 40.7128,
            'longitude' => -74.0060,
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true]);
    }

    // ------------------------------------------------------------------
    // Location token model behavior
    // ------------------------------------------------------------------

    public function test_generate_location_token_clears_previous_shared_at(): void
    {
        [, $sr] = $this->createCustomerWithRequest(withToken: true);
        $sr->update(['location_shared_at' => now()]);

        // Re-generate token — location_shared_at should be cleared
        $sr->generateLocationToken();
        $sr->refresh();

        $this->assertNull($sr->location_shared_at);
        $this->assertTrue($sr->isLocationTokenValid());
    }

    public function test_location_share_url_returns_null_without_token(): void
    {
        [, $sr] = $this->createCustomerWithRequest(withToken: false);

        $this->assertNull($sr->locationShareUrl());
    }

    public function test_location_share_url_uses_base_url_when_configured(): void
    {
        config(['services.location.base_url' => 'https://example.com/webhook-proxy/locate.php']);

        [, $sr] = $this->createCustomerWithRequest(withToken: true);

        $url = $sr->locationShareUrl();

        $this->assertSame('https://example.com/webhook-proxy/locate/' . $sr->location_token, $url);
    }

    public function test_location_share_url_uses_base_app_url_when_configured(): void
    {
        config(['services.location.base_url' => 'https://example.com/webhook-proxy']);

        [, $sr] = $this->createCustomerWithRequest(withToken: true);

        $url = $sr->locationShareUrl();

        $this->assertSame('https://example.com/webhook-proxy/locate/' . $sr->location_token, $url);
    }

    public function test_location_share_url_falls_back_to_laravel_route(): void
    {
        config(['services.location.base_url' => null]);

        [, $sr] = $this->createCustomerWithRequest(withToken: true);

        $url = $sr->locationShareUrl();

        $this->assertStringContainsString('/locate/' . $sr->location_token, $url);
    }
}
