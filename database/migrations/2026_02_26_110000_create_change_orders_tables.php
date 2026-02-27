<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->string('change_type')->default('informational');
            $table->text('description');
            $table->decimal('price_impact', 10, 2)->default(0);
            $table->boolean('requires_customer_approval')->default(false);
            $table->string('approval_status')->default('pending');
            $table->string('approval_method')->nullable();
            $table->string('approval_token', 100)->nullable()->unique();
            $table->timestamp('approval_token_expires_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('approved_by_name')->nullable();
            $table->json('approval_device_info')->nullable();
            $table->longText('signature_data')->nullable();
            $table->text('technician_notes')->nullable();
            $table->text('rejection_resolution')->nullable();
            $table->timestamps();

            $table->index(['work_order_id', 'approval_status']);
            $table->index(['requires_customer_approval', 'approval_status']);
        });

        Schema::create('change_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_order_items');
        Schema::dropIfExists('change_orders');
    }
};
