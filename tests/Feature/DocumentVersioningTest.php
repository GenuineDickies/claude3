<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\Invoice;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DocumentVersioningTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function user(): User
    {
        return User::factory()->create();
    }

    private function createSr(): ServiceRequest
    {
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name'  => 'User',
            'phone'      => '5550001111',
            'is_active'  => true,
        ]);

        return ServiceRequest::create([
            'customer_id' => $customer->id,
            'status'      => 'new',
        ]);
    }

    private function makeEstimate(ServiceRequest $sr, array $overrides = []): Estimate
    {
        $estimate = Estimate::create(array_merge([
            'service_request_id' => $sr->id,
            'estimate_number'    => Estimate::generateEstimateNumber(),
            'state_code'         => 'FL',
            'tax_rate'           => 7.0,
            'subtotal'           => 100.00,
            'tax_amount'         => 7.00,
            'total'              => 107.00,
            'status'             => 'draft',
        ], $overrides));

        EstimateItem::create([
            'estimate_id' => $estimate->id,
            'name'        => 'Tire Change',
            'description' => 'Replace flat tire',
            'unit_price'  => 100.00,
            'quantity'    => 1,
            'unit'        => 'each',
            'sort_order'  => 0,
        ]);

        return $estimate;
    }

    private function makeInvoice(ServiceRequest $sr, array $overrides = []): Invoice
    {
        return Invoice::create(array_merge([
            'service_request_id' => $sr->id,
            'invoice_number'     => Invoice::generateInvoiceNumber(),
            'status'             => Invoice::STATUS_DRAFT,
            'customer_name'      => 'Test User',
            'line_items'         => [['name' => 'Service', 'quantity' => 1, 'unit' => 'ea', 'unit_price' => 50]],
            'subtotal'           => 50.00,
            'tax_rate'           => 0,
            'tax_amount'         => 0,
            'total'              => 50.00,
            'issued_by'          => null,
            'company_snapshot'   => ['name' => 'Test Co', 'address' => '', 'phone' => '', 'email' => ''],
        ], $overrides));
    }

    // ==================================================================
    // Estimate Model – versioning logic
    // ==================================================================

    public function test_estimate_generates_estimate_number(): void
    {
        $number = Estimate::generateEstimateNumber();

        $this->assertStringStartsWith('EST-', $number);
        $this->assertMatchesRegularExpression('/^EST-\d{8}-\d{4}$/', $number);
    }

    public function test_estimate_display_number_v1(): void
    {
        $sr = $this->createSr();
        $est = $this->makeEstimate($sr);

        $this->assertSame($est->estimate_number, $est->displayNumber());
    }

    public function test_estimate_display_number_v2(): void
    {
        $sr = $this->createSr();
        $est = $this->makeEstimate($sr, ['version' => 2]);

        $this->assertStringEndsWith('-V2', $est->displayNumber());
    }

    public function test_estimate_lock(): void
    {
        $sr = $this->createSr();
        $est = $this->makeEstimate($sr);

        $this->assertFalse($est->is_locked);
        $this->assertTrue($est->isEditable());

        $est->lock();

        $est->refresh();
        $this->assertTrue($est->is_locked);
        $this->assertNotNull($est->locked_at);
        $this->assertFalse($est->isEditable());
    }

    public function test_estimate_create_new_version(): void
    {
        $sr = $this->createSr();
        $est = $this->makeEstimate($sr, ['status' => 'sent']);

        $v2 = $est->createNewVersion();

        // Old version is locked
        $est->refresh();
        $this->assertTrue($est->is_locked);

        // New version properties
        $this->assertEquals(2, $v2->version);
        $this->assertEquals('draft', $v2->status);
        $this->assertFalse($v2->is_locked);
        $this->assertEquals($est->id, $v2->parent_version_id);
        $this->assertEquals($est->estimate_number, $v2->estimate_number);
        $this->assertEquals($est->service_request_id, $v2->service_request_id);

        // Items copied
        $this->assertCount(1, $v2->items);
        $this->assertEquals('Tire Change', $v2->items->first()->name);
    }

    public function test_estimate_all_versions(): void
    {
        $sr = $this->createSr();
        $est = $this->makeEstimate($sr, ['status' => 'sent']);
        $v2 = $est->createNewVersion();

        $versions = $est->allVersions();

        $this->assertCount(2, $versions);
        $this->assertEquals(1, $versions->first()->version);
        $this->assertEquals(2, $versions->last()->version);
    }

    // ==================================================================
    // Invoice Model – versioning logic
    // ==================================================================

    public function test_invoice_display_number_v1(): void
    {
        $sr = $this->createSr();
        $inv = $this->makeInvoice($sr);

        $this->assertSame($inv->invoice_number, $inv->displayNumber());
    }

    public function test_invoice_display_number_v2(): void
    {
        $sr = $this->createSr();
        $inv = $this->makeInvoice($sr, ['version' => 2]);

        $this->assertStringEndsWith('-V2', $inv->displayNumber());
        $this->assertStringStartsWith($inv->invoice_number, $inv->displayNumber());
    }

    public function test_invoice_lock(): void
    {
        $sr = $this->createSr();
        $inv = $this->makeInvoice($sr);

        $this->assertFalse($inv->is_locked);
        $inv->lock();

        $inv->refresh();
        $this->assertTrue($inv->is_locked);
        $this->assertNotNull($inv->locked_at);
    }

    public function test_invoice_create_new_version(): void
    {
        $sr = $this->createSr();
        $inv = $this->makeInvoice($sr, ['status' => Invoice::STATUS_SENT]);

        $v2 = $inv->createNewVersion();

        // Old version locked
        $inv->refresh();
        $this->assertTrue($inv->is_locked);

        // New version
        $this->assertEquals(2, $v2->version);
        $this->assertEquals(Invoice::STATUS_DRAFT, $v2->status);
        $this->assertFalse($v2->is_locked);
        $this->assertEquals($inv->id, $v2->parent_version_id);
        $this->assertEquals($inv->invoice_number, $v2->invoice_number);
        $this->assertEquals($inv->customer_name, $v2->customer_name);
        $this->assertEquals($inv->line_items, $v2->line_items);
    }

    public function test_invoice_all_versions(): void
    {
        $sr = $this->createSr();
        $inv = $this->makeInvoice($sr, ['status' => Invoice::STATUS_SENT]);
        $v2 = $inv->createNewVersion();

        $versions = $inv->allVersions();
        $this->assertCount(2, $versions);
    }

    // ==================================================================
    // EstimateController – revise endpoint
    // ==================================================================

    public function test_revise_sent_estimate_creates_v2(): void
    {
        $sr = $this->createSr();
        $est = $this->makeEstimate($sr, ['status' => 'sent']);
        $user = $this->user();

        $response = $this->actingAs($user)
            ->post("/service-requests/{$sr->id}/estimates/{$est->id}/revise");

        $est->refresh();
        $this->assertTrue($est->is_locked);

        $v2 = Estimate::where('parent_version_id', $est->id)->first();
        $this->assertNotNull($v2);
        $this->assertEquals(2, $v2->version);
        $this->assertEquals('draft', $v2->status);
        $this->assertFalse($v2->is_locked);

        $response->assertRedirect("/service-requests/{$sr->id}/estimates/{$v2->id}/edit");
    }

    public function test_revise_logs_service_log_event(): void
    {
        $sr = $this->createSr();
        $est = $this->makeEstimate($sr, ['status' => 'sent']);
        $user = $this->user();

        $this->actingAs($user)
            ->post("/service-requests/{$sr->id}/estimates/{$est->id}/revise");

        $this->assertDatabaseHas('service_logs', [
            'service_request_id' => $sr->id,
            'event'              => 'estimate_revised',
        ]);
    }

    public function test_revise_draft_estimate_returns_403(): void
    {
        $sr = $this->createSr();
        $est = $this->makeEstimate($sr, ['status' => 'draft']);

        $response = $this->actingAs($this->user())
            ->post("/service-requests/{$sr->id}/estimates/{$est->id}/revise");

        $response->assertForbidden();
    }

    public function test_revise_locked_estimate_returns_403(): void
    {
        $sr = $this->createSr();
        $est = $this->makeEstimate($sr, ['status' => 'sent', 'is_locked' => true, 'locked_at' => now()]);

        $response = $this->actingAs($this->user())
            ->post("/service-requests/{$sr->id}/estimates/{$est->id}/revise");

        $response->assertForbidden();
    }

    public function test_revise_accepted_estimate_returns_403(): void
    {
        $sr = $this->createSr();
        $est = $this->makeEstimate($sr, ['status' => 'accepted']);

        $response = $this->actingAs($this->user())
            ->post("/service-requests/{$sr->id}/estimates/{$est->id}/revise");

        $response->assertForbidden();
    }

    public function test_revise_declined_estimate_creates_v2(): void
    {
        $sr = $this->createSr();
        $est = $this->makeEstimate($sr, ['status' => 'declined']);

        $response = $this->actingAs($this->user())
            ->post("/service-requests/{$sr->id}/estimates/{$est->id}/revise");

        $v2 = Estimate::where('parent_version_id', $est->id)->first();
        $this->assertNotNull($v2);
        $this->assertEquals('draft', $v2->status);
        $response->assertRedirect("/service-requests/{$sr->id}/estimates/{$v2->id}/edit");
    }

    public function test_estimate_create_new_version_rejects_draft_status(): void
    {
        $sr = $this->createSr();
        $est = $this->makeEstimate($sr, ['status' => 'draft']);

        $this->expectException(\LogicException::class);
        $est->createNewVersion();
    }

    public function test_estimate_create_new_version_rejects_accepted_status(): void
    {
        $sr = $this->createSr();
        $est = $this->makeEstimate($sr, ['status' => 'accepted']);

        $this->expectException(\LogicException::class);
        $est->createNewVersion();
    }

    public function test_invoice_create_new_version_rejects_draft_status(): void
    {
        $sr = $this->createSr();
        $inv = $this->makeInvoice($sr, ['status' => Invoice::STATUS_DRAFT]);

        $this->expectException(\LogicException::class);
        $inv->createNewVersion();
    }

    public function test_invoice_create_new_version_rejects_paid_status(): void
    {
        $sr = $this->createSr();
        $inv = $this->makeInvoice($sr, ['status' => Invoice::STATUS_PAID]);

        $this->expectException(\LogicException::class);
        $inv->createNewVersion();
    }

    // ==================================================================
    // EstimateController – locked guard on edit/update/delete
    // ==================================================================

    public function test_edit_locked_estimate_returns_403(): void
    {
        $sr = $this->createSr();
        $est = $this->makeEstimate($sr, ['is_locked' => true, 'locked_at' => now()]);

        $response = $this->actingAs($this->user())
            ->get("/service-requests/{$sr->id}/estimates/{$est->id}/edit");

        $response->assertForbidden();
    }

    public function test_update_locked_estimate_returns_403(): void
    {
        $sr = $this->createSr();
        $est = $this->makeEstimate($sr, ['is_locked' => true, 'locked_at' => now()]);

        $response = $this->actingAs($this->user())
            ->put("/service-requests/{$sr->id}/estimates/{$est->id}", [
                'tax_rate' => 7,
                'items'    => [['name' => 'X', 'unit_price' => 10, 'quantity' => 1, 'unit' => 'each']],
            ]);

        $response->assertForbidden();
    }

    public function test_delete_locked_estimate_returns_403(): void
    {
        $sr = $this->createSr();
        $est = $this->makeEstimate($sr, ['is_locked' => true, 'locked_at' => now()]);

        $response = $this->actingAs($this->user())
            ->delete("/service-requests/{$sr->id}/estimates/{$est->id}");

        $response->assertForbidden();
    }

    // ==================================================================
    // InvoiceController – revise endpoint
    // ==================================================================

    public function test_revise_sent_invoice_creates_v2(): void
    {
        $sr = $this->createSr();
        $inv = $this->makeInvoice($sr, ['status' => Invoice::STATUS_SENT]);
        $user = $this->user();

        $response = $this->actingAs($user)
            ->post("/service-requests/{$sr->id}/invoices/{$inv->id}/revise");

        $inv->refresh();
        $this->assertTrue($inv->is_locked);

        $v2 = Invoice::where('parent_version_id', $inv->id)->first();
        $this->assertNotNull($v2);
        $this->assertEquals(2, $v2->version);
        $this->assertEquals(Invoice::STATUS_DRAFT, $v2->status);

        $response->assertRedirect("/service-requests/{$sr->id}/invoices/{$v2->id}/edit");
    }

    public function test_revise_invoice_logs_service_log(): void
    {
        $sr = $this->createSr();
        $inv = $this->makeInvoice($sr, ['status' => Invoice::STATUS_SENT]);

        $this->actingAs($this->user())
            ->post("/service-requests/{$sr->id}/invoices/{$inv->id}/revise");

        $this->assertDatabaseHas('service_logs', [
            'service_request_id' => $sr->id,
            'event'              => 'invoice_revised',
        ]);
    }

    public function test_revise_draft_invoice_returns_403(): void
    {
        $sr = $this->createSr();
        $inv = $this->makeInvoice($sr, ['status' => Invoice::STATUS_DRAFT]);

        $response = $this->actingAs($this->user())
            ->post("/service-requests/{$sr->id}/invoices/{$inv->id}/revise");

        $response->assertForbidden();
    }

    public function test_revise_locked_invoice_returns_403(): void
    {
        $sr = $this->createSr();
        $inv = $this->makeInvoice($sr, ['status' => Invoice::STATUS_SENT, 'is_locked' => true, 'locked_at' => now()]);

        $response = $this->actingAs($this->user())
            ->post("/service-requests/{$sr->id}/invoices/{$inv->id}/revise");

        $response->assertForbidden();
    }

    // ==================================================================
    // InvoiceController – edit / update
    // ==================================================================

    public function test_invoice_edit_page_loads_for_draft(): void
    {
        $sr = $this->createSr();
        $inv = $this->makeInvoice($sr);

        $response = $this->actingAs($this->user())
            ->get("/service-requests/{$sr->id}/invoices/{$inv->id}/edit");

        $response->assertOk();
        $response->assertViewIs('invoices.edit');
    }

    public function test_invoice_edit_locked_returns_403(): void
    {
        $sr = $this->createSr();
        $inv = $this->makeInvoice($sr, ['is_locked' => true, 'locked_at' => now()]);

        $response = $this->actingAs($this->user())
            ->get("/service-requests/{$sr->id}/invoices/{$inv->id}/edit");

        $response->assertForbidden();
    }

    public function test_invoice_update_saves_changes(): void
    {
        $sr = $this->createSr();
        $inv = $this->makeInvoice($sr);

        $response = $this->actingAs($this->user())
            ->put("/service-requests/{$sr->id}/invoices/{$inv->id}", [
                'customer_name' => 'Updated Name',
                'line_items'    => [['name' => 'Updated Item', 'quantity' => 2, 'unit' => 'ea', 'unit_price' => 30]],
                'subtotal'      => 60,
                'tax_rate'      => 0,
                'tax_amount'    => 0,
                'total'         => 60,
            ]);

        $response->assertRedirect();
        $inv->refresh();
        $this->assertEquals('Updated Name', $inv->customer_name);
        $this->assertEquals(60.00, (float) $inv->total);
    }

    public function test_invoice_update_locked_returns_403(): void
    {
        $sr = $this->createSr();
        $inv = $this->makeInvoice($sr, ['is_locked' => true, 'locked_at' => now()]);

        $response = $this->actingAs($this->user())
            ->put("/service-requests/{$sr->id}/invoices/{$inv->id}", [
                'customer_name' => 'X',
                'line_items'    => [['name' => 'Y', 'quantity' => 1, 'unit' => 'ea', 'unit_price' => 10]],
                'subtotal'      => 10,
                'tax_amount'    => 0,
                'total'         => 10,
            ]);

        $response->assertForbidden();
    }

    // ==================================================================
    // Estimate show – version history
    // ==================================================================

    public function test_estimate_show_displays_version_history(): void
    {
        $sr = $this->createSr();
        $est = $this->makeEstimate($sr, ['status' => 'sent']);
        $v2 = $est->createNewVersion();

        $response = $this->actingAs($this->user())
            ->get("/service-requests/{$sr->id}/estimates/{$v2->id}");

        $response->assertOk();
        $response->assertSee('Version History');
        $response->assertSee('V1');
        $response->assertSee('V2');
    }

    // ==================================================================
    // Invoice show – version history
    // ==================================================================

    public function test_invoice_show_displays_version_history(): void
    {
        $sr = $this->createSr();
        $inv = $this->makeInvoice($sr, ['status' => Invoice::STATUS_SENT]);
        $v2 = $inv->createNewVersion();

        $response = $this->actingAs($this->user())
            ->get("/service-requests/{$sr->id}/invoices/{$v2->id}");

        $response->assertOk();
        $response->assertSee('Version History');
    }
}
