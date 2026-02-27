<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // Assets
            ['code' => '1100', 'name' => 'Cash',                'type' => Account::TYPE_ASSET],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => Account::TYPE_ASSET],

            // Liabilities
            ['code' => '2100', 'name' => 'Accounts Payable',    'type' => Account::TYPE_LIABILITY],
            ['code' => '2200', 'name' => 'Sales Tax Payable',   'type' => Account::TYPE_LIABILITY],
            ['code' => '2300', 'name' => 'Customer Deposits',   'type' => Account::TYPE_LIABILITY],

            // Equity
            ['code' => '3000', 'name' => 'Owner\'s Equity',     'type' => Account::TYPE_EQUITY],
            ['code' => '3900', 'name' => 'Retained Earnings',   'type' => Account::TYPE_EQUITY],

            // Revenue
            ['code' => '4000', 'name' => 'Service Revenue',     'type' => Account::TYPE_REVENUE],

            // Cost of Goods Sold
            ['code' => '5100', 'name' => 'Parts & Materials',   'type' => Account::TYPE_COGS],

            // Expenses
            ['code' => '6100', 'name' => 'Salaries & Wages',    'type' => Account::TYPE_EXPENSE],
            ['code' => '6200', 'name' => 'Vehicle Expenses',    'type' => Account::TYPE_EXPENSE],
            ['code' => '6300', 'name' => 'Insurance',           'type' => Account::TYPE_EXPENSE],
            ['code' => '6400', 'name' => 'Supplies',            'type' => Account::TYPE_EXPENSE],
            ['code' => '6500', 'name' => 'Licensing & Permits', 'type' => Account::TYPE_EXPENSE],
            ['code' => '6600', 'name' => 'Tools & Equipment',   'type' => Account::TYPE_EXPENSE],
            ['code' => '6700', 'name' => 'Marketing',           'type' => Account::TYPE_EXPENSE],
            ['code' => '6800', 'name' => 'Office Expenses',     'type' => Account::TYPE_EXPENSE],
            ['code' => '6900', 'name' => 'Other Expenses',      'type' => Account::TYPE_EXPENSE],
        ];

        foreach ($accounts as $data) {
            Account::firstOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}
