<?php

use App\Models\Account;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $desiredGeneral = [
        ['code' => '1000', 'name' => 'Checking Account', 'type' => Account::TYPE_ASSET],
        ['code' => '1010', 'name' => 'Savings Account', 'type' => Account::TYPE_ASSET],
        ['code' => '1020', 'name' => 'Petty Cash', 'type' => Account::TYPE_ASSET],
        ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => Account::TYPE_ASSET],
        ['code' => '1200', 'name' => 'Parts & Supplies Inventory', 'type' => Account::TYPE_ASSET],
        ['code' => '1500', 'name' => 'Vehicles & Equipment', 'type' => Account::TYPE_ASSET],
        ['code' => '1510', 'name' => 'Accumulated Depreciation', 'type' => Account::TYPE_ASSET],
        ['code' => '1600', 'name' => 'Security Deposits', 'type' => Account::TYPE_ASSET],
        ['code' => '2000', 'name' => 'Accounts Payable', 'type' => Account::TYPE_LIABILITY],
        ['code' => '2010', 'name' => 'Credit Card Payable', 'type' => Account::TYPE_LIABILITY],
        ['code' => '2100', 'name' => 'Payroll Taxes Payable', 'type' => Account::TYPE_LIABILITY],
        ['code' => '2110', 'name' => 'Sales Tax Payable', 'type' => Account::TYPE_LIABILITY],
        ['code' => '2200', 'name' => 'Vehicle Loan Payable', 'type' => Account::TYPE_LIABILITY],
        ['code' => '2210', 'name' => 'Equipment Loan Payable', 'type' => Account::TYPE_LIABILITY],
        ['code' => '2300', 'name' => 'Deferred Revenue', 'type' => Account::TYPE_LIABILITY],
        ['code' => '3000', 'name' => "Owner's Capital", 'type' => Account::TYPE_EQUITY],
        ['code' => '3100', 'name' => "Owner's Draw", 'type' => Account::TYPE_EQUITY],
        ['code' => '3900', 'name' => 'Retained Earnings', 'type' => Account::TYPE_EQUITY],
        ['code' => '4000', 'name' => 'Service Revenue', 'type' => Account::TYPE_REVENUE],
        ['code' => '4010', 'name' => 'Mobile Mechanic Revenue', 'type' => Account::TYPE_REVENUE],
        ['code' => '4020', 'name' => 'Parts & Supplies Revenue', 'type' => Account::TYPE_REVENUE],
        ['code' => '4030', 'name' => 'Membership / Subscription Fees', 'type' => Account::TYPE_REVENUE],
        ['code' => '4040', 'name' => 'Fleet Account Revenue', 'type' => Account::TYPE_REVENUE],
        ['code' => '4900', 'name' => 'Other Income', 'type' => Account::TYPE_REVENUE],
        ['code' => '5000', 'name' => 'Parts & Supplies Used', 'type' => Account::TYPE_COGS],
        ['code' => '5010', 'name' => 'Subcontractor Labor', 'type' => Account::TYPE_COGS],
        ['code' => '5020', 'name' => 'Fuel (Job-Related)', 'type' => Account::TYPE_COGS],
        ['code' => '5030', 'name' => 'Towing Equipment Rental', 'type' => Account::TYPE_COGS],
        ['code' => '6000', 'name' => 'Payroll – Technicians', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6010', 'name' => 'Payroll – Admin / Dispatch', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6020', 'name' => 'Payroll Taxes – Employer', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6030', 'name' => 'Employee Benefits', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6100', 'name' => 'Vehicle Fuel', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6110', 'name' => 'Vehicle Maintenance & Repairs', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6120', 'name' => 'Vehicle Insurance', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6130', 'name' => 'Vehicle Registration & Licenses', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6200', 'name' => 'Commercial Insurance', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6210', 'name' => "Workers' Compensation Insurance", 'type' => Account::TYPE_EXPENSE],
        ['code' => '6300', 'name' => 'Rent / Facility Costs', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6310', 'name' => 'Utilities', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6400', 'name' => 'Software & Subscriptions', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6410', 'name' => 'Phone & Communications', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6420', 'name' => 'SMS / Messaging Services', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6500', 'name' => 'Advertising & Marketing', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6510', 'name' => 'Website & Online Presence', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6600', 'name' => 'Professional Services', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6700', 'name' => 'Bank & Processing Fees', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6710', 'name' => 'Loan Interest', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6800', 'name' => 'Depreciation Expense', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6900', 'name' => 'Uniforms & Safety Gear', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6910', 'name' => 'Tools & Small Equipment', 'type' => Account::TYPE_EXPENSE],
        ['code' => '6990', 'name' => 'Miscellaneous Expense', 'type' => Account::TYPE_EXPENSE],
    ];

    private array $generalReferenceMap = [
        '1010' => '1000',
        '1050' => '1000',
        '1120' => '1010',
        '1300' => '1600',
        '1510' => '1500',
        '1590' => '1510',
        '2020' => '2110',
        '2050' => '2300',
        '2060' => '2300',
        '3100' => '3000',
        '3200' => '3100',
        '3300' => '3900',
        '4010' => '4000',
        '4020' => '4000',
        '4030' => '4000',
        '4040' => '4000',
        '4050' => '4010',
        '4100' => '4020',
        '4110' => '4020',
        '4120' => '4020',
        '4200' => '4000',
        '4250' => '4000',
        '4300' => '4900',
        '4400' => '4040',
        '4500' => '4900',
        '5010' => '5000',
        '5020' => '5000',
        '5100' => '5020',
        '5200' => '5000',
        '5300' => '5000',
        '5400' => '5010',
        '6000' => '6100',
        '6010' => '6110',
        '6020' => '6110',
        '6050' => '6000',
        '6100' => '6500',
        '6110' => '6500',
        '6120' => '6400',
        '6130' => '6410',
        '6140' => '6420',
        '6250' => '6120',
        '6300' => '6200',
        '6400' => '6990',
        '6500' => '6130',
        '6600' => '6910',
        '6800' => '6990',
        '6850' => '6700',
        '6900' => '6990',
        '7000' => '6700',
        '7010' => '6700',
    ];

    public function up(): void
    {
        $this->syncGeneralAccounts();
        $this->syncImportAccounts();
    }

    public function down(): void
    {
        // Intentionally left as a no-op. This migration consolidates live account
        // references and retires legacy chart rows, which is not safely reversible.
    }

    private function syncGeneralAccounts(): void
    {
        foreach ($this->desiredGeneral as $account) {
            $this->upsertAccount(Account::SCOPE_GENERAL, $account);
        }

        foreach ($this->generalReferenceMap as $oldCode => $newCode) {
            $sourceId = $this->accountId(Account::SCOPE_GENERAL, $oldCode);
            $targetId = $this->accountId(Account::SCOPE_GENERAL, $newCode);

            if (! $sourceId || ! $targetId || $sourceId === $targetId) {
                continue;
            }

            $this->remapReferences($sourceId, $targetId);
        }

        foreach ($this->desiredGeneral as $account) {
            $this->upsertAccount(Account::SCOPE_GENERAL, $account);
        }

        $desiredCodes = array_column($this->desiredGeneral, 'code');

        $legacyAccounts = DB::table('accounts')
            ->where('scope', Account::SCOPE_GENERAL)
            ->whereNotIn('code', $desiredCodes)
            ->get(['id']);

        foreach ($legacyAccounts as $account) {
            if ($this->isReferenced($account->id)) {
                DB::table('accounts')
                    ->where('id', $account->id)
                    ->update([
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);

                continue;
            }

            DB::table('accounts')->where('id', $account->id)->delete();
        }
    }

    private function syncImportAccounts(): void
    {
        foreach ($this->desiredGeneral as $account) {
            $this->upsertAccount(Account::SCOPE_IMPORT, $account);
        }

        $desiredCodes = array_column($this->desiredGeneral, 'code');

        DB::table('accounts')
            ->where('scope', Account::SCOPE_IMPORT)
            ->whereNotIn('code', $desiredCodes)
            ->delete();
    }

    private function upsertAccount(string $scope, array $account): void
    {
        $existing = DB::table('accounts')
            ->where('scope', $scope)
            ->where('code', $account['code'])
            ->first();

        $payload = [
            'name' => $account['name'],
            'type' => $account['type'],
            'scope' => $scope,
            'is_active' => true,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('accounts')->where('id', $existing->id)->update($payload);
            return;
        }

        DB::table('accounts')->insert(array_merge($payload, [
            'code' => $account['code'],
            'description' => null,
            'created_at' => now(),
        ]));
    }

    private function accountId(string $scope, string $code): ?int
    {
        return DB::table('accounts')
            ->where('scope', $scope)
            ->where('code', $code)
            ->value('id');
    }

    private function remapReferences(int $sourceId, int $targetId): void
    {
        foreach ($this->referenceColumns() as $table => $columns) {
            foreach ($columns as $column) {
                DB::table($table)
                    ->where($column, $sourceId)
                    ->update([$column => $targetId]);
            }
        }
    }

    private function isReferenced(int $accountId): bool
    {
        foreach ($this->referenceColumns() as $table => $columns) {
            foreach ($columns as $column) {
                if (DB::table($table)->where($column, $accountId)->exists()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function referenceColumns(): array
    {
        return [
            'journal_lines' => ['account_id'],
            'catalog_items' => ['revenue_account_id', 'cogs_account_id'],
            'vendors' => ['default_expense_account_id'],
            'vendor_document_lines' => ['expense_account_id', 'cogs_account_id'],
            'document_line_items' => ['account_id'],
            'accounts' => ['parent_account_id'],
        ];
    }
};