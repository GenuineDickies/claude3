<?php

use App\Models\Account;
use Illuminate\Database\Migrations\Migration;

/**
 * Ensure the Customer Deposits liability account exists
 * for tracking prepayments / deposits before an invoice is issued.
 */
return new class extends Migration
{
    public function up(): void
    {
        Account::firstOrCreate(
            ['code' => '2300'],
            [
                'name'      => 'Customer Deposits',
                'type'      => Account::TYPE_LIABILITY,
                'is_active' => true,
            ]
        );
    }

    public function down(): void
    {
        Account::where('code', '2300')->delete();
    }
};
