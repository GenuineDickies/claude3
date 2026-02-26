<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WhiteLabelTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // View composer: $companyName / $companyTagline
    // ------------------------------------------------------------------

    public function test_company_name_defaults_to_app_config(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText(config('app.name'));
    }

    public function test_company_name_uses_setting_when_set(): void
    {
        Setting::setValue('company_name', 'Test Co Roadside');

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Test Co Roadside');
    }

    public function test_company_tagline_defaults_when_not_set(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Dispatch management');
    }

    public function test_company_tagline_uses_setting_when_set(): void
    {
        Setting::setValue('company_tagline', '24/7 Emergency Service');

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('24/7 Emergency Service');
    }

    // ------------------------------------------------------------------
    // Sidebar brand rendering
    // ------------------------------------------------------------------

    public function test_sidebar_renders_company_name(): void
    {
        Setting::setValue('company_name', 'Acme Towing');

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Acme Towing');
    }

    // ------------------------------------------------------------------
    // Layout title
    // ------------------------------------------------------------------

    public function test_layout_title_uses_company_name(): void
    {
        Setting::setValue('company_name', 'SpecialBrand');

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('<title>SpecialBrand</title>', false);
    }

    // ------------------------------------------------------------------
    // Consent script — no hardcoded business names
    // ------------------------------------------------------------------

    public function test_consent_text_uses_dynamic_company_name(): void
    {
        Setting::setValue('company_name', 'Dynamic Roadside LLC');

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('service-requests.create'));

        $response->assertOk();
        $response->assertSeeText('Dynamic Roadside LLC');
        $response->assertDontSeeText('White Knight');
    }

    // ------------------------------------------------------------------
    // No hardcoded business names in rendered views
    // ------------------------------------------------------------------

    public function test_no_hardcoded_brand_in_dashboard_when_setting_overrides(): void
    {
        Setting::setValue('company_name', 'Custom Biz Name');

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Custom Biz Name');
        // The hardcoded "Roadside Assist" brand should not appear
        $this->assertStringNotContainsString('>Roadside Assist<', $response->getContent());
    }

    public function test_no_hardcoded_roadside_assist_brand_in_sidebar(): void
    {
        Setting::setValue('company_name', 'My Test Biz');

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'));

        $content = $response->getContent();
        // The literal "Roadside Assist" should not appear as a brand — only our dynamic name
        // (The word "roadside" might appear in generic text, but "Roadside Assist" as a brand should not)
        $this->assertStringNotContainsString('>Roadside Assist<', $content);
    }

    // ------------------------------------------------------------------
    // New settings exist in definitions
    // ------------------------------------------------------------------

    public function test_new_settings_are_defined(): void
    {
        $definitions = Setting::definitions();
        $generalFields = $definitions['general']['fields'];

        $this->assertArrayHasKey('company_name', $generalFields);
        $this->assertArrayHasKey('company_tagline', $generalFields);
        $this->assertArrayHasKey('company_phone', $generalFields);
        $this->assertArrayHasKey('company_email', $generalFields);
        $this->assertArrayHasKey('company_address', $generalFields);
    }
}
