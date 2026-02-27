<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\TechnicianProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicianComplianceTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedUser(): User
    {
        return User::factory()->create();
    }

    private function enableCompliance(): void
    {
        Setting::setValue('compliance_tracking_enabled', '1');
    }

    // ── Feature toggle ────────────────────────────────────

    public function test_index_returns_404_when_feature_disabled(): void
    {
        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('technician-profiles.index'));

        $response->assertStatus(404);
    }

    public function test_index_loads_when_feature_enabled(): void
    {
        $this->enableCompliance();

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('technician-profiles.index'));

        $response->assertOk();
        $response->assertSeeText('Technician Compliance');
    }

    public function test_show_returns_404_when_feature_disabled(): void
    {
        $user = $this->authenticatedUser();

        $response = $this->actingAs($user)
            ->get(route('technician-profiles.show', $user));

        $response->assertStatus(404);
    }

    public function test_edit_returns_404_when_feature_disabled(): void
    {
        $user = $this->authenticatedUser();

        $response = $this->actingAs($user)
            ->get(route('technician-profiles.edit', $user));

        $response->assertStatus(404);
    }

    // ── CRUD ──────────────────────────────────────────────

    public function test_edit_page_loads(): void
    {
        $this->enableCompliance();
        $user = $this->authenticatedUser();

        $response = $this->actingAs($user)
            ->get(route('technician-profiles.edit', $user));

        $response->assertOk();
        $response->assertSeeText('Edit Compliance');
    }

    public function test_update_creates_profile(): void
    {
        $this->enableCompliance();
        $user = $this->authenticatedUser();

        $response = $this->actingAs($user)
            ->put(route('technician-profiles.update', $user), [
                'drivers_license_number' => 'DL12345',
                'drivers_license_expiry' => '2027-06-15',
                'insurance_policy_number' => 'INS-999',
                'insurance_expiry' => '2027-01-01',
                'background_check_status' => 'clear',
                'drug_screen_status' => 'clear',
                'emergency_contact_name' => 'Jane Doe',
                'emergency_contact_phone' => '5551234567',
                'vehicle_year' => '2024',
                'vehicle_make' => 'Ford',
                'vehicle_model' => 'F-150',
                'vehicle_plate' => 'ABC1234',
            ]);

        $response->assertRedirect(route('technician-profiles.show', $user));

        $this->assertDatabaseHas('technician_profiles', [
            'user_id' => $user->id,
            'drivers_license_number' => 'DL12345',
            'insurance_policy_number' => 'INS-999',
            'vehicle_make' => 'Ford',
        ]);
    }

    public function test_update_modifies_existing_profile(): void
    {
        $this->enableCompliance();
        $user = $this->authenticatedUser();
        TechnicianProfile::create([
            'user_id' => $user->id,
            'drivers_license_number' => 'OLD123',
        ]);

        $response = $this->actingAs($user)
            ->put(route('technician-profiles.update', $user), [
                'drivers_license_number' => 'NEW456',
            ]);

        $response->assertRedirect(route('technician-profiles.show', $user));
        $this->assertDatabaseHas('technician_profiles', [
            'user_id' => $user->id,
            'drivers_license_number' => 'NEW456',
        ]);
        $this->assertDatabaseCount('technician_profiles', 1);
    }

    public function test_update_validates_status_values(): void
    {
        $this->enableCompliance();
        $user = $this->authenticatedUser();

        $response = $this->actingAs($user)
            ->put(route('technician-profiles.update', $user), [
                'background_check_status' => 'invalid',
            ]);

        $response->assertSessionHasErrors('background_check_status');
    }

    public function test_show_page_displays_profile_data(): void
    {
        $this->enableCompliance();
        $user = $this->authenticatedUser();
        TechnicianProfile::create([
            'user_id' => $user->id,
            'drivers_license_number' => 'DL99999',
            'drivers_license_expiry' => '2027-12-31',
            'background_check_status' => 'clear',
        ]);

        $response = $this->actingAs($user)
            ->get(route('technician-profiles.show', $user));

        $response->assertOk();
        $response->assertSeeText('DL99999');
        $response->assertSeeText('Clear');
    }

    public function test_update_saves_certifications(): void
    {
        $this->enableCompliance();
        $user = $this->authenticatedUser();

        $this->actingAs($user)
            ->put(route('technician-profiles.update', $user), [
                'certifications' => [
                    ['name' => 'CPR', 'issued_date' => '2025-01-01', 'expiry_date' => '2027-01-01'],
                    ['name' => 'First Aid', 'issued_date' => '2025-06-01', 'expiry_date' => '2027-06-01'],
                ],
            ]);

        $profile = $user->fresh()->technicianProfile;
        $this->assertCount(2, $profile->certifications);
        $this->assertSame('CPR', $profile->certifications[0]['name']);
    }

    public function test_update_strips_empty_certifications(): void
    {
        $this->enableCompliance();
        $user = $this->authenticatedUser();

        $this->actingAs($user)
            ->put(route('technician-profiles.update', $user), [
                'certifications' => [
                    ['name' => '', 'issued_date' => '', 'expiry_date' => ''],
                    ['name' => 'Real Cert', 'issued_date' => '2025-01-01', 'expiry_date' => '2027-01-01'],
                ],
            ]);

        $profile = $user->fresh()->technicianProfile;
        $this->assertCount(1, $profile->certifications);
        $this->assertSame('Real Cert', $profile->certifications[0]['name']);
    }

    // ── Compliance status helpers ─────────────────────────

    public function test_date_status_expired(): void
    {
        $this->assertSame('expired', TechnicianProfile::dateStatus(now()->subDay()));
    }

    public function test_date_status_expiring_soon(): void
    {
        $this->assertSame('expiring', TechnicianProfile::dateStatus(now()->addDays(15)));
    }

    public function test_date_status_valid(): void
    {
        $this->assertSame('valid', TechnicianProfile::dateStatus(now()->addDays(60)));
    }

    public function test_date_status_null(): void
    {
        $this->assertNull(TechnicianProfile::dateStatus(null));
    }

    public function test_is_fully_compliant(): void
    {
        $profile = new TechnicianProfile([
            'drivers_license_expiry' => now()->addYear(),
            'insurance_expiry' => now()->addYear(),
            'background_check_status' => 'clear',
            'drug_screen_status' => 'clear',
        ]);

        $this->assertTrue($profile->isFullyCompliant());
    }

    public function test_is_not_compliant_with_expired_license(): void
    {
        $profile = new TechnicianProfile([
            'drivers_license_expiry' => now()->subDay(),
            'insurance_expiry' => now()->addYear(),
            'background_check_status' => 'clear',
            'drug_screen_status' => 'clear',
        ]);

        $this->assertFalse($profile->isFullyCompliant());
    }

    public function test_issue_count_tracks_all_problems(): void
    {
        $profile = new TechnicianProfile([
            'drivers_license_expiry' => now()->subDay(),  // expired
            'insurance_expiry' => now()->addDays(10),     // expiring
            'background_check_status' => 'failed',        // failed
            'drug_screen_status' => 'pending',             // pending
            'certifications' => [
                ['name' => 'Test', 'expiry_date' => now()->subWeek()->toDateString()], // expired cert
            ],
        ]);

        $this->assertSame(5, $profile->issueCount());
    }

    // ── Scopes ────────────────────────────────────────────

    public function test_expired_scope(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        TechnicianProfile::create([
            'user_id' => $user1->id,
            'drivers_license_expiry' => now()->subDay(),
        ]);
        TechnicianProfile::create([
            'user_id' => $user2->id,
            'drivers_license_expiry' => now()->addYear(),
        ]);

        $this->assertSame(1, TechnicianProfile::expired()->count());
    }

    public function test_expiring_scope(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        TechnicianProfile::create([
            'user_id' => $user1->id,
            'insurance_expiry' => now()->addDays(15),
        ]);
        TechnicianProfile::create([
            'user_id' => $user2->id,
            'insurance_expiry' => now()->addYear(),
        ]);

        $this->assertSame(1, TechnicianProfile::expiring()->count());
    }

    public function test_compliant_scope(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        TechnicianProfile::create([
            'user_id' => $user1->id,
            'drivers_license_expiry' => now()->addYear(),
            'insurance_expiry' => now()->addYear(),
        ]);
        TechnicianProfile::create([
            'user_id' => $user2->id,
            'drivers_license_expiry' => now()->subDay(),
        ]);

        $this->assertSame(1, TechnicianProfile::compliant()->count());
    }

    // ── Dashboard widget ──────────────────────────────────

    public function test_dashboard_hides_compliance_widget_when_disabled(): void
    {
        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSeeText('Technician Compliance');
    }

    public function test_dashboard_shows_compliance_widget_when_enabled(): void
    {
        $this->enableCompliance();

        $response = $this->actingAs($this->authenticatedUser())
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Technician Compliance');
    }

    // ── Auth ──────────────────────────────────────────────

    public function test_routes_require_authentication(): void
    {
        $this->enableCompliance();
        $user = User::factory()->create();

        $this->get(route('technician-profiles.index'))->assertRedirect(route('login'));
        $this->get(route('technician-profiles.show', $user))->assertRedirect(route('login'));
        $this->get(route('technician-profiles.edit', $user))->assertRedirect(route('login'));
        $this->put(route('technician-profiles.update', $user))->assertRedirect(route('login'));
    }
}
