<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->string('part_name');
            $table->string('part_number')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('vendor_phone', 30)->nullable();
            $table->string('vendor_invoice_number')->nullable();
            $table->date('install_date');
            $table->unsignedSmallInteger('warranty_months');
            $table->date('warranty_expires_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('warranty_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranties');
    }
};
