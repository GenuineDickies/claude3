<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->foreignId('payment_record_id')
                ->nullable()
                ->after('invoice_id')
                ->constrained('payment_records')
                ->nullOnDelete();

            $table->index('payment_record_id');
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_record_id');
        });
    }
};
