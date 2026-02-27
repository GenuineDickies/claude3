<?php

namespace Tests\Feature;

use App\Models\ChangeOrder;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\ChangeOrderAuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ChangeOrderAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_order_requires_approval_when_delta_exceeds_threshold(): void
    {
        $service = app(ChangeOrderAuthorizationService::class);

        // estimate total 1000 => threshold min(200,100)=100
        $this->assertTrue($service->requiresApproval(1000, 1150));
        $this->assertFalse($service->requiresApproval(1000, 1090));
    }

    public function test_invoice_status_change_is_not_blocked_by_change_orders(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'phone' => '15551112222',
            'is_active' => true,
        ]);

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $customer->id,
            'status' => 'new',
        ]);

        $estimate = Estimate::create([
            'service_request_id' => $serviceRequest->id,
            'status' => 'accepted',
            'subtotal' => 1000,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 1000,
        ]);

        $workOrder = WorkOrder::create([
            'service_request_id' => $serviceRequest->id,
            'estimate_id' => $estimate->id,
            'work_order_number' => WorkOrder::generateWorkOrderNumber(),
            'status' => WorkOrder::STATUS_PENDING,
            'priority' => 'normal',
            'total' => 1100,
            'tax_rate' => 0,
            'subtotal' => 1100,
            'tax_amount' => 0,
        ]);

        ChangeOrder::create([
            'work_order_id' => $workOrder->id,
            'change_type' => 'modify_item',
            'description' => 'Additional labor required',
            'price_impact' => 150,
            'requires_customer_approval' => true,
            'approval_status' => ChangeOrder::APPROVAL_PENDING,
        ]);

        $invoice = Invoice::create([
            'service_request_id' => $serviceRequest->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'status' => Invoice::STATUS_DRAFT,
            'customer_name' => 'Test',
            'line_items' => [['name' => 'Labor', 'quantity' => 1, 'unit_price' => 1100]],
            'subtotal' => 1100,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 1100,
            'issued_by' => $user->id,
            'company_snapshot' => ['name' => 'Test Co'],
        ]);

        $this->actingAs($user)
            ->patch(route('invoices.update-status', [$serviceRequest, $invoice]), [
                'status' => Invoice::STATUS_SENT,
            ])->assertRedirect(route('invoices.show', [$serviceRequest, $invoice]));

        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_SENT, $invoice->status);
    }

    public function test_public_change_order_approval_flow_records_decision(): void
    {
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'phone' => '15553334444',
            'is_active' => true,
        ]);

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $customer->id,
            'status' => 'new',
        ]);

        $workOrder = WorkOrder::create([
            'service_request_id' => $serviceRequest->id,
            'work_order_number' => WorkOrder::generateWorkOrderNumber(),
            'status' => WorkOrder::STATUS_PENDING,
            'priority' => 'normal',
            'total' => 100,
            'tax_rate' => 0,
            'subtotal' => 100,
            'tax_amount' => 0,
        ]);

        $changeOrder = ChangeOrder::create([
            'work_order_id' => $workOrder->id,
            'change_type' => 'informational',
            'description' => 'Test change',
            'price_impact' => 0,
            'requires_customer_approval' => true,
            'approval_status' => ChangeOrder::APPROVAL_PENDING,
            'approval_token' => 'tokentest123',
            'approval_token_expires_at' => now()->addDay(),
        ]);

        $this->get(route('change-orders.show', $changeOrder->approval_token))
            ->assertOk()
            ->assertSeeText('Change Authorization');

        $this->post(route('change-orders.approve', $changeOrder->approval_token), [
            'decision' => 'approved',
            'approved_by_name' => 'Jane Customer',
        ])->assertOk();

        $changeOrder->refresh();
        $this->assertSame(ChangeOrder::APPROVAL_APPROVED, $changeOrder->approval_status);
        $this->assertSame('Jane Customer', $changeOrder->approved_by_name);
    }

    public function test_authenticated_user_can_create_change_order_from_work_order(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'first_name' => 'Mia',
            'last_name' => 'Driver',
            'phone' => '15556667777',
            'is_active' => true,
        ]);

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $customer->id,
            'status' => 'new',
        ]);

        $workOrder = WorkOrder::create([
            'service_request_id' => $serviceRequest->id,
            'work_order_number' => WorkOrder::generateWorkOrderNumber(),
            'status' => WorkOrder::STATUS_PENDING,
            'priority' => 'normal',
            'total' => 100,
            'tax_rate' => 0,
            'subtotal' => 100,
            'tax_amount' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('change-orders.store', [$serviceRequest, $workOrder]), [
                'change_type' => 'informational',
                'description' => 'Customer requested alternate pickup spot.',
                'price_impact' => 0,
                'send_sms' => 0,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('change_orders', [
            'work_order_id' => $workOrder->id,
            'change_type' => 'informational',
            'approval_status' => ChangeOrder::APPROVAL_NOT_REQUIRED,
        ]);
    }

    public function test_invoice_create_page_redirects_when_pending_change_order_exists(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'first_name' => 'Pat',
            'last_name' => 'Pending',
            'phone' => '15558889999',
            'is_active' => true,
        ]);

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $customer->id,
            'status' => 'new',
        ]);

        $workOrder = WorkOrder::create([
            'service_request_id' => $serviceRequest->id,
            'work_order_number' => WorkOrder::generateWorkOrderNumber(),
            'status' => WorkOrder::STATUS_PENDING,
            'priority' => 'normal',
            'total' => 100,
            'tax_rate' => 0,
            'subtotal' => 100,
            'tax_amount' => 0,
        ]);

        ChangeOrder::create([
            'work_order_id' => $workOrder->id,
            'change_type' => 'modify_item',
            'description' => 'Pending approval adjustment',
            'price_impact' => 10,
            'requires_customer_approval' => true,
            'approval_status' => ChangeOrder::APPROVAL_PENDING,
        ]);

        $this->actingAs($user)
            ->get(route('invoices.create', [$serviceRequest, $workOrder]))
            ->assertRedirect(route('work-orders.show', [$serviceRequest, $workOrder]));
    }

    public function test_invoice_store_is_blocked_when_pending_change_order_exists(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'first_name' => 'Sam',
            'last_name' => 'StoreBlock',
            'phone' => '15557778888',
            'is_active' => true,
        ]);

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $customer->id,
            'status' => 'new',
        ]);

        $workOrder = WorkOrder::create([
            'service_request_id' => $serviceRequest->id,
            'work_order_number' => WorkOrder::generateWorkOrderNumber(),
            'status' => WorkOrder::STATUS_PENDING,
            'priority' => 'normal',
            'total' => 100,
            'tax_rate' => 0,
            'subtotal' => 100,
            'tax_amount' => 0,
        ]);

        ChangeOrder::create([
            'work_order_id' => $workOrder->id,
            'change_type' => 'modify_item',
            'description' => 'Pending approval adjustment',
            'price_impact' => 10,
            'requires_customer_approval' => true,
            'approval_status' => ChangeOrder::APPROVAL_PENDING,
        ]);

        $this->actingAs($user)
            ->post(route('invoices.store', [$serviceRequest, $workOrder]), [
                'customer_name' => 'Sam StoreBlock',
                'line_items' => [['name' => 'Labor', 'quantity' => 1, 'unit' => 'ea', 'unit_price' => 100]],
                'subtotal' => 100,
                'tax_amount' => 0,
                'total' => 100,
            ])
            ->assertSessionHasErrors('invoice');

        $this->assertSame(0, Invoice::count());
    }

    public function test_approved_change_order_updates_work_order_total(): void
    {
        $customer = Customer::create([
            'first_name' => 'Ari',
            'last_name' => 'Approve',
            'phone' => '15554443333',
            'is_active' => true,
        ]);

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $customer->id,
            'status' => 'new',
        ]);

        $workOrder = WorkOrder::create([
            'service_request_id' => $serviceRequest->id,
            'work_order_number' => WorkOrder::generateWorkOrderNumber(),
            'status' => WorkOrder::STATUS_PENDING,
            'priority' => 'normal',
            'total' => 100,
            'tax_rate' => 0,
            'subtotal' => 100,
            'tax_amount' => 0,
        ]);

        $changeOrder = ChangeOrder::create([
            'work_order_id' => $workOrder->id,
            'change_type' => 'modify_item',
            'description' => 'Add after-hours surcharge',
            'price_impact' => 25,
            'requires_customer_approval' => true,
            'approval_status' => ChangeOrder::APPROVAL_PENDING,
            'approval_token' => 'token-apply-1',
            'approval_token_expires_at' => now()->addDay(),
        ]);

        $this->post(route('change-orders.approve', $changeOrder->approval_token), [
            'decision' => 'approved',
            'approved_by_name' => 'Ari Approve',
        ])->assertOk();

        $workOrder->refresh();
        $this->assertSame('125.00', (string) $workOrder->total);
    }

    public function test_cancelling_pending_change_order_unblocks_invoice_create(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'first_name' => 'Cal',
            'last_name' => 'Cancel',
            'phone' => '15550001111',
            'is_active' => true,
        ]);

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $customer->id,
            'status' => 'new',
        ]);

        $workOrder = WorkOrder::create([
            'service_request_id' => $serviceRequest->id,
            'work_order_number' => WorkOrder::generateWorkOrderNumber(),
            'status' => WorkOrder::STATUS_PENDING,
            'priority' => 'normal',
            'total' => 100,
            'tax_rate' => 0,
            'subtotal' => 100,
            'tax_amount' => 0,
        ]);

        $changeOrder = ChangeOrder::create([
            'work_order_id' => $workOrder->id,
            'change_type' => 'modify_item',
            'description' => 'Pending approval adjustment',
            'price_impact' => 10,
            'requires_customer_approval' => true,
            'approval_status' => ChangeOrder::APPROVAL_PENDING,
        ]);

        $this->actingAs($user)
            ->post(route('change-orders.cancel', [$serviceRequest, $workOrder, $changeOrder]))
            ->assertSessionHas('success');

        $changeOrder->refresh();
        $this->assertSame(ChangeOrder::APPROVAL_CANCELLED, $changeOrder->approval_status);

        $this->actingAs($user)
            ->get(route('invoices.create', [$serviceRequest, $workOrder]))
            ->assertOk();
    }
}
