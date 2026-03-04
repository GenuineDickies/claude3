<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_categories', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        // Drop the unique index first (SQLite requires this before dropping the column)
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropUnique(['sku']);
        });

        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropColumn('sku');
            $table->renameColumn('unit_price', 'base_cost');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->renameColumn('base_cost', 'unit_price');
            $table->string('sku')->nullable()->unique()->after('name');
        });

        Schema::table('catalog_categories', function (Blueprint $table) {
            $table->string('type')->default('service')->after('name');
        });
    }
};
