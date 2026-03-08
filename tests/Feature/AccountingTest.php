<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\PaymentRecord;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\FinancialPostingService;
use App\Services\FinancialReportingService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create();
    }

    private function seedAccounts(): void
    {
        $this->seed(ChartOfAccountsSeeder::class);
    }

    private function account(string $code): Account
    {
        return Account::where('code', $code)->firstOrFail();
    }

    /**
     * Create a service request (required FK for invoices/payments).
     */
    private function createServiceRequest(): ServiceRequest
    {
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name'  => 'Customer',
            'phone'      => '5551234567',
        ]);

        return ServiceRequest::create([
            'customer_id' => $customer->id,
            'status'      => 'new',
        ]);
    }

    private function createInvoice(array $overrides = []): Invoice
    {
        $sr = $this->createServiceRequest();

        return Invoice::create(array_merge([
            'service_request_id' => $sr->id,
            'invoice_number'     => Invoice::generateInvoiceNumber(),
            'status'             => Invoice::STATUS_SENT,
            'customer_name'      => 'Test Customer',
            'subtotal'           => 1000.00,
            'tax_rate'           => 0,
            'tax_amount'         => 0,
            'total'              => 1000.00,
            'line_items'         => [],
            'company_snapshot'   => [],
        ], $overrides));
    }

    private function createPayment(array $overrides = []): PaymentRecord
    {
        $sr = $this->createServiceRequest();

        return PaymentRecord::create(array_merge([
            'service_request_id' => $sr->id,
            'method'             => 'cash',
            'amount'             => 500.00,
            'collected_at'       => now(),
        ], $overrides));
    }

    /**
     * Create a balanced posted journal entry.
     */
    private function createJournalEntry(array $lines, array $entryOverrides = []): JournalEntry
    {
        $entry = JournalEntry::create(array_merge([
            'entry_number' => JournalEntry::generateEntryNumber(),
            'entry_date'   => now()->toDateString(),
            'memo'         => 'Test entry',
            'status'       => JournalEntry::STATUS_POSTED,
            'posted_at'    => now(),
        ], $entryOverrides));

        foreach ($lines as $line) {
            $entry->lines()->create($line);
        }

        return $entry;
    }

    // ══════════════════════════════════════════════════════════
    //  Seeder
    // ══════════════════════════════════════════════════════════

    public function test_seeder_creates_default_accounts(): void
    {
        $this->seedAccounts();

        $this->assertDatabaseHas('accounts', ['code' => '1000', 'name' => 'Checking Account']);
        $this->assertDatabaseHas('accounts', ['code' => '1100', 'name' => 'Accounts Receivable']);
        $this->assertDatabaseHas('accounts', ['code' => '4000', 'name' => 'Service Revenue']);
        $this->assertDatabaseHas('accounts', ['code' => '5000', 'name' => 'Parts & Supplies Used']);

        $this->assertGreaterThanOrEqual(17, Account::count());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seedAccounts();
        $count = Account::count();

        $this->seedAccounts();
        $this->assertEquals($count, Account::count());
    }

    // ══════════════════════════════════════════════════════════
    //  Account model
    // ══════════════════════════════════════════════════════════

    public function test_account_debit_normal_types(): void
    {
        $asset   = Account::firstOrCreate(['code' => '1000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'T', 'type' => 'asset']);
        $expense = Account::firstOrCreate(['code' => '6000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'T', 'type' => 'expense']);
        $cogs    = Account::firstOrCreate(['code' => '5000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'T', 'type' => 'cogs']);

        $this->assertTrue($asset->isDebitNormal());
        $this->assertTrue($expense->isDebitNormal());
        $this->assertTrue($cogs->isDebitNormal());
    }

    public function test_account_credit_normal_types(): void
    {
        $liability = Account::firstOrCreate(['code' => '2000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'T', 'type' => 'liability']);
        $equity    = Account::firstOrCreate(['code' => '3000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'T', 'type' => 'equity']);
        $revenue   = Account::firstOrCreate(['code' => '4000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'T', 'type' => 'revenue']);

        $this->assertFalse($liability->isDebitNormal());
        $this->assertFalse($equity->isDebitNormal());
        $this->assertFalse($revenue->isDebitNormal());
    }

    public function test_account_balance_debit_normal(): void
    {
        $cash = Account::firstOrCreate(['code' => '1000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'Checking Account', 'type' => 'asset']);
        $revenue = Account::firstOrCreate(['code' => '4000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'Rev', 'type' => 'revenue']);

        $this->createJournalEntry([
            ['account_id' => $cash->id, 'debit' => 500, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 500],
        ]);

        $this->assertEquals(500.0, $cash->balance());
    }

    public function test_account_balance_credit_normal(): void
    {
        $cash = Account::firstOrCreate(['code' => '1000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'Checking Account', 'type' => 'asset']);
        $revenue = Account::firstOrCreate(['code' => '4000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'Rev', 'type' => 'revenue']);

        $this->createJournalEntry([
            ['account_id' => $cash->id, 'debit' => 1000, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 1000],
        ]);

        $this->assertEquals(1000.0, $revenue->balance());
    }

    public function test_account_balance_ignores_void_entries(): void
    {
        $cash = Account::firstOrCreate(['code' => '1000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'Checking Account', 'type' => 'asset']);
        $revenue = Account::firstOrCreate(['code' => '4000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'Rev', 'type' => 'revenue']);

        // Posted entry
        $this->createJournalEntry([
            ['account_id' => $cash->id, 'debit' => 500, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 500],
        ]);

        // Void entry
        $this->createJournalEntry([
            ['account_id' => $cash->id, 'debit' => 300, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 300],
        ], ['status' => JournalEntry::STATUS_VOID]);

        $this->assertEquals(500.0, $cash->balance());
    }

    public function test_account_balance_with_as_of_date(): void
    {
        $cash = Account::firstOrCreate(['code' => '1000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'Checking Account', 'type' => 'asset']);
        $revenue = Account::firstOrCreate(['code' => '4000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'Rev', 'type' => 'revenue']);

        $this->createJournalEntry([
            ['account_id' => $cash->id, 'debit' => 500, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 500],
        ], ['entry_date' => '2026-01-15']);

        $this->createJournalEntry([
            ['account_id' => $cash->id, 'debit' => 200, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 200],
        ], ['entry_date' => '2026-02-15']);

        $asOf = new \DateTimeImmutable('2026-01-31');
        $this->assertEquals(500.0, $cash->balance($asOf));
    }

    // ══════════════════════════════════════════════════════════
    //  JournalEntry model
    // ══════════════════════════════════════════════════════════

    public function test_journal_entry_number_generation(): void
    {
        $num = JournalEntry::generateEntryNumber();
        $this->assertMatchesRegularExpression('/^JE-\d{8}-\d{4}$/', $num);
    }

    public function test_journal_entry_number_increments(): void
    {
        JournalEntry::create([
            'entry_number' => 'JE-' . now()->format('Ymd') . '-0001',
            'entry_date'   => now()->toDateString(),
            'status'       => JournalEntry::STATUS_POSTED,
        ]);

        $next = JournalEntry::generateEntryNumber();
        $this->assertStringEndsWith('-0002', $next);
    }

    public function test_journal_entry_is_balanced(): void
    {
        $account1 = Account::firstOrCreate(['code' => '1000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'Checking Account', 'type' => 'asset']);
        $account2 = Account::firstOrCreate(['code' => '4000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'Rev', 'type' => 'revenue']);

        $entry = $this->createJournalEntry([
            ['account_id' => $account1->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $account2->id, 'debit' => 0, 'credit' => 100],
        ]);

        $this->assertTrue($entry->isBalanced());
    }

    public function test_journal_entry_is_not_balanced(): void
    {
        $account1 = Account::firstOrCreate(['code' => '1000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'Checking Account', 'type' => 'asset']);
        $account2 = Account::firstOrCreate(['code' => '4000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'Rev', 'type' => 'revenue']);

        $entry = JournalEntry::create([
            'entry_number' => JournalEntry::generateEntryNumber(),
            'entry_date'   => now()->toDateString(),
            'status'       => JournalEntry::STATUS_POSTED,
        ]);

        $entry->lines()->create(['account_id' => $account1->id, 'debit' => 100, 'credit' => 0]);
        $entry->lines()->create(['account_id' => $account2->id, 'debit' => 0, 'credit' => 50]);

        $this->assertFalse($entry->isBalanced());
    }

    public function test_journal_entry_void_excludes_from_balances(): void
    {
        $cash    = Account::firstOrCreate(['code' => '1000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'Checking Account', 'type' => 'asset']);
        $revenue = Account::firstOrCreate(['code' => '4000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'Rev', 'type' => 'revenue']);

        $entry = $this->createJournalEntry([
            ['account_id' => $cash->id, 'debit' => 250, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 250],
        ]);

        $this->assertEquals(250.0, $cash->balance());

        $entry->void('Test void');

        // Voided entries are excluded from balance calculations
        $this->assertEquals(JournalEntry::STATUS_VOID, $entry->fresh()->status);
        $this->assertEquals(0.0, $cash->balance());
        $this->assertEquals(0.0, $revenue->balance());
    }

    // ══════════════════════════════════════════════════════════
    //  FinancialPostingService — Invoice
    // ══════════════════════════════════════════════════════════

    public function test_post_invoice_sent(): void
    {
        $this->seedAccounts();
        $user = $this->createUser();

        $invoice = $this->createInvoice([
            'subtotal'   => 1000.00,
            'tax_rate'   => 0.08,
            'tax_amount' => 80.00,
            'total'      => 1080.00,
        ]);

        $service = new FinancialPostingService();
        $entry = $service->postInvoiceSent($invoice, $user->id);

        $this->assertNotNull($entry);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $entry->status);
        $this->assertTrue($entry->isBalanced());

        // A/R debited for total
        $arLine = $entry->lines()->where('account_id', $this->account('1100')->id)->first();
        $this->assertEquals(1080.00, (float) $arLine->debit);

        // Revenue credited for subtotal
        $revLine = $entry->lines()->where('account_id', $this->account('4000')->id)->first();
        $this->assertEquals(1000.00, (float) $revLine->credit);

        // Sales tax credited
        $taxLine = $entry->lines()->where('account_id', $this->account('2110')->id)->first();
        $this->assertEquals(80.00, (float) $taxLine->credit);
    }

    public function test_post_invoice_sent_no_tax(): void
    {
        $this->seedAccounts();

        $invoice = $this->createInvoice([
            'subtotal'   => 500.00,
            'tax_amount' => 0,
            'total'      => 500.00,
        ]);

        $service = new FinancialPostingService();
        $entry = $service->postInvoiceSent($invoice);

        $this->assertNotNull($entry);
        $this->assertTrue($entry->isBalanced());
        $this->assertEquals(2, $entry->lines()->count()); // No tax line
    }

    public function test_post_invoice_prevents_double_posting(): void
    {
        $this->seedAccounts();

        $invoice = $this->createInvoice(['total' => 100, 'subtotal' => 100]);

        $service = new FinancialPostingService();
        $first = $service->postInvoiceSent($invoice);
        $second = $service->postInvoiceSent($invoice);

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertEquals(1, JournalEntry::where('source_type', Invoice::class)->where('source_id', $invoice->id)->count());
    }

    public function test_reverse_invoice(): void
    {
        $this->seedAccounts();
        $user = $this->createUser();

        $invoice = $this->createInvoice(['subtotal' => 200, 'total' => 200]);

        $service = new FinancialPostingService();
        $service->postInvoiceSent($invoice, $user->id);

        $result = $service->reverseInvoice($invoice, $user->id);

        $this->assertTrue($result);

        // Original entry is now void — balances should be zero
        $this->assertEquals(0.0, $this->account('1100')->balance());
        $this->assertEquals(0.0, $this->account('4000')->balance());
    }

    public function test_reverse_invoice_without_posting_returns_false(): void
    {
        $this->seedAccounts();

        $invoice = $this->createInvoice(['status' => Invoice::STATUS_DRAFT]);

        $service = new FinancialPostingService();
        $this->assertFalse($service->reverseInvoice($invoice));
    }

    // ══════════════════════════════════════════════════════════
    //  FinancialPostingService — Payment
    // ══════════════════════════════════════════════════════════

    public function test_post_payment_received(): void
    {
        $this->seedAccounts();
        $user = $this->createUser();

        $payment = $this->createPayment([
            'amount'       => 500.00,
            'reference'    => 'PAY-001',
            'collected_by' => $user->id,
        ]);

        $service = new FinancialPostingService();
        $entry = $service->postPaymentReceived($payment, $user->id);

        $this->assertNotNull($entry);
        $this->assertTrue($entry->isBalanced());

        // Cash debited
        $cashLine = $entry->lines()->where('account_id', $this->account('1000')->id)->first();
        $this->assertEquals(500.00, (float) $cashLine->debit);

        // A/R credited
        $arLine = $entry->lines()->where('account_id', $this->account('1100')->id)->first();
        $this->assertEquals(500.00, (float) $arLine->credit);
    }

    public function test_post_payment_prevents_double_posting(): void
    {
        $this->seedAccounts();

        $payment = $this->createPayment(['amount' => 100]);

        $service = new FinancialPostingService();
        $first = $service->postPaymentReceived($payment);
        $second = $service->postPaymentReceived($payment);

        $this->assertNotNull($first);
        $this->assertNull($second);
    }

    // ══════════════════════════════════════════════════════════
    //  FinancialPostingService — Expense
    // ══════════════════════════════════════════════════════════

    public function test_post_expense(): void
    {
        $this->seedAccounts();
        $user = $this->createUser();

        $expense = Expense::create([
            'expense_number' => 'EXP-TEST-0001',
            'date'           => now()->toDateString(),
            'vendor'         => 'AutoZone',
            'description'    => 'Brake pads',
            'category'       => 'parts',
            'amount'         => 49.99,
            'created_by'     => $user->id,
        ]);

        $service = new FinancialPostingService();
        $entry = $service->postExpense($expense, $user->id);

        $this->assertNotNull($entry);
        $this->assertTrue($entry->isBalanced());

        // Parts → COGS account 5300
        $expLine = $entry->lines()->where('account_id', $this->account('5000')->id)->first();
        $this->assertEquals(49.99, (float) $expLine->debit);

        // Cash credited
        $cashLine = $entry->lines()->where('account_id', $this->account('1000')->id)->first();
        $this->assertEquals(49.99, (float) $cashLine->credit);
    }

    public function test_post_expense_maps_fuel_to_vehicle_expenses(): void
    {
        $this->seedAccounts();

        $expense = Expense::create([
            'expense_number' => 'EXP-TEST-0002',
            'date'           => now()->toDateString(),
            'vendor'         => 'Shell',
            'description'    => 'Gas',
            'category'       => 'fuel',
            'amount'         => 75.00,
        ]);

        $service = new FinancialPostingService();
        $entry = $service->postExpense($expense);

        $this->assertNotNull($entry);
        // fuel → 6100 Vehicle Fuel
        $this->assertNotNull(
            $entry->lines()->where('account_id', $this->account('6100')->id)->first()
        );
    }

    public function test_post_expense_prevents_double_posting(): void
    {
        $this->seedAccounts();

        $expense = Expense::create([
            'expense_number' => 'EXP-TEST-0003',
            'date'           => now()->toDateString(),
            'vendor'         => 'Test',
            'category'       => 'other',
            'amount'         => 10,
        ]);

        $service = new FinancialPostingService();
        $first = $service->postExpense($expense);
        $second = $service->postExpense($expense);

        $this->assertNotNull($first);
        $this->assertNull($second);
    }

    // ══════════════════════════════════════════════════════════
    //  FinancialReportingService — Trial Balance
    // ══════════════════════════════════════════════════════════

    public function test_trial_balance_totals_balance(): void
    {
        $this->seedAccounts();
        $service = new FinancialPostingService();

        // Post an invoice
        $invoice = $this->createInvoice(['subtotal' => 1000, 'tax_amount' => 80, 'total' => 1080]);
        $service->postInvoiceSent($invoice);

        // Post a payment
        $payment = $this->createPayment(['amount' => 500]);
        $service->postPaymentReceived($payment);

        $reporting = new FinancialReportingService();
        $tb = $reporting->trialBalance();

        $this->assertEquals(
            round($tb['total_debits'], 2),
            round($tb['total_credits'], 2),
            'Trial balance debits must equal credits'
        );
    }

    public function test_trial_balance_empty_system(): void
    {
        $this->seedAccounts();

        $reporting = new FinancialReportingService();
        $tb = $reporting->trialBalance();

        $this->assertCount(0, $tb['accounts']);
        $this->assertEquals(0.0, $tb['total_debits']);
        $this->assertEquals(0.0, $tb['total_credits']);
    }

    // ══════════════════════════════════════════════════════════
    //  FinancialReportingService — Profit & Loss
    // ══════════════════════════════════════════════════════════

    public function test_profit_and_loss_calculation(): void
    {
        $this->seedAccounts();
        $service = new FinancialPostingService();

        // Revenue: invoice
        $invoice = $this->createInvoice(['subtotal' => 2000, 'tax_amount' => 0, 'total' => 2000]);
        $service->postInvoiceSent($invoice);

        // Expense
        $expense = Expense::create([
            'expense_number' => 'EXP-PL-001',
            'date'           => now()->toDateString(),
            'vendor'         => 'Shell',
            'category'       => 'fuel',
            'amount'         => 300,
        ]);
        $service->postExpense($expense);

        // COGS
        $cogsExpense = Expense::create([
            'expense_number' => 'EXP-PL-002',
            'date'           => now()->toDateString(),
            'vendor'         => 'AutoZone',
            'category'       => 'parts',
            'amount'         => 200,
        ]);
        $service->postExpense($cogsExpense);

        $reporting = new FinancialReportingService();
        $pl = $reporting->profitAndLoss(now()->startOfMonth(), now()->endOfDay());

        $this->assertEquals(2000, $pl['total_revenue']);
        $this->assertEquals(200, $pl['total_cogs']);
        $this->assertEquals(300, $pl['total_expenses']);
        $this->assertEquals(1800, $pl['gross_profit']);   // 2000 - 200
        $this->assertEquals(1500, $pl['net_income']);     // 2000 - 200 - 300
    }

    // ══════════════════════════════════════════════════════════
    //  FinancialReportingService — Balance Sheet
    // ══════════════════════════════════════════════════════════

    public function test_balance_sheet_equation(): void
    {
        $this->seedAccounts();
        $service = new FinancialPostingService();

        $invoice = $this->createInvoice(['subtotal' => 1500, 'tax_amount' => 0, 'total' => 1500]);
        $service->postInvoiceSent($invoice);

        $payment = $this->createPayment(['amount' => 800]);
        $service->postPaymentReceived($payment);

        $reporting = new FinancialReportingService();
        $bs = $reporting->balanceSheet(now());

        // Assets = Liabilities + Equity + Net Income
        $this->assertEquals(
            round($bs['total_assets'], 2),
            round($bs['equity_plus_income'], 2),
            'Balance sheet must balance: Assets = Liabilities + Equity + Net Income'
        );
    }

    public function test_balance_sheet_empty_system(): void
    {
        $this->seedAccounts();

        $reporting = new FinancialReportingService();
        $bs = $reporting->balanceSheet(now());

        $this->assertEquals(0.0, $bs['total_assets']);
        $this->assertEquals(0.0, $bs['total_liabilities']);
        $this->assertEquals(0.0, $bs['total_equity']);
        $this->assertEquals(0.0, $bs['net_income']);
    }

    // ══════════════════════════════════════════════════════════
    //  FinancialReportingService — General Ledger
    // ══════════════════════════════════════════════════════════

    public function test_general_ledger_running_balance(): void
    {
        $this->seedAccounts();
        $cash = $this->account('1000');

        $service = new FinancialPostingService();

        // Payment 1
        $p1 = $this->createPayment(['amount' => 300]);
        $service->postPaymentReceived($p1);

        // Payment 2
        $p2 = $this->createPayment(['amount' => 200]);
        $service->postPaymentReceived($p2);

        $reporting = new FinancialReportingService();
        $gl = $reporting->generalLedger($cash, now()->startOfMonth(), now());

        $this->assertEquals(0.0, $gl['opening_balance']);
        $this->assertCount(2, $gl['entries']);

        // Running balance after two payments: 300, then 500
        $this->assertEquals(300.0, $gl['entries'][0]['running_balance']);
        $this->assertEquals(500.0, $gl['entries'][1]['running_balance']);
    }

    public function test_general_ledger_opening_balance(): void
    {
        $this->seedAccounts();
        $cash = $this->account('1000');

        $service = new FinancialPostingService();

        // Old payment (last month)
        $old = $this->createPayment(['amount' => 1000]);
        $oldEntry = $service->postPaymentReceived($old);
        // Set the entry date to last month
        $oldEntry->update(['entry_date' => now()->subMonth()->toDateString()]);

        // Current month payment
        $current = $this->createPayment(['amount' => 250]);
        $service->postPaymentReceived($current);

        $reporting = new FinancialReportingService();
        $gl = $reporting->generalLedger($cash, now()->startOfMonth(), now());

        $this->assertEquals(1000.0, $gl['opening_balance']);
        $this->assertCount(1, $gl['entries']);
        $this->assertEquals(1250.0, $gl['entries'][0]['running_balance']);
    }

    // ══════════════════════════════════════════════════════════
    //  Controller routes — authentication
    // ══════════════════════════════════════════════════════════

    public function test_chart_of_accounts_requires_auth(): void
    {
        $this->get(route('accounting.chart-of-accounts'))->assertRedirect(route('login'));
    }

    public function test_journal_requires_auth(): void
    {
        $this->get(route('accounting.journal'))->assertRedirect(route('login'));
    }

    public function test_trial_balance_requires_auth(): void
    {
        $this->get(route('accounting.trial-balance'))->assertRedirect(route('login'));
    }

    public function test_profit_loss_requires_auth(): void
    {
        $this->get(route('accounting.profit-loss'))->assertRedirect(route('login'));
    }

    public function test_balance_sheet_requires_auth(): void
    {
        $this->get(route('accounting.balance-sheet'))->assertRedirect(route('login'));
    }

    public function test_general_ledger_requires_auth(): void
    {
        $account = Account::firstOrCreate(['code' => '1000', 'scope' => Account::SCOPE_GENERAL], ['name' => 'Checking Account', 'type' => 'asset']);
        $this->get(route('accounting.general-ledger', $account))->assertRedirect(route('login'));
    }

    // ══════════════════════════════════════════════════════════
    //  Controller routes — authenticated
    // ══════════════════════════════════════════════════════════

    public function test_chart_of_accounts_page_loads(): void
    {
        $this->seedAccounts();
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('accounting.chart-of-accounts'))
            ->assertOk()
            ->assertSee('Chart of Accounts')
            ->assertSee('1000')
            ->assertSee('Checking Account');
    }

    public function test_journal_page_loads(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('accounting.journal'))
            ->assertOk()
            ->assertSee('Journal Entries');
    }

    public function test_journal_shows_entries(): void
    {
        $this->seedAccounts();
        $user = $this->createUser();

        $invoice = $this->createInvoice(['subtotal' => 100, 'total' => 100]);
        (new FinancialPostingService())->postInvoiceSent($invoice);

        $this->actingAs($user)
            ->get(route('accounting.journal'))
            ->assertOk()
            ->assertSee('JE-');
    }

    public function test_trial_balance_page_loads(): void
    {
        $this->seedAccounts();
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('accounting.trial-balance'))
            ->assertOk()
            ->assertSee('Trial Balance');
    }

    public function test_profit_loss_page_loads(): void
    {
        $this->seedAccounts();
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('accounting.profit-loss'))
            ->assertOk()
            ->assertSee('Profit');
    }

    public function test_balance_sheet_page_loads(): void
    {
        $this->seedAccounts();
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('accounting.balance-sheet'))
            ->assertOk()
            ->assertSee('Balance Sheet');
    }

    public function test_general_ledger_page_loads(): void
    {
        $this->seedAccounts();
        $user = $this->createUser();
        $account = $this->account('1000');

        $this->actingAs($user)
            ->get(route('accounting.general-ledger', $account))
            ->assertOk()
            ->assertSee('General Ledger')
            ->assertSee('1000')
            ->assertSee('Checking Account');
    }

    public function test_chart_of_accounts_empty_state(): void
    {
        $user = $this->createUser();

        // Clear any seeded chart rows to test the true empty state.
        Account::query()->delete();

        $this->actingAs($user)
            ->get(route('accounting.chart-of-accounts'))
            ->assertOk()
            ->assertSee('No accounts found');
    }

    // ══════════════════════════════════════════════════════════
    //  Integration — full cycle
    // ══════════════════════════════════════════════════════════

    public function test_full_invoice_payment_cycle_balances(): void
    {
        $this->seedAccounts();
        $service = new FinancialPostingService();

        // 1. Send invoice ($1000 + $80 tax = $1080)
        $invoice = $this->createInvoice([
            'subtotal'   => 1000,
            'tax_amount' => 80,
            'total'      => 1080,
        ]);
        $service->postInvoiceSent($invoice);

        // 2. Receive full payment
        $payment = $this->createPayment(['amount' => 1080]);
        $service->postPaymentReceived($payment);

        // A/R should be zero (invoiced then paid)
        $this->assertEquals(0.0, $this->account('1100')->balance());

        // Cash should be $1080
        $this->assertEquals(1080.0, $this->account('1000')->balance());

        // Revenue should be $1000
        $this->assertEquals(1000.0, $this->account('4000')->balance());

        // Sales tax payable should be $80
        $this->assertEquals(80.0, $this->account('2110')->balance());

        // Trial balance must balance
        $reporting = new FinancialReportingService();
        $tb = $reporting->trialBalance();
        $this->assertEquals(round($tb['total_debits'], 2), round($tb['total_credits'], 2));

        // Balance sheet must balance
        $bs = $reporting->balanceSheet(now());
        $this->assertEquals(round($bs['total_assets'], 2), round($bs['equity_plus_income'], 2));
    }
}
