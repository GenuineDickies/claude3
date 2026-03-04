<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Renumber account codes to match the accounting-engine specification.
 *
 * Uses a two-phase approach (temp prefix → final code) to avoid
 * unique-constraint collisions from circular/chained renames.
 */
return new class extends Migration
{
    // ── General-scope renames: [old_code, new_code] ──
    private array $generalRenames = [
        // Assets
        ['1100', '1000'],  // Cash
        ['1110', '1010'],  // Checking
        ['1150', '1050'],  // Square Clearing
        ['1200', '1100'],  // Accounts Receivable
        ['1400', '1200'],  // Parts Inventory

        // Liabilities
        ['2100', '2000'],  // Accounts Payable
        ['2200', '2020'],  // Sales Tax Payable
        ['2350', '2050'],  // Core Deposits Payable
        ['2360', '2060'],  // Customer Refunds Payable
        ['2400', '2010'],  // Credit Card Payable

        // Equity
        ['3050', '3100'],  // Owner Contributions
        ['3100', '3200'],  // Owner Draw
        ['3900', '3300'],  // Retained Earnings

        // Revenue
        ['4150', '4200'],  // Fuel Delivery Revenue
        ['4200', '4250'],  // Towing Revenue (extra — move out of spec 4200)

        // COGS
        ['5050', '5100'],  // Fuel Cost of Goods Sold
        ['5100', '5300'],  // Parts & Materials (extra — move out of spec 5100)
        ['5150', '5200'],  // Shop Supplies Cost
        ['5200', '5400'],  // Subcontractor Payments (extra — move out of spec 5200)

        // Expenses
        ['6100', '6050'],  // Salaries & Wages (extra — move out of spec 6100)
        ['6150', '6000'],  // Vehicle Fuel Expense
        ['6160', '6010'],  // Vehicle Maintenance
        ['6200', '6020'],  // Vehicle Repairs
        ['6700', '6100'],  // Advertising
        ['6710', '6110'],  // Google Ads
        ['6750', '6120'],  // Software Subscriptions
        ['6760', '6130'],  // Phone and Communications
        ['6770', '6140'],  // SMS Messaging
    ];

    // ── Import-scope renames (mirror general where applicable) ──
    private array $importRenames = [
        ['1100', '1000'],  // Cash / Undeposited Funds
        ['1110', '1010'],  // Bank Account (Checking)
        ['3100', '3200'],  // Owner Draw / Personal
        ['4200', '4250'],  // Towing Income
        ['5100', '5300'],  // Parts & Materials
        ['5200', '5400'],  // Subcontractor / Provider Payments
        ['6100', '6050'],  // Payroll / Wages
        ['6150', '6000'],  // Gas / Fuel
        ['6200', '6020'],  // Vehicle Repair / Maintenance
        ['6350', '6130'],  // Phone / Internet → align with spec 6130
        ['6700', '6100'],  // Marketing / Advertising
        ['6750', '6120'],  // Software / Subscriptions
    ];

    // ── Name corrections (general scope, applied AFTER code renames) ──
    private array $nameChanges = [
        ['1010', 'Checking'],                  // was "Business Checking"
        ['3000', 'Owner Equity'],              // was "Owner's Equity"
        ['3200', 'Owner Draw'],                // was "Owner's Draw"
        ['6120', 'Software Subscriptions'],    // was "Software & Subscriptions"
        ['6130', 'Phone and Communications'],  // was "Phone & Communications"
    ];

    public function up(): void
    {
        // Drop unique so temp codes don't collide
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropUnique(['code', 'scope']);
        });

        // Phase&nbsp;1 — park every moving account at a temp code (T + old code)
        $this->parkAtTemp($this->generalRenames, 'general');
        $this->parkAtTemp($this->importRenames, 'import');

        // Phase&nbsp;2 — move from temp to final spec code
        $this->moveToFinal($this->generalRenames, 'general');
        $this->moveToFinal($this->importRenames, 'import');

        // Phase&nbsp;3 — fix account names
        foreach ($this->nameChanges as [$code, $name]) {
            DB::table('accounts')
                ->where('code', $code)
                ->where('scope', 'general')
                ->update(['name' => $name]);
        }

        // Restore the composite unique
        Schema::table('accounts', function (Blueprint $table) {
            $table->unique(['code', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropUnique(['code', 'scope']);
        });

        // Reverse name changes first
        $reverseNames = [
            ['1010', 'Business Checking'],
            ['3000', 'Owner\'s Equity'],
            ['3200', 'Owner\'s Draw'],
            ['6120', 'Software & Subscriptions'],
            ['6130', 'Phone & Communications'],
        ];
        foreach ($reverseNames as [$code, $name]) {
            DB::table('accounts')
                ->where('code', $code)
                ->where('scope', 'general')
                ->update(['name' => $name]);
        }

        // Reverse code renames (swap old ↔ new)
        $reverseGeneral = array_map(fn ($r) => [$r[1], $r[0]], $this->generalRenames);
        $reverseImport  = array_map(fn ($r) => [$r[1], $r[0]], $this->importRenames);

        $this->parkAtTemp($reverseGeneral, 'general');
        $this->parkAtTemp($reverseImport, 'import');
        $this->moveToFinal($reverseGeneral, 'general');
        $this->moveToFinal($reverseImport, 'import');

        Schema::table('accounts', function (Blueprint $table) {
            $table->unique(['code', 'scope']);
        });
    }

    // ── Helpers ──

    private function parkAtTemp(array $renames, string $scope): void
    {
        foreach ($renames as [$oldCode]) {
            DB::table('accounts')
                ->where('code', $oldCode)
                ->where('scope', $scope)
                ->update(['code' => 'T' . $oldCode]);
        }
    }

    private function moveToFinal(array $renames, string $scope): void
    {
        foreach ($renames as [$oldCode, $newCode]) {
            DB::table('accounts')
                ->where('code', 'T' . $oldCode)
                ->where('scope', $scope)
                ->update(['code' => $newCode]);
        }
    }
};
