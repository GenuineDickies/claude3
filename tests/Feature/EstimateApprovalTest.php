<?php

namespace Tests\Feature;

use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\SmsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EstimateApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedUser(): User
    {
        return User::factory()->create();
    }

    private function createServiceRequest(): ServiceRequest
    {
        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'phone'      => '5551234567',
            'is_active'  => true,
        ]);

        $category = CatalogCategory::create([
            'name'       => 'Services',
            'type'       => 'service',
            'sort_order' => 0,
            'is_active'  => true,
        ]);

        CatalogItem::create([
            'catalog_category_id' => $category->id,
            'name'                => 'Flat Tire Change',
            'unit_price'          => 75.00,
            'unit'                => 'each',
            'pricing_type'        => 'fixed',
            'sort_order'          => 1,
            'is_active'           => true,
        ]);

        return ServiceRequest::create([
            'customer_id'    => $customer->id,
            'catalog_item_id' => CatalogItem::first()->id,
            'quoted_price'   => 75.00,
            'status'         => 'new',
        ]);
    }

    private function createEstimate(ServiceRequest $sr, float $total = 250.00, string $status = 'sent'): Estimate
    {
        $estimate = Estimate::create([
            'service_request_id' => $sr->id,
            'estimate_number'    => Estimate::generateEstimateNumber(),
            'tax_rate'           => 0,
            'subtotal'           => $total,
            'tax_amount'         => 0,
            'total'              => $total,
            'status'             => $status,
        ]);

        EstimateItem::create([
            'estimate_id' => $estimate->id,
            'name'        => 'Test Item',
            'unit_price'  => $total,
            'quantity'    => 1,
            'unit'        => 'each',
            'sort_order'  => 0,
        ]);

        return $estimate;
    }

    private function setThreshold(?string $value): void
    {
        if ($value === null) {
            Setting::where('key', 'estimate_signature_threshold')->delete();
            Setting::setValue('estimate_approval_mode', 'none');
            cache()->forget('app_settings');
            return;
        }

        Setting::setValue('estimate_approval_mode', 'threshold');
        Setting::setValue('estimate_signature_threshold', $value);
    }

    // ------------------------------------------------------------------
    // requiresApproval() logic
    // ------------------------------------------------------------------

    public function test_estimate_requires_approval_above_threshold(): void
    {
        $this->setThreshold('200.00');
        $sr = $this->createServiceRequest();
        $estimate = $this->createEstimate($sr, 250.00);

        $this->assertTrue($estimate->requiresApproval());
    }

    public function test_estimate_does_not_require_approval_below_threshold(): void
    {
        $this->setThreshold('200.00');
        $sr = $this->createServiceRequest();
        $estimate = $this->createEstimate($sr, 150.00);

        $this->assertFalse($estimate->requiresApproval());
    }

    public function test_estimate_does_not_require_approval_at_threshold(): void
    {
        $this->setThreshold('200.00');
        $sr = $this->createServiceRequest();
        $estimate = $this->createEstimate($sr, 200.00);

        $this->assertFalse($estimate->requiresApproval());
    }

    public function test_no_approval_required_when_threshold_not_set(): void
    {
        $this->setThreshold(null);
        $sr = $this->createServiceRequest();
        $estimate = $this->createEstimate($sr, 500.00);

        $this->assertFalse($estimate->requiresApproval());
    }

    public function test_zero_threshold_requires_approval_for_all(): void
    {
        Setting::setValue('estimate_approval_mode', 'all');
        $sr = $this->createServiceRequest();
        $estimate = $this->createEstimate($sr, 50.00);

        $this->assertTrue($estimate->requiresApproval());
    }

    // ------------------------------------------------------------------
    // Request Approval flow
    // ------------------------------------------------------------------

    public function test_request_approval_generates_token_and_sets_status(): void
    {
        $this->setThreshold('200.00');
        $sr = $this->createServiceRequest();
        $estimate = $this->createEstimate($sr, 300.00, 'sent');

        // Mock SMS to avoid real sending
        $this->mock(SmsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('sendRaw')->once()->andReturn([
                'success' => true, 'message_id' => 'test-id', 'error' => null,
            ]);
        });

        $response = $this->actingAs($this->authenticatedUser())
            ->post("/service-requests/{$sr->id}/estimates/{$estimate->id}/request-approval");

        $response->assertRedirect();

        $estimate->refresh();
        $this->assertEquals('pending_approval', $estimate->status);
        $this->assertNotNull($estimate->approval_token);
        $this->assertNotNull($estimate->approval_token_expires_at);
        $this->assertTrue($estimate->approval_token_expires_at->isFuture());
    }

    public function test_request_approval_forbidden_for_draft_estimate(): void
    {
        $sr = $this->createServiceRequest();
        $estimate = $this->createEstimate($sr, 300.00, 'draft');

        $response = $this->actingAs($this->authenticatedUser())
            ->post("/service-requests/{$sr->id}/estimates/{$estimate->id}/request-approval");

        $response->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Public approval page
    // ------------------------------------------------------------------

    public function test_public_approval_page_loads_with_valid_token(): void
    {
        $this->setThreshold('200.00');
        $sr = $this->createServiceRequest();
        $estimate = $this->createEstimate($sr, 300.00, 'sent');
        $token = $estimate->generateApprovalToken();

        $response = $this->get("/estimates/approve/{$token}");

        $response->assertOk();
        $response->assertViewIs('estimates.approve');
    }

    public function test_public_approval_page_shows_closed_for_expired_token(): void
    {
        $sr = $this->createServiceRequest();
        $estimate = $this->createEstimate($sr, 300.00, 'sent');
        $token = $estimate->generateApprovalToken();

        // Manually expire the token
        $estimate->update(['approval_token_expires_at' => now()->subDay()]);

        $response = $this->get("/estimates/approve/{$token}");

        $response->assertOk();
        $response->assertViewIs('estimates.approval-closed');
    }

    public function test_public_approval_page_404_for_invalid_token(): void
    {
        $response = $this->get('/estimates/approve/invalid-token-that-does-not-exist');

        $response->assertNotFound();
    }

    // ------------------------------------------------------------------
    // Approve / decline via token
    // ------------------------------------------------------------------

    public function test_customer_can_approve_estimate(): void
    {
        $this->setThreshold('200.00');
        $sr = $this->createServiceRequest();
        $estimate = $this->createEstimate($sr, 300.00, 'sent');
        $token = $estimate->generateApprovalToken();

        $response = $this->post("/estimates/approve/{$token}", [
            'decision'    => 'accepted',
            'signer_name' => 'Jane Doe',
            'signature_data' => 'data:image/png;base64,test',
        ]);

        $response->assertOk();
        $response->assertViewIs('estimates.approval-complete');

        $estimate->refresh();
        $this->assertEquals('accepted', $estimate->status);
        $this->assertEquals(300.00, (float) $estimate->approved_total);
        $this->assertEquals('Jane Doe', $estimate->signer_name);
        $this->assertNotNull($estimate->approved_at);
        $this->assertTrue($estimate->is_locked);
    }

    public function test_customer_can_decline_estimate(): void
    {
        $sr = $this->createServiceRequest();
        $estimate = $this->createEstimate($sr, 300.00, 'sent');
        $token = $estimate->generateApprovalToken();

        $response = $this->post("/estimates/approve/{$token}", [
            'decision'    => 'declined',
            'signer_name' => 'Jane Doe',
        ]);

        $response->assertOk();

        $estimate->refresh();
        $this->assertEquals('declined', $estimate->status);
        $this->assertNull($estimate->approved_total);
        $this->assertNotNull($estimate->approved_at);
        $this->assertFalse($estimate->is_locked);
    }

    public function test_cannot_approve_already_approved_estimate(): void
    {
        $sr = $this->createServiceRequest();
        $estimate = $this->createEstimate($sr, 300.00, 'sent');
        $token = $estimate->generateApprovalToken();

        // Approve first
        $estimate->update([
            'status' => 'accepted',
            'approved_at' => now(),
            'approved_total' => 300.00,
        ]);

        $response = $this->post("/estimates/approve/{$token}", [
            'decision'    => 'accepted',
            'signer_name' => 'Jane Doe',
        ]);

        // Should redirect back since approval is no longer open
        $response->assertRedirect();
    }

    // ------------------------------------------------------------------
    // Work Order creation gate
    // ------------------------------------------------------------------

    public function test_work_order_creation_blocked_for_unapproved_estimate(): void
    {
        $this->setThreshold('200.00');
        $sr = $this->createServiceRequest();
        $estimate = $this->createEstimate($sr, 300.00, 'sent');

        $response = $this->actingAs($this->authenticatedUser())
            ->post("/service-requests/{$sr->id}/work-orders", [
                'estimate_id' => $estimate->id,
                'priority'    => 'normal',
                'tax_rate'    => 0,
                'items'       => [[
                    'name'       => 'Test',
                    'unit_price' => 300,
                    'quantity'   => 1,
                    'unit'       => 'each',
                ]],
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('work_orders', 0);
    }

    public function test_work_order_creation_allowed_for_approved_estimate(): void
    {
        $this->setThreshold('200.00');
        $sr = $this->createServiceRequest();
        $estimate = $this->createEstimate($sr, 300.00, 'accepted');
        $estimate->update([
            'approved_at'    => now(),
            'approved_total' => 300.00,
            'signer_name'   => 'Jane Doe',
        ]);

        $response = $this->actingAs($this->authenticatedUser())
            ->post("/service-requests/{$sr->id}/work-orders", [
                'estimate_id' => $estimate->id,
                'priority'    => 'normal',
                'tax_rate'    => 0,
                'items'       => [[
                    'name'       => 'Test',
                    'unit_price' => 300,
                    'quantity'   => 1,
                    'unit'       => 'each',
                ]],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('work_orders', 1);
    }

    public function test_work_order_creation_allowed_when_below_threshold(): void
    {
        $this->setThreshold('200.00');
        $sr = $this->createServiceRequest();
        $estimate = $this->createEstimate($sr, 150.00, 'accepted');

        $response = $this->actingAs($this->authenticatedUser())
            ->post("/service-requests/{$sr->id}/work-orders", [
                'estimate_id' => $estimate->id,
                'priority'    => 'normal',
                'tax_rate'    => 0,
                'items'       => [[
                    'name'       => 'Test',
                    'unit_price' => 150,
                    'quantity'   => 1,
                    'unit'       => 'each',
                ]],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('work_orders', 1);
    }

    public function test_work_order_creation_allowed_without_estimate(): void
    {
        $this->setThreshold('200.00');
        $sr = $this->createServiceRequest();

        $response = $this->actingAs($this->authenticatedUser())
            ->post("/service-requests/{$sr->id}/work-orders", [
                'priority' => 'normal',
                'tax_rate' => 0,
                'items'    => [[
                    'name'       => 'Test',
                    'unit_price' => 500,
                    'quantity'   => 1,
                    'unit'       => 'each',
                ]],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('work_orders', 1);
    }

    // ------------------------------------------------------------------
    // Approved total immutability
    // ------------------------------------------------------------------

    public function test_approved_total_locked_after_approval(): void
    {
        $sr = $this->createServiceRequest();
        $estimate = $this->createEstimate($sr, 300.00, 'sent');
        $token = $estimate->generateApprovalToken();

        $this->post("/estimates/approve/{$token}", [
            'decision'    => 'accepted',
            'signer_name' => 'Jane Doe',
        ]);

        $estimate->refresh();
        $this->assertEquals(300.00, (float) $estimate->approved_total);
        $this->assertTrue($estimate->is_locked);

        // Verify that the estimate is locked and cannot be edited
        $this->assertTrue($estimate->is_locked);
        $this->assertFalse($estimate->isEditable());
    }

    // ------------------------------------------------------------------
    // Setting definition exists
    // ------------------------------------------------------------------

    public function test_threshold_setting_exists_in_definitions(): void
    {
        $definitions = Setting::definitions();
        $this->assertArrayHasKey('general', $definitions);
        $this->assertArrayHasKey('estimate_approval_mode', $definitions['general']['fields']);
        $this->assertArrayHasKey('estimate_signature_threshold', $definitions['general']['fields']);
    }

    // ------------------------------------------------------------------
    // Pending approval status in model
    // ------------------------------------------------------------------

    public function test_pending_approval_status_in_statuses(): void
    {
        $statuses = Estimate::statuses();
        $this->assertArrayHasKey('pending_approval', $statuses);
    }

    // ------------------------------------------------------------------
    // Service log events
    // ------------------------------------------------------------------

    public function test_approval_request_creates_service_log(): void
    {
        $this->setThreshold('200.00');
        $sr = $this->createServiceRequest();
        $estimate = $this->createEstimate($sr, 300.00, 'sent');

        $this->mock(SmsServiceInterface::class, function ($mock) {
            $mock->shouldReceive('sendRaw')->once()->andReturn([
                'success' => true, 'message_id' => 'test-id', 'error' => null,
            ]);
        });

        $this->actingAs($this->authenticatedUser())
            ->post("/service-requests/{$sr->id}/estimates/{$estimate->id}/request-approval");

        $this->assertDatabaseHas('service_logs', [
            'service_request_id' => $sr->id,
            'event'              => 'estimate_approval_requested',
        ]);
    }

    public function test_estimate_approval_creates_service_log(): void
    {
        $sr = $this->createServiceRequest();
        $estimate = $this->createEstimate($sr, 300.00, 'sent');
        $token = $estimate->generateApprovalToken();

        $this->post("/estimates/approve/{$token}", [
            'decision'    => 'accepted',
            'signer_name' => 'Jane Doe',
        ]);

        $this->assertDatabaseHas('service_logs', [
            'service_request_id' => $sr->id,
            'event'              => 'estimate_approved',
        ]);
    }

    public function test_estimate_decline_creates_service_log(): void
    {
        $sr = $this->createServiceRequest();
        $estimate = $this->createEstimate($sr, 300.00, 'sent');
        $token = $estimate->generateApprovalToken();

        $this->post("/estimates/approve/{$token}", [
            'decision'    => 'declined',
            'signer_name' => 'Jane Doe',
        ]);

        $this->assertDatabaseHas('service_logs', [
            'service_request_id' => $sr->id,
            'event'              => 'estimate_declined',
        ]);
    }
}
