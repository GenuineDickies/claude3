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
            ['code' => '1100', 'name' => 'Cash',                    'type' => Account::TYPE_ASSET],
            ['code' => '1110', 'name' => 'Business Checking',       'type' => Account::TYPE_ASSET],
            ['code' => '1120', 'name' => 'Business Savings',        'type' => Account::TYPE_ASSET],
            ['code' => '1200', 'name' => 'Accounts Receivable',     'type' => Account::TYPE_ASSET],
            ['code' => '1300', 'name' => 'Prepaid Expenses',        'type' => Account::TYPE_ASSET],

            // Liabilities (2xxx)
            ['code' => '2100', 'name' => 'Accounts Payable',        'type' => Account::TYPE_LIABILITY],
            ['code' => '2200', 'name' => 'Sales Tax Payable',       'type' => Account::TYPE_LIABILITY],
            ['code' => '2300', 'name' => 'Customer Deposits',       'type' => Account::TYPE_LIABILITY],
            ['code' => '2400', 'name' => 'Credit Card Payable',     'type' => Account::TYPE_LIABILITY],

            // Equity (3xxx)
            ['code' => '3000', 'name' => 'Owner\'s Equity',         'type' => Account::TYPE_EQUITY],
            ['code' => '3100', 'name' => 'Owner\'s Draw',           'type' => Account::TYPE_EQUITY],
            ['code' => '3900', 'name' => 'Retained Earnings',       'type' => Account::TYPE_EQUITY],

            // Revenue (4xxx)
            ['code' => '4000', 'name' => 'Service Revenue',         'type' => Account::TYPE_REVENUE],
            ['code' => '4100', 'name' => 'Roadside Assistance Revenue', 'type' => Account::TYPE_REVENUE],
            ['code' => '4200', 'name' => 'Towing Revenue',          'type' => Account::TYPE_REVENUE],
            ['code' => '4300', 'name' => 'Other Revenue',           'type' => Account::TYPE_REVENUE],
            ['code' => '4400', 'name' => 'Platform Revenue (Honk/Urgently)', 'type' => Account::TYPE_REVENUE],
            ['code' => '4500', 'name' => 'Square Payment Revenue',  'type' => Account::TYPE_REVENUE],

            // Cost of Goods Sold (5xxx)
            ['code' => '5100', 'name' => 'Parts & Materials',       'type' => Account::TYPE_COGS],
            ['code' => '5200', 'name' => 'Subcontractor Payments',  'type' => Account::TYPE_COGS],

            // Expenses (6xxx)
            ['code' => '6100', 'name' => 'Salaries & Wages',        'type' => Account::TYPE_EXPENSE],
            ['code' => '6150', 'name' => 'Fuel',                    'type' => Account::TYPE_EXPENSE],
            ['code' => '6200', 'name' => 'Vehicle Repairs & Maintenance', 'type' => Account::TYPE_EXPENSE],
            ['code' => '6250', 'name' => 'Vehicle Insurance',       'type' => Account::TYPE_EXPENSE],
            ['code' => '6300', 'name' => 'General Insurance',       'type' => Account::TYPE_EXPENSE],
            ['code' => '6400', 'name' => 'Supplies',                'type' => Account::TYPE_EXPENSE],
            ['code' => '6500', 'name' => 'Licensing & Permits',     'type' => Account::TYPE_EXPENSE],
            ['code' => '6600', 'name' => 'Tools & Equipment',       'type' => Account::TYPE_EXPENSE],
            ['code' => '6700', 'name' => 'Marketing & Advertising', 'type' => Account::TYPE_EXPENSE],
            ['code' => '6750', 'name' => 'Software & Subscriptions','type' => Account::TYPE_EXPENSE],
            ['code' => '6800', 'name' => 'Office Expenses',         'type' => Account::TYPE_EXPENSE],
            ['code' => '6850', 'name' => 'Bank & Processing Fees',  'type' => Account::TYPE_EXPENSE],
            ['code' => '6900', 'name' => 'Other Expenses',          'type' => Account::TYPE_EXPENSE],
        ];

        foreach ($general as $data) {
            Account::firstOrCreate(
                ['code' => $data['code'], 'scope' => Account::SCOPE_GENERAL],
                array_merge($data, ['scope' => Account::SCOPE_GENERAL])
            );
        }

        // ── Import (AI categorisation for scanned records) ──
        $import = [
            // Asset / bank accounts (for transfers)
            ['code' => '1100', 'name' => 'Cash / Undeposited Funds', 'type' => Account::TYPE_ASSET],
            ['code' => '1110', 'name' => 'Bank Account (Checking)',  'type' => Account::TYPE_ASSET],
            ['code' => '1120', 'name' => 'Savings Account',         'type' => Account::TYPE_ASSET],

            // Equity
            ['code' => '3100', 'name' => 'Owner Draw / Personal',   'type' => Account::TYPE_EQUITY],

            // Revenue
            ['code' => '4000', 'name' => 'Service Revenue',         'type' => Account::TYPE_REVENUE],
            ['code' => '4100', 'name' => 'Roadside Assistance Income', 'type' => Account::TYPE_REVENUE],
            ['code' => '4200', 'name' => 'Towing Income',           'type' => Account::TYPE_REVENUE],
            ['code' => '4300', 'name' => 'Other Income',            'type' => Account::TYPE_REVENUE],
            ['code' => '4400', 'name' => 'Honk / Urgently Payouts', 'type' => Account::TYPE_REVENUE],
            ['code' => '4500', 'name' => 'Square Deposits',         'type' => Account::TYPE_REVENUE],

            // COGS
            ['code' => '5100', 'name' => 'Parts & Materials',       'type' => Account::TYPE_COGS],
            ['code' => '5200', 'name' => 'Subcontractor / Provider Payments', 'type' => Account::TYPE_COGS],

            // Expenses — granular categories for bank-statement scanning
            ['code' => '6100', 'name' => 'Payroll / Wages',         'type' => Account::TYPE_EXPENSE],
            ['code' => '6150', 'name' => 'Gas / Fuel',              'type' => Account::TYPE_EXPENSE],
            ['code' => '6200', 'name' => 'Vehicle Repair / Maintenance', 'type' => Account::TYPE_EXPENSE],
            ['code' => '6250', 'name' => 'Vehicle Insurance',       'type' => Account::TYPE_EXPENSE],
            ['code' => '6300', 'name' => 'Business Insurance',      'type' => Account::TYPE_EXPENSE],
            ['code' => '6350', 'name' => 'Phone / Internet',        'type' => Account::TYPE_EXPENSE],
            ['code' => '6400', 'name' => 'Supplies',                'type' => Account::TYPE_EXPENSE],
            ['code' => '6450', 'name' => 'Food / Meals',            'type' => Account::TYPE_EXPENSE],
            ['code' => '6500', 'name' => 'Licensing / Registration','type' => Account::TYPE_EXPENSE],
            ['code' => '6550', 'name' => 'Rent / Lease Payments',   'type' => Account::TYPE_EXPENSE],
            ['code' => '6600', 'name' => 'Tools & Equipment',       'type' => Account::TYPE_EXPENSE],
            ['code' => '6650', 'name' => 'Uniforms / Work Clothes', 'type' => Account::TYPE_EXPENSE],
            ['code' => '6700', 'name' => 'Marketing / Advertising', 'type' => Account::TYPE_EXPENSE],
            ['code' => '6750', 'name' => 'Software / Subscriptions','type' => Account::TYPE_EXPENSE],
            ['code' => '6800', 'name' => 'Office / Admin',          'type' => Account::TYPE_EXPENSE],
            ['code' => '6850', 'name' => 'Bank Fees / Processing Fees', 'type' => Account::TYPE_EXPENSE],
            ['code' => '6900', 'name' => 'Miscellaneous Expense',   'type' => Account::TYPE_EXPENSE],
        ];

        foreach ($import as $data) {
            Account::firstOrCreate(
                ['code' => $data['code'], 'scope' => Account::SCOPE_IMPORT],
                array_merge($data, ['scope' => Account::SCOPE_IMPORT])
            );
        }
    }
}
