<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // ── General (formal bookkeeping) ────────────────────
        $general = [
            // Assets (1xxx)
            ['code' => '1000', 'name' => 'Checking Account',           'type' => Account::TYPE_ASSET],
            ['code' => '1010', 'name' => 'Savings Account',            'type' => Account::TYPE_ASSET],
            ['code' => '1020', 'name' => 'Petty Cash',                 'type' => Account::TYPE_ASSET],
            ['code' => '1100', 'name' => 'Accounts Receivable',        'type' => Account::TYPE_ASSET],
            ['code' => '1200', 'name' => 'Parts & Supplies Inventory', 'type' => Account::TYPE_ASSET],
            ['code' => '1500', 'name' => 'Vehicles & Equipment',       'type' => Account::TYPE_ASSET],
            ['code' => '1510', 'name' => 'Accumulated Depreciation',   'type' => Account::TYPE_ASSET],
            ['code' => '1600', 'name' => 'Security Deposits',          'type' => Account::TYPE_ASSET],

            // Liabilities (2xxx)
            ['code' => '2000', 'name' => 'Accounts Payable',           'type' => Account::TYPE_LIABILITY],
            ['code' => '2010', 'name' => 'Credit Card Payable',        'type' => Account::TYPE_LIABILITY],
            ['code' => '2100', 'name' => 'Payroll Taxes Payable',      'type' => Account::TYPE_LIABILITY],
            ['code' => '2110', 'name' => 'Sales Tax Payable',          'type' => Account::TYPE_LIABILITY],
            ['code' => '2200', 'name' => 'Vehicle Loan Payable',       'type' => Account::TYPE_LIABILITY],
            ['code' => '2210', 'name' => 'Equipment Loan Payable',     'type' => Account::TYPE_LIABILITY],
            ['code' => '2300', 'name' => 'Deferred Revenue',           'type' => Account::TYPE_LIABILITY],

            // Equity (3xxx)
            ['code' => '3000', 'name' => "Owner's Capital",           'type' => Account::TYPE_EQUITY],
            ['code' => '3100', 'name' => "Owner's Draw",              'type' => Account::TYPE_EQUITY],
            ['code' => '3900', 'name' => 'Retained Earnings',          'type' => Account::TYPE_EQUITY],

            // Revenue (4xxx)
            ['code' => '4000', 'name' => 'Service Revenue',            'type' => Account::TYPE_REVENUE],
            ['code' => '4010', 'name' => 'Mobile Mechanic Revenue',    'type' => Account::TYPE_REVENUE],
            ['code' => '4020', 'name' => 'Parts & Supplies Revenue',   'type' => Account::TYPE_REVENUE],
            ['code' => '4030', 'name' => 'Membership / Subscription Fees', 'type' => Account::TYPE_REVENUE],
            ['code' => '4040', 'name' => 'Fleet Account Revenue',      'type' => Account::TYPE_REVENUE],
            ['code' => '4900', 'name' => 'Other Income',               'type' => Account::TYPE_REVENUE],

            // Cost of Services (5xxx)
            ['code' => '5000', 'name' => 'Parts & Supplies Used',      'type' => Account::TYPE_COGS],
            ['code' => '5010', 'name' => 'Subcontractor Labor',        'type' => Account::TYPE_COGS],
            ['code' => '5020', 'name' => 'Fuel (Job-Related)',         'type' => Account::TYPE_COGS],
            ['code' => '5030', 'name' => 'Towing Equipment Rental',    'type' => Account::TYPE_COGS],

            // Expenses (6xxx)
            ['code' => '6000', 'name' => 'Payroll – Technicians',      'type' => Account::TYPE_EXPENSE],
            ['code' => '6010', 'name' => 'Payroll – Admin / Dispatch', 'type' => Account::TYPE_EXPENSE],
            ['code' => '6020', 'name' => 'Payroll Taxes – Employer',   'type' => Account::TYPE_EXPENSE],
            ['code' => '6030', 'name' => 'Employee Benefits',          'type' => Account::TYPE_EXPENSE],
            ['code' => '6100', 'name' => 'Vehicle Fuel',               'type' => Account::TYPE_EXPENSE],
            ['code' => '6110', 'name' => 'Vehicle Maintenance & Repairs', 'type' => Account::TYPE_EXPENSE],
            ['code' => '6120', 'name' => 'Vehicle Insurance',          'type' => Account::TYPE_EXPENSE],
            ['code' => '6130', 'name' => 'Vehicle Registration & Licenses', 'type' => Account::TYPE_EXPENSE],
            ['code' => '6200', 'name' => 'Commercial Insurance',       'type' => Account::TYPE_EXPENSE],
            ['code' => '6210', 'name' => "Workers' Compensation Insurance", 'type' => Account::TYPE_EXPENSE],
            ['code' => '6300', 'name' => 'Rent / Facility Costs',      'type' => Account::TYPE_EXPENSE],
            ['code' => '6310', 'name' => 'Utilities',                  'type' => Account::TYPE_EXPENSE],
            ['code' => '6400', 'name' => 'Software & Subscriptions',   'type' => Account::TYPE_EXPENSE],
            ['code' => '6410', 'name' => 'Phone & Communications',     'type' => Account::TYPE_EXPENSE],
            ['code' => '6420', 'name' => 'SMS / Messaging Services',   'type' => Account::TYPE_EXPENSE],
            ['code' => '6500', 'name' => 'Advertising & Marketing',    'type' => Account::TYPE_EXPENSE],
            ['code' => '6510', 'name' => 'Website & Online Presence',  'type' => Account::TYPE_EXPENSE],
            ['code' => '6600', 'name' => 'Professional Services',      'type' => Account::TYPE_EXPENSE],
            ['code' => '6700', 'name' => 'Bank & Processing Fees',     'type' => Account::TYPE_EXPENSE],
            ['code' => '6710', 'name' => 'Loan Interest',              'type' => Account::TYPE_EXPENSE],
            ['code' => '6800', 'name' => 'Depreciation Expense',       'type' => Account::TYPE_EXPENSE],
            ['code' => '6900', 'name' => 'Uniforms & Safety Gear',     'type' => Account::TYPE_EXPENSE],
            ['code' => '6910', 'name' => 'Tools & Small Equipment',    'type' => Account::TYPE_EXPENSE],
            ['code' => '6990', 'name' => 'Miscellaneous Expense',      'type' => Account::TYPE_EXPENSE],
        ];
        $this->upsertScopeAccounts($general, Account::SCOPE_GENERAL);

        // ── Import (AI categorisation for scanned records) ──
        $import = $general;
        $this->upsertScopeAccounts($import, Account::SCOPE_IMPORT);
    }

    private function upsertScopeAccounts(array $accounts, string $scope): void
    {
        foreach ($accounts as $data) {
            Account::updateOrCreate(
                ['code' => $data['code'], 'scope' => $scope],
                array_merge($data, ['scope' => $scope, 'is_active' => true])
            );
        }
    }
}
