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
            ['code' => '1100', 'name' => 'Cash',                       'type' => Account::TYPE_ASSET],
            ['code' => '1110', 'name' => 'Business Checking',          'type' => Account::TYPE_ASSET],
            ['code' => '1120', 'name' => 'Business Savings',           'type' => Account::TYPE_ASSET],
            ['code' => '1150', 'name' => 'Square Clearing',            'type' => Account::TYPE_ASSET],
            ['code' => '1200', 'name' => 'Accounts Receivable',        'type' => Account::TYPE_ASSET],
            ['code' => '1300', 'name' => 'Prepaid Expenses',           'type' => Account::TYPE_ASSET],
            ['code' => '1400', 'name' => 'Parts Inventory',            'type' => Account::TYPE_ASSET],
            ['code' => '1500', 'name' => 'Service Vehicle',            'type' => Account::TYPE_ASSET],
            ['code' => '1510', 'name' => 'Tools and Equipment',        'type' => Account::TYPE_ASSET],
            ['code' => '1590', 'name' => 'Accumulated Depreciation',   'type' => Account::TYPE_ASSET],

            // Liabilities (2xxx)
            ['code' => '2100', 'name' => 'Accounts Payable',           'type' => Account::TYPE_LIABILITY],
            ['code' => '2200', 'name' => 'Sales Tax Payable',          'type' => Account::TYPE_LIABILITY],
            ['code' => '2300', 'name' => 'Customer Deposits',          'type' => Account::TYPE_LIABILITY],
            ['code' => '2350', 'name' => 'Core Deposits Payable',      'type' => Account::TYPE_LIABILITY],
            ['code' => '2360', 'name' => 'Customer Refunds Payable',   'type' => Account::TYPE_LIABILITY],
            ['code' => '2400', 'name' => 'Credit Card Payable',        'type' => Account::TYPE_LIABILITY],

            // Equity (3xxx)
            ['code' => '3000', 'name' => 'Owner\'s Equity',            'type' => Account::TYPE_EQUITY],
            ['code' => '3050', 'name' => 'Owner Contributions',        'type' => Account::TYPE_EQUITY],
            ['code' => '3100', 'name' => 'Owner\'s Draw',              'type' => Account::TYPE_EQUITY],
            ['code' => '3900', 'name' => 'Retained Earnings',          'type' => Account::TYPE_EQUITY],

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
            ['code' => '4150', 'name' => 'Fuel Delivery Revenue',      'type' => Account::TYPE_REVENUE],
            ['code' => '4200', 'name' => 'Towing Revenue',             'type' => Account::TYPE_REVENUE],
            ['code' => '4300', 'name' => 'Other Revenue',              'type' => Account::TYPE_REVENUE],
            ['code' => '4400', 'name' => 'Platform Revenue (Honk/Urgently)', 'type' => Account::TYPE_REVENUE],
            ['code' => '4500', 'name' => 'Square Payment Revenue',     'type' => Account::TYPE_REVENUE],

            // Cost of Goods Sold (5xxx)
            ['code' => '5000', 'name' => 'Parts Cost of Goods Sold',   'type' => Account::TYPE_COGS],
            ['code' => '5010', 'name' => 'Battery Cost',               'type' => Account::TYPE_COGS],
            ['code' => '5020', 'name' => 'Starter Cost',               'type' => Account::TYPE_COGS],
            ['code' => '5050', 'name' => 'Fuel Cost of Goods Sold',    'type' => Account::TYPE_COGS],
            ['code' => '5100', 'name' => 'Parts & Materials',          'type' => Account::TYPE_COGS],
            ['code' => '5150', 'name' => 'Shop Supplies Cost',         'type' => Account::TYPE_COGS],
            ['code' => '5200', 'name' => 'Subcontractor Payments',     'type' => Account::TYPE_COGS],

            // Expenses (6xxx)
            ['code' => '6100', 'name' => 'Salaries & Wages',           'type' => Account::TYPE_EXPENSE],
            ['code' => '6150', 'name' => 'Vehicle Fuel Expense',       'type' => Account::TYPE_EXPENSE],
            ['code' => '6160', 'name' => 'Vehicle Maintenance',        'type' => Account::TYPE_EXPENSE],
            ['code' => '6200', 'name' => 'Vehicle Repairs',            'type' => Account::TYPE_EXPENSE],
            ['code' => '6250', 'name' => 'Vehicle Insurance',          'type' => Account::TYPE_EXPENSE],
            ['code' => '6300', 'name' => 'General Insurance',          'type' => Account::TYPE_EXPENSE],
            ['code' => '6400', 'name' => 'Supplies',                   'type' => Account::TYPE_EXPENSE],
            ['code' => '6500', 'name' => 'Licensing & Permits',        'type' => Account::TYPE_EXPENSE],
            ['code' => '6600', 'name' => 'Tools & Equipment',          'type' => Account::TYPE_EXPENSE],
            ['code' => '6700', 'name' => 'Advertising',                'type' => Account::TYPE_EXPENSE],
            ['code' => '6710', 'name' => 'Google Ads',                 'type' => Account::TYPE_EXPENSE],
            ['code' => '6750', 'name' => 'Software & Subscriptions',   'type' => Account::TYPE_EXPENSE],
            ['code' => '6760', 'name' => 'Phone & Communications',     'type' => Account::TYPE_EXPENSE],
            ['code' => '6770', 'name' => 'SMS Messaging',              'type' => Account::TYPE_EXPENSE],
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
