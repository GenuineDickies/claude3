<?php

namespace Tests\Feature;

use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\PaymentRecord;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    // ── Authentication ──────────────────────────────────

    public function test_guests_cannot_view_reports(): void
    {
        $this->get(route('reports.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_reports(): void
    {
        $this->actingAs($this->createUser())
            ->get(route('reports.dashboard'))
            ->assertOk()
            ->assertViewIs('reports.dashboard');
    }

    // ── Default Date Range ─────────────────────────────

    public function test_default_range_is_30_days(): void
    {
        $this->actingAs($this->createUser())
            ->get(route('reports.dashboard'))
            ->assertOk()
            ->assertViewHas('range', '30');
    }

    // ── Date Range Filtering ───────────────────────────

    public function test_range_parameter_is_respected(): void
    {
        $user = $this->createUser();

        foreach (['7', '30', '90', '365'] as $range) {
            $this->actingAs($user)
                ->get(route('reports.dashboard', ['range' => $range]))
                ->assertOk()
                ->assertViewHas('range', $range);
        }
    }

    public function test_invalid_range_defaults_to_30(): void
    {
        $this->actingAs($this->createUser())
            ->get(route('reports.dashboard', ['range' => '999']))
            ->assertOk()
            ->assertViewHas('range', '999')
            ->assertViewHas('totalRequests', 0);
    }

    // ── Summary Card Metrics ───────────────────────────

    public function test_total_requests_count(): void
    {
        $user = $this->createUser();
        $customer = $this->createCustomer();

        // 3 requests within range
        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'new']);
        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'completed']);
        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'cancelled']);

        // 1 request outside range — use query builder to set created_at
        $oldId = ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'new'])->id;
        DB::table('service_requests')->where('id', $oldId)->update(['created_at' => now()->subDays(60)]);

        $this->actingAs($user)
            ->get(route('reports.dashboard', ['range' => '30']))
            ->assertOk()
            ->assertViewHas('totalRequests', 3);
    }

    public function test_completed_and_cancelled_counts(): void
    {
        $user = $this->createUser();
        $customer = $this->createCustomer();

        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'completed']);
        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'completed']);
        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'cancelled']);
        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'new']);

        $response = $this->actingAs($user)
            ->get(route('reports.dashboard', ['range' => '30']));

        $response->assertOk()
            ->assertViewHas('completedRequests', 2)
            ->assertViewHas('cancelledRequests', 1);
    }

    public function test_revenue_sums_payment_records(): void
    {
        $user = $this->createUser();
        $customer = $this->createCustomer();
        $sr = ServiceRequest::create(['customer_id' => $customer->id]);

        PaymentRecord::create([
            'service_request_id' => $sr->id,
            'method' => 'cash',
            'amount' => 150.00,
            'collected_at' => now(),
        ]);
        PaymentRecord::create([
            'service_request_id' => $sr->id,
            'method' => 'card',
            'amount' => 75.50,
            'collected_at' => now(),
        ]);

        // Old payment outside range
        PaymentRecord::create([
            'service_request_id' => $sr->id,
            'method' => 'cash',
            'amount' => 999.00,
            'collected_at' => now()->subDays(60),
        ]);

        $this->actingAs($user)
            ->get(route('reports.dashboard', ['range' => '30']))
            ->assertOk()
            ->assertViewHas('totalRevenue', '225.50');
    }

    public function test_expenses_sum(): void
    {
        $user = $this->createUser();

        Expense::create([
            'expense_number' => Expense::generateExpenseNumber(),
            'date' => now()->subDays(2),
            'vendor' => 'Gas Station',
            'category' => 'fuel',
            'amount' => 60.00,
        ]);
        Expense::create([
            'expense_number' => Expense::generateExpenseNumber(),
            'date' => now()->subDays(3),
            'vendor' => 'Parts Store',
            'category' => 'parts',
            'amount' => 120.00,
        ]);

        // Outside range
        Expense::create([
            'expense_number' => Expense::generateExpenseNumber(),
            'date' => now()->subDays(60),
            'vendor' => 'Old Purchase',
            'category' => 'supplies',
            'amount' => 500.00,
        ]);

        $this->actingAs($user)
            ->get(route('reports.dashboard', ['range' => '30']))
            ->assertOk()
            ->assertViewHas('totalExpenses', '180.00');
    }

    // ── Requests by Status ─────────────────────────────

    public function test_requests_by_status_grouping(): void
    {
        $user = $this->createUser();
        $customer = $this->createCustomer();

        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'new']);
        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'new']);
        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'completed']);

        $response = $this->actingAs($user)
            ->get(route('reports.dashboard', ['range' => '30']));

        $statuses = $response->viewData('requestsByStatus');
        $this->assertEquals(2, $statuses['new']);
        $this->assertEquals(1, $statuses['completed']);
    }

    // ── Top Service Types ──────────────────────────────

    public function test_top_service_types(): void
    {
        $user = $this->createUser();
        $customer = $this->createCustomer();
        $category = CatalogCategory::create(['name' => 'Services', 'type' => 'service']);

        $tow = CatalogItem::create([
            'catalog_category_id' => $category->id,
            'name' => 'Tow Service',
            'unit_price' => 100,
            'unit' => 'each',
            'pricing_type' => 'fixed',
        ]);
        $jump = CatalogItem::create([
            'catalog_category_id' => $category->id,
            'name' => 'Jump Start',
            'unit_price' => 50,
            'unit' => 'each',
            'pricing_type' => 'fixed',
        ]);

        // 3 tow, 1 jump
        ServiceRequest::create(['customer_id' => $customer->id, 'catalog_item_id' => $tow->id]);
        ServiceRequest::create(['customer_id' => $customer->id, 'catalog_item_id' => $tow->id]);
        ServiceRequest::create(['customer_id' => $customer->id, 'catalog_item_id' => $tow->id]);
        ServiceRequest::create(['customer_id' => $customer->id, 'catalog_item_id' => $jump->id]);

        $response = $this->actingAs($user)
            ->get(route('reports.dashboard', ['range' => '30']));

        $types = $response->viewData('topServiceTypes');
        $this->assertEquals('Tow Service', $types[0]['name']);
        $this->assertEquals(3, $types[0]['count']);
        $this->assertEquals('Jump Start', $types[1]['name']);
        $this->assertEquals(1, $types[1]['count']);
    }

    // ── Revenue By Period ──────────────────────────────

    public function test_revenue_by_period_returns_labels_and_data(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('reports.dashboard', ['range' => '7']))
            ->assertOk()
            ->assertViewHas('revenueByPeriod', function ($value) {
                return isset($value['labels'], $value['data'])
                    && is_array($value['labels'])
                    && is_array($value['data']);
            });
    }

    // ── Requests By Period ─────────────────────────────

    public function test_requests_by_period_returns_labels_and_data(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('reports.dashboard', ['range' => '7']))
            ->assertOk()
            ->assertViewHas('requestsByPeriod', function ($value) {
                return isset($value['labels'], $value['data'])
                    && is_array($value['labels'])
                    && is_array($value['data']);
            });
    }

    // ── Average Response Time ──────────────────────────

    public function test_avg_response_time_with_status_logs(): void
    {
        $user = $this->createUser();
        $customer = $this->createCustomer();
        $sr = ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'new']);

        // Simulate dispatch 30 minutes after creation
        DB::table('service_request_status_logs')->insert([
            'service_request_id' => $sr->id,
            'old_status' => 'new',
            'new_status' => 'dispatched',
            'created_at' => $sr->created_at->addMinutes(30),
            'updated_at' => $sr->created_at->addMinutes(30),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.dashboard', ['range' => '30']));

        $avg = $response->viewData('avgResponseMinutes');
        $this->assertNotNull($avg);
        $this->assertEqualsWithDelta(30.0, $avg, 1.0);
    }

    public function test_avg_response_time_null_when_no_logs(): void
    {
        $this->actingAs($this->createUser())
            ->get(route('reports.dashboard'))
            ->assertOk()
            ->assertViewHas('avgResponseMinutes', null);
    }

    // ── Expenses by Category ───────────────────────────

    public function test_expenses_by_category_grouping(): void
    {
        $user = $this->createUser();

        Expense::create([
            'expense_number' => Expense::generateExpenseNumber(),
            'date' => now()->subDays(1),
            'vendor' => 'Shell',
            'category' => 'fuel',
            'amount' => 60.00,
        ]);
        Expense::create([
            'expense_number' => Expense::generateExpenseNumber(),
            'date' => now()->subDays(2),
            'vendor' => 'Shell',
            'category' => 'fuel',
            'amount' => 40.00,
        ]);
        Expense::create([
            'expense_number' => Expense::generateExpenseNumber(),
            'date' => now()->subDays(3),
            'vendor' => 'AutoZone',
            'category' => 'parts',
            'amount' => 85.00,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.dashboard', ['range' => '30']));

        $categories = collect($response->viewData('expensesByCategory'));
        $fuel = $categories->firstWhere('category', 'fuel');
        $parts = $categories->firstWhere('category', 'parts');

        $this->assertEquals('100.00', $fuel['total']);
        $this->assertEquals('85.00', $parts['total']);
    }

    // ── View Content ───────────────────────────────────

    public function test_dashboard_displays_summary_cards(): void
    {
        $user = $this->createUser();
        $customer = $this->createCustomer();

        ServiceRequest::create(['customer_id' => $customer->id, 'status' => 'completed']);

        $this->actingAs($user)
            ->get(route('reports.dashboard'))
            ->assertOk()
            ->assertSee('Total Requests')
            ->assertSee('Completed')
            ->assertSee('Revenue')
            ->assertSee('Avg Response');
    }

    public function test_dashboard_shows_date_range_links(): void
    {
        $this->actingAs($this->createUser())
            ->get(route('reports.dashboard'))
            ->assertOk()
            ->assertSee('7 days')
            ->assertSee('30 days')
            ->assertSee('90 days')
            ->assertSee('1 year');
    }

    // ── Edge Cases ─────────────────────────────────────

    public function test_empty_dashboard_renders_without_errors(): void
    {
        $this->actingAs($this->createUser())
            ->get(route('reports.dashboard'))
            ->assertOk()
            ->assertViewHas('totalRequests', 0)
            ->assertViewHas('completedRequests', 0)
            ->assertViewHas('totalRevenue', '0')
            ->assertViewHas('totalExpenses', '0');
    }

    public function test_yearly_range_uses_monthly_grouping(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('reports.dashboard', ['range' => '365']))
            ->assertOk()
            ->assertViewHas('revenueByPeriod', function ($value) {
                // Monthly labels like "Jan 2025"
                if (empty($value['labels'])) {
                    return true;
                }
                return (bool) preg_match('/^[A-Z][a-z]{2} \d{4}$/', $value['labels'][0]);
            });
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
}
