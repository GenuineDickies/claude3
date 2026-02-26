<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\Invoice;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create();
    }

    private function createServiceRequest(): ServiceRequest
    {
        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'phone'      => '5559876543',
            'is_active'  => true,
        ]);

        return ServiceRequest::create([
            'customer_id'   => $customer->id,
            'status'        => 'completed',
            'vehicle_year'  => '2020',
            'vehicle_make'  => 'Toyota',
            'vehicle_model' => 'Camry',
            'vehicle_color' => 'Blue',
            'location'      => '123 Main St, Tampa FL',
        ]);
    }

    private function createEstimateForSr(ServiceRequest $sr): Estimate
    {
        $estimate = Estimate::create([
            'service_request_id' => $sr->id,
            'state_code'   => 'FL',
            'tax_rate'     => 7.0,
            'subtotal'     => 100.00,
            'tax_amount'   => 7.00,
            'total'        => 107.00,
            'status'       => 'accepted',
        ]);

        EstimateItem::create([
            'estimate_id' => $estimate->id,
            'name'        => 'Tire Change',
            'description' => 'Replace flat tire',
            'unit_price'  => 75.00,
            'quantity'     => 1,
            'unit'        => 'ea',
            'sort_order'  => 0,
        ]);

        EstimateItem::create([
            'estimate_id' => $estimate->id,
            'name'        => 'Service Fee',
            'unit_price'  => 25.00,
            'quantity'     => 1,
            'unit'        => 'ea',
            'sort_order'  => 1,
        ]);

        return $estimate;
    }

    private function createInvoice(ServiceRequest $sr, ?User $user = null, array $overrides = []): Invoice
    {
        return Invoice::create(array_merge([
            'service_request_id' => $sr->id,
            'invoice_number'     => Invoice::generateInvoiceNumber(),
            'status'             => 'draft',
            'customer_name'      => 'Jane Doe',
            'line_items'         => [['name' => 'Service', 'quantity' => 1, 'unit_price' => 50]],
            'subtotal'           => 50,
            'tax_amount'         => 0,
            'total'              => 50,
            'issued_by'          => $user?->id,
            'company_snapshot'   => ['name' => 'Test Co', 'address' => '', 'phone' => '', 'email' => ''],
        ], $overrides));
    }

    // ── Create page ────────────────────────────────────────────

    public function test_create_page_loads_with_auto_populated_data(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();
        $this->createEstimateForSr($sr);

        $response = $this->actingAs($user)->get(route('invoices.create', $sr));

        $response->assertOk();
        $response->assertSee('Create Invoice');
        $response->assertSee('Jane Doe');
        $response->assertSee('5559876543');
        $response->assertSee('Blue 2020 Toyota Camry');
    }

    public function test_create_page_loads_without_estimate(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        $response = $this->actingAs($user)->get(route('invoices.create', $sr));

        $response->assertOk();
    }

    // ── Store ──────────────────────────────────────────────────

    public function test_store_creates_invoice_with_snapshot(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        Setting::setValue('company_name', 'Test Company');
        Setting::setValue('company_phone', '555-0000');

        $response = $this->actingAs($user)->post(route('invoices.store', $sr), [
            'customer_name'       => 'Jane Doe',
            'customer_phone'      => '5559876543',
            'vehicle_description' => 'Blue 2020 Toyota Camry',
            'service_description' => 'Tire Change',
            'service_location'    => '123 Main St',
            'line_items'          => [
                ['name' => 'Tire Change', 'quantity' => 1, 'unit' => 'ea', 'unit_price' => 75],
                ['name' => 'Service Fee', 'quantity' => 1, 'unit' => 'ea', 'unit_price' => 25],
            ],
            'subtotal'       => 100,
            'tax_rate'       => 7,
            'tax_amount'     => 7,
            'total'          => 107,
            'due_date'       => '2026-03-24',
            'payment_terms'  => 'Net 30',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $invoice = Invoice::first();
        $this->assertNotNull($invoice);
        $this->assertStringStartsWith('INV-', $invoice->invoice_number);
        $this->assertEquals('Jane Doe', $invoice->customer_name);
        $this->assertEquals(107, $invoice->total);
        $this->assertEquals('draft', $invoice->status);
        $this->assertEquals('Test Company', $invoice->company_snapshot['name']);
        $this->assertEquals($user->id, $invoice->issued_by);
        $this->assertEquals('Net 30', $invoice->payment_terms);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        $response = $this->actingAs($user)->post(route('invoices.store', $sr), []);

        $response->assertSessionHasErrors(['customer_name', 'line_items', 'subtotal', 'tax_amount', 'total']);
    }

    // ── Show ───────────────────────────────────────────────────

    public function test_show_displays_invoice_details(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        $invoice = $this->createInvoice($sr, $user, ['invoice_number' => 'INV-20260224-0001']);

        $response = $this->actingAs($user)->get(route('invoices.show', [$sr, $invoice]));

        $response->assertOk();
        $response->assertSee('INV-20260224-0001');
        $response->assertSee('Jane Doe');
        $response->assertSee('$50.00');
    }

    public function test_show_returns_404_for_wrong_sr(): void
    {
        $user = $this->createUser();
        $sr1 = $this->createServiceRequest();
        $sr2 = $this->createServiceRequest();

        $invoice = $this->createInvoice($sr1, $user);

        $response = $this->actingAs($user)->get(route('invoices.show', [$sr2, $invoice]));
        $response->assertNotFound();
    }

    // ── Status update ──────────────────────────────────────────

    public function test_update_status_to_sent(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();
        $invoice = $this->createInvoice($sr, $user);

        $response = $this->actingAs($user)->patch(route('invoices.update-status', [$sr, $invoice]), [
            'status' => 'sent',
        ]);

        $response->assertRedirect();
        $this->assertEquals('sent', $invoice->fresh()->status);
    }

    public function test_update_status_to_paid(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();
        $invoice = $this->createInvoice($sr, $user, ['status' => 'sent']);

        $response = $this->actingAs($user)->patch(route('invoices.update-status', [$sr, $invoice]), [
            'status' => 'paid',
        ]);

        $response->assertRedirect();
        $this->assertEquals('paid', $invoice->fresh()->status);
    }

    public function test_update_status_rejects_invalid_status(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();
        $invoice = $this->createInvoice($sr, $user);

        $response = $this->actingAs($user)->patch(route('invoices.update-status', [$sr, $invoice]), [
            'status' => 'invalid',
        ]);

        $response->assertSessionHasErrors('status');
    }

    public function test_update_status_returns_404_for_wrong_sr(): void
    {
        $user = $this->createUser();
        $sr1 = $this->createServiceRequest();
        $sr2 = $this->createServiceRequest();
        $invoice = $this->createInvoice($sr1, $user);

        $response = $this->actingAs($user)->patch(route('invoices.update-status', [$sr2, $invoice]), [
            'status' => 'sent',
        ]);

        $response->assertNotFound();
    }

    // ── PDF ────────────────────────────────────────────────────

    public function test_pdf_download_returns_pdf_content_type(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        $invoice = $this->createInvoice($sr, $user, [
            'invoice_number'      => 'INV-20260224-0003',
            'customer_phone'      => '5559876543',
            'vehicle_description' => '2020 Toyota Camry',
            'service_description' => 'Tire Change',
            'line_items'          => [['name' => 'Tire Change', 'quantity' => 1, 'unit' => 'ea', 'unit_price' => 75]],
            'subtotal'            => 75,
            'tax_rate'            => 7,
            'tax_amount'          => 5.25,
            'total'               => 80.25,
            'due_date'            => '2026-03-24',
            'payment_terms'       => 'Net 30',
            'company_snapshot'    => ['name' => 'Test Company', 'address' => '123 Test St', 'phone' => '555-0000', 'email' => 'test@example.com'],
        ]);

        $response = $this->actingAs($user)->get(route('invoices.pdf', [$sr, $invoice]));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_pdf_returns_404_for_wrong_sr(): void
    {
        $user = $this->createUser();
        $sr1 = $this->createServiceRequest();
        $sr2 = $this->createServiceRequest();
        $invoice = $this->createInvoice($sr1, $user);

        $response = $this->actingAs($user)->get(route('invoices.pdf', [$sr2, $invoice]));
        $response->assertNotFound();
    }

    // ── Invoice number generation ─────────────────────────────

    public function test_invoice_number_auto_increments(): void
    {
        $sr = $this->createServiceRequest();

        $this->createInvoice($sr);

        $second = Invoice::generateInvoiceNumber();
        $this->assertStringEndsWith('-0002', $second);
    }

    // ── Snapshot immutability ──────────────────────────────────

    public function test_snapshot_data_unchanged_when_company_data_changes(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        $invoice = $this->createInvoice($sr, $user, [
            'company_snapshot' => ['name' => 'Original Co', 'address' => '', 'phone' => '', 'email' => ''],
        ]);

        Setting::setValue('company_name', 'New Company Name');

        $invoice->refresh();
        $this->assertEquals('Original Co', $invoice->company_snapshot['name']);
    }

    // ── Auth ───────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_access_invoices(): void
    {
        $sr = $this->createServiceRequest();

        $this->get(route('invoices.create', $sr))->assertRedirect(route('login'));
        $this->post(route('invoices.store', $sr))->assertRedirect(route('login'));
    }

    // ── ServiceRequest relationship ───────────────────────────

    public function test_service_request_has_invoices_relationship(): void
    {
        $sr = $this->createServiceRequest();
        $this->createInvoice($sr);
        $this->createInvoice($sr, null, ['invoice_number' => 'INV-20260224-9999']);

        $this->assertCount(2, $sr->invoices);
    }
}
