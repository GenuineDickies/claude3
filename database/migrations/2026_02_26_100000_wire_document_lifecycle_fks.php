<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wire the correct document lifecycle:
 *   ServiceRequest → Estimate → WorkOrder → ChangeOrder(s) → Invoice → Receipt
 *
 * Adds:
 *   - invoices.work_order_id  (invoice is generated from a completed work order)
 *   - receipts.invoice_id     (receipt is proof of payment for an invoice)
 *   - payment_records.invoice_id (payment is applied against an invoice)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('work_order_id')
                ->nullable()
                ->after('service_request_id')
                ->constrained()
                ->nullOnDelete();

            $table->index('work_order_id');
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->foreignId('invoice_id')
                ->nullable()
                ->after('service_request_id')
                ->constrained()
                ->nullOnDelete();

            $table->index('invoice_id');
        });

        Schema::table('payment_records', function (Blueprint $table) {
            $table->foreignId('invoice_id')
                ->nullable()
                ->after('service_request_id')
                ->constrained()
                ->nullOnDelete();

            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_order_id');
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_id');
        });

        Schema::table('payment_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_id');
        });
    }
};
