<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\PaymentRecord;
use App\Models\Receipt;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAutoReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create();
    }

    private function createServiceRequest(): ServiceRequest
    {
        $customer = Customer::create([
            'first_name' => 'Mike',
            'last_name'  => 'Smith',
            'phone'      => '5551234567',
            'is_active'  => true,
        ]);

        return ServiceRequest::create([
            'customer_id'   => $customer->id,
            'status'        => 'in_progress',
            'vehicle_year'  => '2019',
            'vehicle_make'  => 'Ford',
            'vehicle_model' => 'F-150',
            'vehicle_color' => 'White',
            'location'      => '456 Oak Ave, Tampa FL',
        ]);
    }

    // ── Auto-receipt on every payment ────────────────────────────

    public function test_payment_with_invoice_creates_receipt(): void
    {
        $user = $this->createUser();
        $sr   = $this->createServiceRequest();

        $invoice = Invoice::create([
            'service_request_id' => $sr->id,
            'invoice_number'     => Invoice::generateInvoiceNumber(),
            'status'             => Invoice::STATUS_SENT,
            'customer_name'      => 'Mike Smith',
            'line_items'         => [['name' => 'Service', 'quantity' => 1, 'unit_price' => 150]],
            'subtotal'           => 150,
            'tax_amount'         => 0,
            'total'              => 150,
            'company_snapshot'   => ['name' => 'Test Co'],
        ]);

        $response = $this->actingAs($user)->post(route('payments.store', $sr), [
            'method'     => 'card',
            'amount'     => 150.00,
            'invoice_id' => $invoice->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Payment created
        $this->assertDatabaseCount('payment_records', 1);
        $payment = PaymentRecord::first();
        $this->assertEquals($invoice->id, $payment->invoice_id);

        // Receipt auto-created
        $this->assertDatabaseCount('receipts', 1);
        $receipt = Receipt::first();
        $this->assertEquals($sr->id, $receipt->service_request_id);
        $this->assertEquals($invoice->id, $receipt->invoice_id);
        $this->assertEquals($payment->id, $receipt->payment_record_id);
        $this->assertStringStartsWith('R-', $receipt->receipt_number);
        $this->assertEquals('Mike Smith', $receipt->customer_name);
        $this->assertEquals(150.00, (float) $receipt->total);
        $this->assertEquals('card', $receipt->payment_method);
        $this->assertEquals('Invoice Payment', $receipt->service_description);
    }

    public function test_payment_without_invoice_creates_deposit_receipt(): void
    {
        $user = $this->createUser();
        $sr   = $this->createServiceRequest();

        $response = $this->actingAs($user)->post(route('payments.store', $sr), [
            'method'    => 'cash',
            'amount'    => 200.00,
            'reference' => 'parts-deposit',
            'notes'     => 'Deposit for alternator',
        ]);

        $response->assertRedirect();

        // Receipt auto-created with deposit description
        $receipt = Receipt::first();
        $this->assertNotNull($receipt);
        $this->assertNull($receipt->invoice_id);
        $this->assertEquals('Deposit / Prepayment', $receipt->service_description);
        $this->assertEquals(200.00, (float) $receipt->total);
        $this->assertEquals('cash', $receipt->payment_method);
        $this->assertEquals('parts-deposit', $receipt->payment_reference);
        $this->assertEquals('Deposit for alternator', $receipt->notes);

        // Line item describes it as a deposit
        $this->assertEquals('Customer Deposit', $receipt->line_items[0]['name']);
    }

    public function test_deposit_payment_creates_journal_entry(): void
    {
        $user = $this->createUser();
        $sr   = $this->createServiceRequest();
        $this->seed(ChartOfAccountsSeeder::class);

        $this->actingAs($user)->post(route('payments.store', $sr), [
            'method' => 'zelle',
            'amount' => 350.00,
        ]);

        // Journal entry posted
        $je = JournalEntry::first();
        $this->assertNotNull($je);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $je->status);
        $this->assertStringContainsString('Customer deposit', $je->memo);
        $this->assertEquals(PaymentRecord::class, $je->source_type);

        // Balanced double-entry: debit Checking, credit Deferred Revenue
        $lines = $je->lines()->with('account')->get();
        $this->assertCount(2, $lines);

        $debitLine = $lines->firstWhere('debit', '>', 0);
        $creditLine = $lines->firstWhere('credit', '>', 0);

        $this->assertEquals('1000', $debitLine->account->code);  // Checking
        $this->assertEquals(350.00, (float) $debitLine->debit);

        $this->assertEquals('2300', $creditLine->account->code);  // Deferred Revenue
        $this->assertEquals(350.00, (float) $creditLine->credit);
    }

    public function test_invoice_payment_does_not_create_deposit_journal_entry(): void
    {
        $user = $this->createUser();
        $sr   = $this->createServiceRequest();
        $this->seed(ChartOfAccountsSeeder::class);

        $invoice = Invoice::create([
            'service_request_id' => $sr->id,
            'invoice_number'     => Invoice::generateInvoiceNumber(),
            'status'             => Invoice::STATUS_SENT,
            'customer_name'      => 'Mike Smith',
            'line_items'         => [['name' => 'Service', 'quantity' => 1, 'unit_price' => 100]],
            'subtotal'           => 100,
            'tax_amount'         => 0,
            'total'              => 100,
            'company_snapshot'   => ['name' => 'Test Co'],
        ]);

        $this->actingAs($user)->post(route('payments.store', $sr), [
            'method'     => 'card',
            'amount'     => 100.00,
            'invoice_id' => $invoice->id,
        ]);

        // No journal entry for invoice payments (handled elsewhere in invoice lifecycle)
        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_receipt_linked_to_payment_record(): void
    {
        $user = $this->createUser();
        $sr   = $this->createServiceRequest();

        $this->actingAs($user)->post(route('payments.store', $sr), [
            'method' => 'venmo',
            'amount' => 75.00,
        ]);

        $payment = PaymentRecord::first();
        $receipt = Receipt::first();

        // Bidirectional relationship
        $this->assertEquals($receipt->id, $payment->receipt->id);
        $this->assertEquals($payment->id, $receipt->paymentRecord->id);
    }

    public function test_receipt_includes_vehicle_description(): void
    {
        $user = $this->createUser();
        $sr   = $this->createServiceRequest();

        $this->actingAs($user)->post(route('payments.store', $sr), [
            'method' => 'cash',
            'amount' => 50.00,
        ]);

        $receipt = Receipt::first();
        $this->assertEquals('White 2019 Ford F-150', $receipt->vehicle_description);
    }

    public function test_success_message_includes_receipt_number(): void
    {
        $user = $this->createUser();
        $sr   = $this->createServiceRequest();

        $response = $this->actingAs($user)->post(route('payments.store', $sr), [
            'method' => 'cash',
            'amount' => 25.00,
        ]);

        $receipt = Receipt::first();
        $response->assertSessionHas('success');
        $this->assertStringContainsString($receipt->receipt_number, session('success'));
    }
}
