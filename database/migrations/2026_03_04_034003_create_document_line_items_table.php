<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 3)->nullable();
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('category')->nullable();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_line_items');
    }
};
