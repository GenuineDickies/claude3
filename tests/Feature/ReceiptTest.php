<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\Receipt;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptTest extends TestCase
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
            'customer_id'  => $customer->id,
            'status'       => 'completed',
            'vehicle_year' => '2020',
            'vehicle_make' => 'Toyota',
            'vehicle_model' => 'Camry',
            'vehicle_color' => 'Blue',
            'location'     => '123 Main St, Tampa FL',
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
            'quantity'    => 1,
            'unit'        => 'ea',
            'sort_order'  => 0,
        ]);

        EstimateItem::create([
            'estimate_id' => $estimate->id,
            'name'        => 'Service Fee',
            'unit_price'  => 25.00,
            'quantity'    => 1,
            'unit'        => 'ea',
            'sort_order'  => 1,
        ]);

        return $estimate;
    }

    // ── Create page ────────────────────────────────────────────

    public function test_create_page_loads_with_auto_populated_data(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();
        $this->createEstimateForSr($sr);

        $response = $this->actingAs($user)->get(route('receipts.create', $sr));

        $response->assertOk();
        $response->assertSee('Issue Receipt');
        $response->assertSee('Jane Doe');
        $response->assertSee('5559876543');
        $response->assertSee('Blue 2020 Toyota Camry');
    }

    public function test_create_page_loads_without_estimate(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        $response = $this->actingAs($user)->get(route('receipts.create', $sr));

        $response->assertOk();
    }

    // ── Store ──────────────────────────────────────────────────

    public function test_store_creates_receipt_with_snapshot(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        Setting::setValue('company_name', 'Test Company');
        Setting::setValue('company_phone', '555-0000');

        $response = $this->actingAs($user)->post(route('receipts.store', $sr), [
            'customer_name'      => 'Jane Doe',
            'customer_phone'     => '5559876543',
            'vehicle_description' => 'Blue 2020 Toyota Camry',
            'service_description' => 'Tire Change',
            'service_location'   => '123 Main St',
            'line_items'         => [
                ['name' => 'Tire Change', 'quantity' => 1, 'unit' => 'ea', 'unit_price' => 75],
                ['name' => 'Service Fee', 'quantity' => 1, 'unit' => 'ea', 'unit_price' => 25],
            ],
            'subtotal'       => 100,
            'tax_rate'       => 7,
            'tax_amount'     => 7,
            'total'          => 107,
            'payment_method' => 'card',
            'payment_date'   => '2026-02-24',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $receipt = Receipt::first();
        $this->assertNotNull($receipt);
        $this->assertStringStartsWith('R-', $receipt->receipt_number);
        $this->assertEquals('Jane Doe', $receipt->customer_name);
        $this->assertEquals(107, $receipt->total);
        $this->assertEquals('Test Company', $receipt->company_snapshot['name']);
        $this->assertEquals($user->id, $receipt->issued_by);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        $response = $this->actingAs($user)->post(route('receipts.store', $sr), []);

        $response->assertSessionHasErrors(['customer_name', 'line_items', 'subtotal', 'tax_amount', 'total']);
    }

    // ── Show ───────────────────────────────────────────────────

    public function test_show_displays_receipt_details(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        $receipt = Receipt::create([
            'service_request_id' => $sr->id,
            'receipt_number'     => 'R-20260224-0001',
            'customer_name'      => 'Jane Doe',
            'line_items'         => [['name' => 'Service', 'quantity' => 1, 'unit_price' => 50]],
            'subtotal'           => 50,
            'tax_amount'         => 0,
            'total'              => 50,
            'issued_by'          => $user->id,
            'company_snapshot'   => ['name' => 'Test Co', 'address' => '', 'phone' => '', 'email' => ''],
        ]);

        $response = $this->actingAs($user)->get(route('receipts.show', [$sr, $receipt]));

        $response->assertOk();
        $response->assertSee('R-20260224-0001');
        $response->assertSee('Jane Doe');
        $response->assertSee('$50.00');
    }

    public function test_show_returns_404_for_wrong_sr(): void
    {
        $user = $this->createUser();
        $sr1 = $this->createServiceRequest();
        $sr2 = $this->createServiceRequest();

        $receipt = Receipt::create([
            'service_request_id' => $sr1->id,
            'receipt_number'     => 'R-20260224-0002',
            'customer_name'      => 'Jane Doe',
            'line_items'         => [['name' => 'Service', 'quantity' => 1, 'unit_price' => 50]],
            'subtotal'           => 50,
            'tax_amount'         => 0,
            'total'              => 50,
            'issued_by'          => $user->id,
            'company_snapshot'   => ['name' => 'Test Co', 'address' => '', 'phone' => '', 'email' => ''],
        ]);

        $response = $this->actingAs($user)->get(route('receipts.show', [$sr2, $receipt]));
        $response->assertNotFound();
    }

    // ── PDF ────────────────────────────────────────────────────

    public function test_pdf_download_returns_pdf_content_type(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        $receipt = Receipt::create([
            'service_request_id' => $sr->id,
            'receipt_number'     => 'R-20260224-0003',
            'customer_name'      => 'Jane Doe',
            'customer_phone'     => '5559876543',
            'vehicle_description' => '2020 Toyota Camry',
            'service_description' => 'Tire Change',
            'line_items'         => [['name' => 'Tire Change', 'quantity' => 1, 'unit' => 'ea', 'unit_price' => 75]],
            'subtotal'           => 75,
            'tax_rate'           => 7,
            'tax_amount'         => 5.25,
            'total'              => 80.25,
            'payment_method'     => 'cash',
            'payment_date'       => '2026-02-24',
            'issued_by'          => $user->id,
            'company_snapshot'   => ['name' => 'Test Company', 'address' => '123 Test St', 'phone' => '555-0000', 'email' => 'test@example.com'],
        ]);

        $response = $this->actingAs($user)->get(route('receipts.pdf', [$sr, $receipt]));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    // ── Receipt number generation ─────────────────────────────

    public function test_receipt_number_auto_increments(): void
    {
        $sr = $this->createServiceRequest();

        Receipt::create([
            'service_request_id' => $sr->id,
            'receipt_number'     => Receipt::generateReceiptNumber(),
            'customer_name'      => 'Test',
            'line_items'         => [['name' => 'X', 'quantity' => 1, 'unit_price' => 10]],
            'subtotal'           => 10,
            'tax_amount'         => 0,
            'total'              => 10,
            'company_snapshot'   => ['name' => 'Co'],
        ]);

        $second = Receipt::generateReceiptNumber();
        $this->assertStringEndsWith('-0002', $second);
    }

    // ── Snapshot immutability ──────────────────────────────────

    public function test_snapshot_data_unchanged_when_sr_data_changes(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        $receipt = Receipt::create([
            'service_request_id' => $sr->id,
            'receipt_number'     => 'R-20260224-0010',
            'customer_name'      => 'Jane Doe',
            'line_items'         => [['name' => 'Service', 'quantity' => 1, 'unit_price' => 50]],
            'subtotal'           => 50,
            'tax_amount'         => 0,
            'total'              => 50,
            'company_snapshot'   => ['name' => 'Original Co', 'address' => '', 'phone' => '', 'email' => ''],
        ]);

        // Change the company name setting
        Setting::setValue('company_name', 'New Company Name');

        // Reload the receipt — snapshot should still be "Original Co"
        $receipt->refresh();
        $this->assertEquals('Original Co', $receipt->company_snapshot['name']);
    }

    // ── SR show page integration ──────────────────────────────

    public function test_sr_show_page_displays_receipt_section(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        $response = $this->actingAs($user)->get(route('service-requests.show', $sr));
        $response->assertOk();
        $response->assertSee('Issue Receipt');
    }

    public function test_sr_show_page_shows_existing_receipt(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        Receipt::create([
            'service_request_id' => $sr->id,
            'receipt_number'     => 'R-20260224-0020',
            'customer_name'      => 'Jane Doe',
            'line_items'         => [['name' => 'X', 'quantity' => 1, 'unit_price' => 50]],
            'subtotal'           => 50,
            'tax_amount'         => 0,
            'total'              => 50,
            'company_snapshot'   => ['name' => 'Co'],
        ]);

        $response = $this->actingAs($user)->get(route('service-requests.show', $sr));
        $response->assertOk();
        $response->assertSee('R-20260224-0020');
        $response->assertSee('$50.00');
    }

    // ── Auth ───────────────────────────────────────────────────

    public function test_receipt_routes_require_authentication(): void
    {
        $sr = $this->createServiceRequest();

        $this->get(route('receipts.create', $sr))->assertRedirect(route('login'));
        $this->post(route('receipts.store', $sr))->assertRedirect(route('login'));
    }
}
