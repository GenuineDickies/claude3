<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->string('receipt_number', 20)->unique();

            // Snapshot data at time of issue
            $table->string('customer_name');
            $table->string('customer_phone', 20)->nullable();
            $table->string('vehicle_description')->nullable();
            $table->string('service_description')->nullable();
            $table->string('service_location')->nullable();

            $table->json('line_items'); // snapshot of estimate items
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_rate', 6, 4)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            $table->string('payment_method', 30)->nullable();
            $table->string('payment_reference')->nullable();
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('company_snapshot'); // name, address, phone, email at time of issue

            $table->timestamps();

            $table->index('service_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
