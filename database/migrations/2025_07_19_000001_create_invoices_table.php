<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_request_id');
            $table->string('invoice_number', 20)->unique();

            // Status: draft, sent, paid, overdue, cancelled
            $table->string('status', 20)->default('draft');

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

            $table->date('due_date')->nullable();
            $table->string('payment_terms')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('company_snapshot'); // name, address, phone, email at time of issue

            $table->timestamps();

            $table->index('service_request_id');
            $table->index('status');

            if (Schema::hasTable('service_requests')) {
                $table->foreign('service_request_id')
                    ->references('id')
                    ->on('service_requests')
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
