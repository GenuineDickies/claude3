<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number', 20)->unique();

            $table->date('date');
            $table->string('vendor', 200);
            $table->string('description', 500)->nullable();

            // Category enum stored as string
            $table->string('category', 30);

            $table->decimal('amount', 10, 2);

            // Payment method: cash, card, check, transfer
            $table->string('payment_method', 20)->nullable();
            $table->string('reference_number', 100)->nullable();

            // Receipt file upload
            $table->string('receipt_path')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('category');
            $table->index('date');
            $table->index('vendor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
