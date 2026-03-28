<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add parent_account_id to accounts for hierarchy
        if (! Schema::hasColumn('accounts', 'parent_account_id')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->foreignId('parent_account_id')->nullable()->after('scope')
                    ->constrained('accounts')->nullOnDelete();
            });
        }

        // 2. Add accounting metadata to catalog_items
        Schema::table('catalog_items', function (Blueprint $table) {
            if (! Schema::hasColumn('catalog_items', 'revenue_account_id')) {
                $table->foreignId('revenue_account_id')->nullable()->after('is_active')
                    ->constrained('accounts')->nullOnDelete();
            }
            if (! Schema::hasColumn('catalog_items', 'cogs_account_id')) {
                $table->foreignId('cogs_account_id')->nullable()->after('revenue_account_id')
                    ->constrained('accounts')->nullOnDelete();
            }
            if (! Schema::hasColumn('catalog_items', 'core_required')) {
                $table->boolean('core_required')->default(false)->after('cogs_account_id');
            }
            if (! Schema::hasColumn('catalog_items', 'core_amount')) {
                $table->decimal('core_amount', 10, 2)->default(0)->after('core_required');
            }
            if (! Schema::hasColumn('catalog_items', 'taxable')) {
                $table->boolean('taxable')->default(true)->after('core_amount');
            }
        });

        // 3. Vendors
        if (! Schema::hasTable('vendors')) {
            Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 10)->nullable();
            $table->string('zip', 20)->nullable();
            $table->string('account_number')->nullable();
            $table->string('payment_terms', 50)->nullable();
            $table->foreignId('default_expense_account_id')->nullable()
                  ->constrained('accounts')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('name');
            });
        }

        // 4. Vendor Documents (receipts/invoices from suppliers)
        if (! Schema::hasTable('vendor_documents')) {
            Schema::create('vendor_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->date('document_date');
            $table->string('document_type', 20); // receipt, invoice
            $table->string('vendor_document_number')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('payment_method', 30)->nullable();
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();

            // Optional link to a job (service request or work order)
            $table->string('job_link_type', 50)->nullable();
            $table->unsignedBigInteger('job_link_id')->nullable();

            $table->string('status', 20)->default('draft'); // draft, posted, void
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index(['job_link_type', 'job_link_id']);
            $table->index('document_date');
            });
        }

        // 5. Vendor Document Lines
        if (! Schema::hasTable('vendor_document_lines')) {
            Schema::create('vendor_document_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_document_id')->constrained()->cascadeOnDelete();
            $table->string('line_type', 30)->default('expense'); // part, service, expense, core_charge, shipping, tax
            $table->string('description');
            $table->foreignId('part_id')->nullable()->constrained('catalog_items')->nullOnDelete();
            $table->decimal('qty', 10, 3)->default(1);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->decimal('core_amount', 10, 2)->default(0);
            $table->boolean('taxable')->default(false);
            $table->foreignId('expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('cogs_account_id')->nullable()->constrained('accounts')->nullOnDelete();

            // Optional per-line job link
            $table->unsignedBigInteger('job_link_id')->nullable();
            $table->timestamps();

            $table->index('vendor_document_id');
            });
        }

        // 6. Vendor Document Attachments
        if (! Schema::hasTable('vendor_document_attachments')) {
            Schema::create('vendor_document_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_document_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_type', 50)->nullable();
            $table->string('original_filename')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();
            });
        }

        // 7. Document Accounting Links (traceability)
        if (! Schema::hasTable('document_accounting_links')) {
            Schema::create('document_accounting_links', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 50);
            $table->unsignedBigInteger('document_id');
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['document_type', 'document_id']);
            $table->index('journal_entry_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_accounting_links');
        Schema::dropIfExists('vendor_document_attachments');
        Schema::dropIfExists('vendor_document_lines');
        Schema::dropIfExists('vendor_documents');
        Schema::dropIfExists('vendors');

        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropForeign(['revenue_account_id']);
            $table->dropForeign(['cogs_account_id']);
            $table->dropColumn(['revenue_account_id', 'cogs_account_id', 'core_required', 'core_amount', 'taxable']);
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['parent_account_id']);
            $table->dropColumn('parent_account_id');
        });
    }
};
