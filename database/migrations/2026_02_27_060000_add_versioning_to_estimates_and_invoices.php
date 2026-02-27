<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->string('estimate_number')->nullable()->after('service_request_id');
            $table->unsignedInteger('version')->default(1)->after('status');
            $table->boolean('is_locked')->default(false)->after('version');
            $table->timestamp('locked_at')->nullable()->after('is_locked');
            $table->unsignedBigInteger('parent_version_id')->nullable()->after('locked_at');

            $table->foreign('parent_version_id')
                ->references('id')
                ->on('estimates')
                ->nullOnDelete();

            $table->index('estimate_number');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('status');
            $table->boolean('is_locked')->default(false)->after('version');
            $table->timestamp('locked_at')->nullable()->after('is_locked');
            $table->unsignedBigInteger('parent_version_id')->nullable()->after('locked_at');

            $table->foreign('parent_version_id')
                ->references('id')
                ->on('invoices')
                ->nullOnDelete();

            // Change unique constraint from invoice_number alone to (invoice_number, version)
            $table->dropUnique(['invoice_number']);
            $table->unique(['invoice_number', 'version']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['parent_version_id']);
            $table->dropUnique(['invoice_number', 'version']);
            $table->unique('invoice_number');
            $table->dropColumn(['version', 'is_locked', 'locked_at', 'parent_version_id']);
        });

        Schema::table('estimates', function (Blueprint $table) {
            $table->dropForeign(['parent_version_id']);
            $table->dropIndex(['estimate_number']);
            $table->dropColumn(['estimate_number', 'version', 'is_locked', 'locked_at', 'parent_version_id']);
        });
    }
};
