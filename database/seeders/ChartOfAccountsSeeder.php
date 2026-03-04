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
            ['code' => '1000', 'name' => 'Cash',                       'type' => Account::TYPE_ASSET],
            ['code' => '1010', 'name' => 'Checking',                   'type' => Account::TYPE_ASSET],
            ['code' => '1120', 'name' => 'Business Savings',           'type' => Account::TYPE_ASSET],
            ['code' => '1050', 'name' => 'Square Clearing',            'type' => Account::TYPE_ASSET],
            ['code' => '1100', 'name' => 'Accounts Receivable',        'type' => Account::TYPE_ASSET],
            ['code' => '1300', 'name' => 'Prepaid Expenses',           'type' => Account::TYPE_ASSET],
            ['code' => '1200', 'name' => 'Parts Inventory',            'type' => Account::TYPE_ASSET],
            ['code' => '1500', 'name' => 'Service Vehicle',            'type' => Account::TYPE_ASSET],
            ['code' => '1510', 'name' => 'Tools and Equipment',        'type' => Account::TYPE_ASSET],
            ['code' => '1590', 'name' => 'Accumulated Depreciation',   'type' => Account::TYPE_ASSET],

            // Liabilities (2xxx)
            ['code' => '2000', 'name' => 'Accounts Payable',           'type' => Account::TYPE_LIABILITY],
            ['code' => '2010', 'name' => 'Credit Card Payable',        'type' => Account::TYPE_LIABILITY],
            ['code' => '2020', 'name' => 'Sales Tax Payable',          'type' => Account::TYPE_LIABILITY],
            ['code' => '2300', 'name' => 'Customer Deposits',          'type' => Account::TYPE_LIABILITY],
            ['code' => '2050', 'name' => 'Core Deposits Payable',      'type' => Account::TYPE_LIABILITY],
            ['code' => '2060', 'name' => 'Customer Refunds Payable',   'type' => Account::TYPE_LIABILITY],

            // Equity (3xxx)
            ['code' => '3000', 'name' => 'Owner Equity',               'type' => Account::TYPE_EQUITY],
            ['code' => '3100', 'name' => 'Owner Contributions',        'type' => Account::TYPE_EQUITY],
            ['code' => '3200', 'name' => 'Owner Draw',                 'type' => Account::TYPE_EQUITY],
            ['code' => '3300', 'name' => 'Retained Earnings',          'type' => Account::TYPE_EQUITY],

            // Revenue (4xxx)
            ['code' => '4000', 'name' => 'Roadside Service Revenue',   'type' => Account::TYPE_REVENUE],
            ['code' => '4010', 'name' => 'Jump Start Service',         'type' => Account::TYPE_REVENUE],
            ['code' => '4020', 'name' => 'Tire Change Service',        'type' => Account::TYPE_REVENUE],
            ['code' => '4030', 'name' => 'Lockout Service',            'type' => Account::TYPE_REVENUE],
            ['code' => '4040', 'name' => 'Battery Installation Labor', 'type' => Account::TYPE_REVENUE],
            ['code' => '4050', 'name' => 'Mobile Mechanic Labor',      'type' => Account::TYPE_REVENUE],
            ['code' => '4100', 'name' => 'Parts Sales',                'type' => Account::TYPE_REVENUE],
            ['code' => '4110', 'name' => 'Battery Sales',              'type' => Account::TYPE_REVENUE],
            ['code' => '4120', 'name' => 'Starter Sales',              'type' => Account::TYPE_REVENUE],
            ['code' => '4200', 'name' => 'Fuel Delivery Revenue',      'type' => Account::TYPE_REVENUE],
            ['code' => '4250', 'name' => 'Towing Revenue',             'type' => Account::TYPE_REVENUE],
            ['code' => '4300', 'name' => 'Other Revenue',              'type' => Account::TYPE_REVENUE],
            ['code' => '4400', 'name' => 'Platform Revenue (Honk/Urgently)', 'type' => Account::TYPE_REVENUE],
            ['code' => '4500', 'name' => 'Square Payment Revenue',     'type' => Account::TYPE_REVENUE],

            // Cost of Goods Sold (5xxx)
            ['code' => '5000', 'name' => 'Parts Cost of Goods Sold',   'type' => Account::TYPE_COGS],
            ['code' => '5010', 'name' => 'Battery Cost',               'type' => Account::TYPE_COGS],
            ['code' => '5020', 'name' => 'Starter Cost',               'type' => Account::TYPE_COGS],
            ['code' => '5100', 'name' => 'Fuel Cost of Goods Sold',    'type' => Account::TYPE_COGS],
            ['code' => '5200', 'name' => 'Shop Supplies Cost',         'type' => Account::TYPE_COGS],
            ['code' => '5300', 'name' => 'Parts & Materials',          'type' => Account::TYPE_COGS],
            ['code' => '5400', 'name' => 'Subcontractor Payments',     'type' => Account::TYPE_COGS],

            // Expenses (6xxx)
            ['code' => '6000', 'name' => 'Vehicle Fuel Expense',       'type' => Account::TYPE_EXPENSE],
            ['code' => '6010', 'name' => 'Vehicle Maintenance',        'type' => Account::TYPE_EXPENSE],
            ['code' => '6020', 'name' => 'Vehicle Repairs',            'type' => Account::TYPE_EXPENSE],
            ['code' => '6050', 'name' => 'Salaries & Wages',           'type' => Account::TYPE_EXPENSE],
            ['code' => '6100', 'name' => 'Advertising',                'type' => Account::TYPE_EXPENSE],
            ['code' => '6110', 'name' => 'Google Ads',                 'type' => Account::TYPE_EXPENSE],
            ['code' => '6120', 'name' => 'Software Subscriptions',     'type' => Account::TYPE_EXPENSE],
            ['code' => '6130', 'name' => 'Phone and Communications',   'type' => Account::TYPE_EXPENSE],
            ['code' => '6140', 'name' => 'SMS Messaging',              'type' => Account::TYPE_EXPENSE],
            ['code' => '6250', 'name' => 'Vehicle Insurance',          'type' => Account::TYPE_EXPENSE],
            ['code' => '6300', 'name' => 'General Insurance',          'type' => Account::TYPE_EXPENSE],
            ['code' => '6400', 'name' => 'Supplies',                   'type' => Account::TYPE_EXPENSE],
            ['code' => '6500', 'name' => 'Licensing & Permits',        'type' => Account::TYPE_EXPENSE],
            ['code' => '6600', 'name' => 'Tools & Equipment',          'type' => Account::TYPE_EXPENSE],
            ['code' => '6800', 'name' => 'Office Expenses',            'type' => Account::TYPE_EXPENSE],
            ['code' => '6850', 'name' => 'Bank & Processing Fees',     'type' => Account::TYPE_EXPENSE],
            ['code' => '6900', 'name' => 'Other Expenses',             'type' => Account::TYPE_EXPENSE],

            // Payment Processing (7xxx — treated as expenses)
            ['code' => '7000', 'name' => 'Merchant Processing Fees',   'type' => Account::TYPE_EXPENSE],
            ['code' => '7010', 'name' => 'Square Fees',                'type' => Account::TYPE_EXPENSE],
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
            ['code' => '1000', 'name' => 'Cash / Undeposited Funds', 'type' => Account::TYPE_ASSET],
            ['code' => '1010', 'name' => 'Bank Account (Checking)',  'type' => Account::TYPE_ASSET],
            ['code' => '1120', 'name' => 'Savings Account',         'type' => Account::TYPE_ASSET],

            // Equity
            ['code' => '3200', 'name' => 'Owner Draw / Personal',   'type' => Account::TYPE_EQUITY],

            // Revenue
            ['code' => '4000', 'name' => 'Service Revenue',         'type' => Account::TYPE_REVENUE],
            ['code' => '4100', 'name' => 'Roadside Assistance Income', 'type' => Account::TYPE_REVENUE],
            ['code' => '4250', 'name' => 'Towing Income',           'type' => Account::TYPE_REVENUE],
            ['code' => '4300', 'name' => 'Other Income',            'type' => Account::TYPE_REVENUE],
            ['code' => '4400', 'name' => 'Honk / Urgently Payouts', 'type' => Account::TYPE_REVENUE],
            ['code' => '4500', 'name' => 'Square Deposits',         'type' => Account::TYPE_REVENUE],

            // COGS
            ['code' => '5300', 'name' => 'Parts & Materials',       'type' => Account::TYPE_COGS],
            ['code' => '5400', 'name' => 'Subcontractor / Provider Payments', 'type' => Account::TYPE_COGS],

            // Expenses — granular categories for bank-statement scanning
            ['code' => '6050', 'name' => 'Payroll / Wages',         'type' => Account::TYPE_EXPENSE],
            ['code' => '6000', 'name' => 'Gas / Fuel',              'type' => Account::TYPE_EXPENSE],
            ['code' => '6020', 'name' => 'Vehicle Repair / Maintenance', 'type' => Account::TYPE_EXPENSE],
            ['code' => '6250', 'name' => 'Vehicle Insurance',       'type' => Account::TYPE_EXPENSE],
            ['code' => '6300', 'name' => 'Business Insurance',      'type' => Account::TYPE_EXPENSE],
            ['code' => '6130', 'name' => 'Phone / Internet',        'type' => Account::TYPE_EXPENSE],
            ['code' => '6400', 'name' => 'Supplies',                'type' => Account::TYPE_EXPENSE],
            ['code' => '6450', 'name' => 'Food / Meals',            'type' => Account::TYPE_EXPENSE],
            ['code' => '6500', 'name' => 'Licensing / Registration','type' => Account::TYPE_EXPENSE],
            ['code' => '6550', 'name' => 'Rent / Lease Payments',   'type' => Account::TYPE_EXPENSE],
            ['code' => '6600', 'name' => 'Tools & Equipment',       'type' => Account::TYPE_EXPENSE],
            ['code' => '6650', 'name' => 'Uniforms / Work Clothes', 'type' => Account::TYPE_EXPENSE],
            ['code' => '6100', 'name' => 'Marketing / Advertising', 'type' => Account::TYPE_EXPENSE],
            ['code' => '6120', 'name' => 'Software / Subscriptions','type' => Account::TYPE_EXPENSE],
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
