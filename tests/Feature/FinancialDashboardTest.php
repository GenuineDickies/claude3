<?php

namespace Tests\Feature;

use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PaymentRecord;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialDashboardTest extends TestCase
{
    use RefreshDatabase;

    // ── Authentication ──────────────────────────────────

    public function test_guests_cannot_view_financial_dashboard(): void
    {
        $this->get(route('reports.financial'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_financial_dashboard(): void
    {
        $this->actingAs($this->createUser())
            ->get(route('reports.financial'))
            ->assertOk()
            ->assertViewIs('reports.financial');
    }

    // ── Date Range ──────────────────────────────────────

    public function test_default_range_is_30(): void
    {
        $this->actingAs($this->createUser())
            ->get(route('reports.financial'))
            ->assertOk()
            ->assertViewHas('range', '30');
    }

    public function test_range_parameter_is_respected(): void
    {
        $user = $this->createUser();

        foreach (['1', '7', '30', '90'] as $range) {
            $this->actingAs($user)
                ->get(route('reports.financial', ['range' => $range]))
                ->assertOk()
                ->assertViewHas('range', $range);
        }
    }

    // ── Paid Revenue ────────────────────────────────────

    public function test_paid_revenue_sums_payments_in_range(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        PaymentRecord::create([
            'service_request_id' => $sr->id,
            'method' => 'cash',
            'amount' => 200.00,
            'collected_at' => now(),
        ]);
        PaymentRecord::create([
            'service_request_id' => $sr->id,
            'method' => 'card',
            'amount' => 50.00,
            'collected_at' => now()->subDays(5),
        ]);

        // Outside range
        PaymentRecord::create([
            'service_request_id' => $sr->id,
            'method' => 'cash',
            'amount' => 999.00,
            'collected_at' => now()->subDays(60),
        ]);

        $this->actingAs($user)
            ->get(route('reports.financial', ['range' => '30']))
            ->assertOk()
            ->assertViewHas('paidRevenue', '250.00');
    }

    // ── Outstanding A/R ─────────────────────────────────

    public function test_outstanding_ar_includes_sent_and_overdue_invoices(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        $this->createInvoice($sr, ['status' => 'sent', 'total' => 300.00]);
        $this->createInvoice($sr, ['status' => 'overdue', 'total' => 150.00]);
        $this->createInvoice($sr, ['status' => 'paid', 'total' => 500.00]); // not counted
        $this->createInvoice($sr, ['status' => 'draft', 'total' => 100.00]); // not counted

        $response = $this->actingAs($user)
            ->get(route('reports.financial'));

        $response->assertOk();
        $this->assertEquals(450.00, (float) $response->viewData('outstandingAR'));
    }

    // ── Overdue A/R ─────────────────────────────────────

    public function test_overdue_ar_counts_overdue_and_past_due_sent(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        // Overdue status invoice
        $this->createInvoice($sr, ['status' => 'overdue', 'total' => 200.00]);

        // Sent but past due_date
        $this->createInvoice($sr, [
            'status' => 'sent',
            'total' => 100.00,
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

        // Sent with future due_date — NOT overdue
        $this->createInvoice($sr, [
            'status' => 'sent',
            'total' => 50.00,
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.financial'));

        $response->assertOk();
        $this->assertEquals(300.00, (float) $response->viewData('overdueAR'));
    }

    // ── Collections Rate ────────────────────────────────

    public function test_collections_rate_calculation(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        // Create invoices in range (billed = 400)
        $this->createInvoice($sr, ['status' => 'paid', 'total' => 200.00]);
        $this->createInvoice($sr, ['status' => 'sent', 'total' => 200.00]);

        // Payments in range (collected = 200)
        PaymentRecord::create([
            'service_request_id' => $sr->id,
            'method' => 'cash',
            'amount' => 200.00,
            'collected_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.financial', ['range' => '30']));

        $response->assertOk();
        $this->assertEquals(50.0, $response->viewData('collectionsRate'));
    }

    public function test_collections_rate_null_when_no_billed(): void
    {
        $this->actingAs($this->createUser())
            ->get(route('reports.financial'))
            ->assertOk()
            ->assertViewHas('collectionsRate', null);
    }

    // ── Avg Ticket Value ────────────────────────────────

    public function test_avg_ticket_value(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        $this->createInvoice($sr, ['status' => 'paid', 'total' => 300.00]);
        $this->createInvoice($sr, ['status' => 'paid', 'total' => 100.00]);

        $response = $this->actingAs($user)
            ->get(route('reports.financial', ['range' => '30']));

        $response->assertOk();
        $this->assertEquals(200.00, $response->viewData('avgTicketValue'));
    }

    // ── Margin Proxy ────────────────────────────────────

    public function test_margin_proxy_calculation(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        // Revenue = 1000
        PaymentRecord::create([
            'service_request_id' => $sr->id,
            'method' => 'cash',
            'amount' => 1000.00,
            'collected_at' => now(),
        ]);

        // Expenses = 400
        Expense::create([
            'expense_number' => Expense::generateExpenseNumber(),
            'date' => now()->subDays(1),
            'vendor' => 'Parts',
            'category' => 'parts',
            'amount' => 400.00,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.financial', ['range' => '30']));

        // (1000 - 400) / 1000 * 100 = 60%
        $this->assertEquals(60.0, $response->viewData('marginProxy'));
    }

    // ── A/R Aging Buckets ───────────────────────────────

    public function test_ar_aging_buckets(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        // Current (due in future)
        $this->createInvoice($sr, [
            'status' => 'sent',
            'total' => 100.00,
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        // 1-30 days overdue
        $this->createInvoice($sr, [
            'status' => 'sent',
            'total' => 200.00,
            'due_date' => now()->subDays(15)->toDateString(),
        ]);

        // 31-60 days overdue
        $this->createInvoice($sr, [
            'status' => 'sent',
            'total' => 300.00,
            'due_date' => now()->subDays(45)->toDateString(),
        ]);

        // 61+ days overdue
        $this->createInvoice($sr, [
            'status' => 'overdue',
            'total' => 400.00,
            'due_date' => now()->subDays(90)->toDateString(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.financial'));

        $buckets = $response->viewData('agingBuckets');
        $this->assertEquals(100.00, $buckets['current']);
        $this->assertEquals(200.00, $buckets['1_30']);
        $this->assertEquals(300.00, $buckets['31_60']);
        $this->assertEquals(400.00, $buckets['61_plus']);
    }

    // ── Trend Data ──────────────────────────────────────

    public function test_trend_data_has_14_days(): void
    {
        $this->actingAs($this->createUser())
            ->get(route('reports.financial'))
            ->assertOk()
            ->assertViewHas('trendData', function ($data) {
                return count($data['labels']) === 14
                    && count($data['jobsReceived']) === 14
                    && count($data['jobsCompleted']) === 14
                    && count($data['revenueReceived']) === 14;
            });
    }

    public function test_trend_jobs_received_counts(): void
    {
        $user = $this->createUser();
        $customer = $this->createCustomer();

        // Create 2 requests today
        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'new']);
        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'new']);

        $response = $this->actingAs($user)
            ->get(route('reports.financial'));

        $trend = $response->viewData('trendData');
        // Last element should be today's count
        $this->assertEquals(2, end($trend['jobsReceived']));
    }

    // ── Operational Metrics ─────────────────────────────

    public function test_completion_rate(): void
    {
        $user = $this->createUser();
        $customer = $this->createCustomer();

        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'completed']);
        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'completed']);
        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'new']);
        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'cancelled']);

        $response = $this->actingAs($user)
            ->get(route('reports.financial', ['range' => '30']));

        // 2 completed / 4 total = 50%
        $this->assertEquals(50.0, $response->viewData('completionRate'));
    }

    public function test_open_queue_counts_active_statuses(): void
    {
        $user = $this->createUser();
        $customer = $this->createCustomer();

        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'new']);
        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'dispatched']);
        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'en_route']);
        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'completed']); // not counted
        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'cancelled']);  // not counted

        $this->actingAs($user)
            ->get(route('reports.financial'))
            ->assertOk()
            ->assertViewHas('openQueue', 3);
    }

    public function test_urgent_unassigned_count(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        // Urgent + unassigned (pending)
        WorkOrder::create([
            'service_request_id' => $sr->id,
            'work_order_number' => WorkOrder::generateWorkOrderNumber(),
            'status' => 'pending',
            'priority' => 'urgent',
            'assigned_to' => null,
        ]);

        // Urgent + assigned — not counted
        WorkOrder::create([
            'service_request_id' => $sr->id,
            'work_order_number' => WorkOrder::generateWorkOrderNumber(),
            'status' => 'pending',
            'priority' => 'urgent',
            'assigned_to' => 'John Doe',
        ]);

        // Normal priority + unassigned — not counted
        WorkOrder::create([
            'service_request_id' => $sr->id,
            'work_order_number' => WorkOrder::generateWorkOrderNumber(),
            'status' => 'pending',
            'priority' => 'normal',
            'assigned_to' => null,
        ]);

        $this->actingAs($user)
            ->get(route('reports.financial'))
            ->assertOk()
            ->assertViewHas('urgentUnassigned', 1);
    }

    // ── Top Technicians ─────────────────────────────────

    public function test_top_technicians_ranked_by_jobs(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        // Alice: 2 completed jobs
        WorkOrder::create([
            'service_request_id' => $sr->id,
            'work_order_number' => WorkOrder::generateWorkOrderNumber(),
            'status' => 'completed',
            'priority' => 'normal',
            'assigned_to' => 'Alice',
            'completed_at' => now()->subDays(1),
            'total' => 150.00,
        ]);
        WorkOrder::create([
            'service_request_id' => $sr->id,
            'work_order_number' => WorkOrder::generateWorkOrderNumber(),
            'status' => 'completed',
            'priority' => 'normal',
            'assigned_to' => 'Alice',
            'completed_at' => now()->subDays(2),
            'total' => 200.00,
        ]);

        // Bob: 1 completed job
        WorkOrder::create([
            'service_request_id' => $sr->id,
            'work_order_number' => WorkOrder::generateWorkOrderNumber(),
            'status' => 'completed',
            'priority' => 'normal',
            'assigned_to' => 'Bob',
            'completed_at' => now()->subDays(3),
            'total' => 300.00,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.financial', ['range' => '30']));

        $techs = $response->viewData('topTechnicians');
        $this->assertCount(2, $techs);
        $this->assertEquals('Alice', $techs[0]['name']);
        $this->assertEquals(2, $techs[0]['jobs']);
        $this->assertEquals('350.00', $techs[0]['revenue']);
        $this->assertEquals('Bob', $techs[1]['name']);
    }

    // ── Service Mix ─────────────────────────────────────

    public function test_service_mix_by_type(): void
    {
        $user = $this->createUser();
        $customer = $this->createCustomer();
        $category = CatalogCategory::create(['name' => 'Services', 'type' => 'service']);
        $tow = CatalogItem::create([
            'catalog_category_id' => $category->id,
            'name' => 'Tow',
            'unit_price' => 100,
            'unit' => 'each',
            'pricing_type' => 'fixed',
        ]);

        ServiceRequest::create(['customer_id' => $customer->id, 'catalog_item_id' => $tow->id]);
        ServiceRequest::create(['customer_id' => $customer->id, 'catalog_item_id' => $tow->id]);

        $response = $this->actingAs($user)
            ->get(route('reports.financial', ['range' => '30']));

        $mix = $response->viewData('serviceMix');
        $this->assertEquals('Tow', $mix[0]['name']);
        $this->assertEquals(2, $mix[0]['count']);
    }

    // ── View Content ────────────────────────────────────

    public function test_view_renders_all_sections(): void
    {
        $this->actingAs($this->createUser())
            ->get(route('reports.financial'))
            ->assertOk()
            ->assertSee('Financial Dashboard')
            ->assertSee('Paid Revenue')
            ->assertSee('Outstanding A/R')
            ->assertSee('Overdue A/R')
            ->assertSee('Collections Rate')
            ->assertSee('Avg Ticket Value')
            ->assertSee('Margin Proxy')
            ->assertSee('Accounts Receivable Aging')
            ->assertSee('Operational Metrics')
            ->assertSee('Completion Rate');
    }

    public function test_date_range_links_displayed(): void
    {
        $this->actingAs($this->createUser())
            ->get(route('reports.financial'))
            ->assertOk()
            ->assertSee('Today')
            ->assertSee('7 days')
            ->assertSee('30 days')
            ->assertSee('90 days');
    }

    // ── Empty State ─────────────────────────────────────

    public function test_empty_state_renders_without_errors(): void
    {
        $response = $this->actingAs($this->createUser())
            ->get(route('reports.financial'));

        $response->assertOk();
        $this->assertEquals(0, (float) $response->viewData('paidRevenue'));
        $this->assertEquals(0, (float) $response->viewData('outstandingAR'));
        $this->assertEquals(0, (float) $response->viewData('overdueAR'));
        $this->assertNull($response->viewData('collectionsRate'));
        $this->assertNull($response->viewData('avgTicketValue'));
        $this->assertNull($response->viewData('marginProxy'));
    }

    // ── Helpers ─────────────────────────────────────────

    private function createUser(): User
    {
        return User::factory()->create();
    }

    private function createCustomer(): Customer
    {
        return Customer::create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'phone' => '5551234567',
        ]);
    }

    private function createServiceRequest(): ServiceRequest
    {
        return ServiceRequest::create([
            'customer_id' => $this->createCustomer()->id,
            'status' => 'new',
        ]);
    }

    private function createInvoice(ServiceRequest $sr, array $overrides = []): Invoice
    {
        return Invoice::create(array_merge([
            'service_request_id' => $sr->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'status' => 'draft',
            'customer_name' => 'Test Customer',
            'customer_phone' => '5551234567',
            'line_items' => [],
            'subtotal' => 0,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'company_snapshot' => [],
        ], $overrides));
    }

    // ── A/R Deducts Partial Payments ────────────────────

    public function test_outstanding_ar_deducts_partial_payments(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        $invoice = $this->createInvoice($sr, ['status' => 'sent', 'total' => 1000.00]);

        // Partial payment of $300 applied to this invoice
        PaymentRecord::create([
            'service_request_id' => $sr->id,
            'invoice_id'         => $invoice->id,
            'method'             => 'cash',
            'amount'             => 300.00,
            'collected_at'       => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.financial'));

        $response->assertOk();
        // Should be 1000 - 300 = 700, not 1000
        $this->assertEquals(700.00, (float) $response->viewData('outstandingAR'));
    }

    public function test_overdue_ar_deducts_partial_payments(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        $invoice = $this->createInvoice($sr, [
            'status'   => 'overdue',
            'total'    => 500.00,
            'due_date' => now()->subDays(10)->toDateString(),
        ]);

        PaymentRecord::create([
            'service_request_id' => $sr->id,
            'invoice_id'         => $invoice->id,
            'method'             => 'card',
            'amount'             => 200.00,
            'collected_at'       => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.financial'));

        $response->assertOk();
        $this->assertEquals(300.00, (float) $response->viewData('overdueAR'));
    }

    public function test_aging_buckets_deduct_partial_payments(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        $invoice = $this->createInvoice($sr, [
            'status'   => 'sent',
            'total'    => 400.00,
            'due_date' => now()->subDays(15)->toDateString(),
        ]);

        PaymentRecord::create([
            'service_request_id' => $sr->id,
            'invoice_id'         => $invoice->id,
            'method'             => 'cash',
            'amount'             => 150.00,
            'collected_at'       => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.financial'));

        $buckets = $response->viewData('agingBuckets');
        // 400 - 150 = 250 in the 1-30 day bucket
        $this->assertEquals(250.00, $buckets['1_30']);
    }

    public function test_fully_paid_invoice_excluded_from_ar(): void
    {
        $user = $this->createUser();
        $sr = $this->createServiceRequest();

        $invoice = $this->createInvoice($sr, ['status' => 'sent', 'total' => 200.00]);

        PaymentRecord::create([
            'service_request_id' => $sr->id,
            'invoice_id'         => $invoice->id,
            'method'             => 'cash',
            'amount'             => 200.00,
            'collected_at'       => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.financial'));

        $response->assertOk();
        $this->assertEquals(0, (float) $response->viewData('outstandingAR'));
    }
}
