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
        Schema::create('document_transaction_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->date('transaction_date')->nullable();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->string('type', 30)->default('expense');       // expense, income, transfer
            $table->string('category', 50)->nullable();           // maps to Expense::CATEGORIES
            $table->string('vendor', 255)->nullable();
            $table->string('payment_method', 30)->nullable();     // maps to Expense::PAYMENT_METHODS
            $table->string('reference', 255)->nullable();         // check #, trans ID
            $table->string('account_code', 10)->nullable();       // AI-suggested chart-of-accounts code
            $table->json('raw_data')->nullable();                 // original row from AI
            $table->string('status', 20)->default('draft');       // draft, accepted, rejected
            $table->foreignId('created_expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->foreignId('created_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('document_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_transaction_imports');
    }
};
