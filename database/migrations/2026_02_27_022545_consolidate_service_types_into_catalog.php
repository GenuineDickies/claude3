<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add catalog_item_id column to service_requests
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('catalog_item_id')
                ->nullable()
                ->after('service_type_id')
                ->constrained('catalog_items')
                ->nullOnDelete();
        });

        // 2. Migrate existing service_types → catalog system
        //    Create a "Services" category if it doesn't exist, then create
        //    catalog items for each service type and point service_requests at them.
        $categoryId = DB::table('catalog_categories')
            ->where('type', 'service')
            ->value('id');

        if (! $categoryId) {
            $categoryId = DB::table('catalog_categories')->insertGetId([
                'name'        => 'Services',
                'type'        => 'service',
                'description' => 'Migrated from Service Types',
                'sort_order'  => 0,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        $serviceTypes = DB::table('service_types')->get();

        foreach ($serviceTypes as $st) {
            // Check if a catalog item with the same name already exists in this category
            $existingItem = DB::table('catalog_items')
                ->where('catalog_category_id', $categoryId)
                ->where('name', $st->name)
                ->first();

            if ($existingItem) {
                $catalogItemId = $existingItem->id;
            } else {
                $catalogItemId = DB::table('catalog_items')->insertGetId([
                    'catalog_category_id' => $categoryId,
                    'name'                => $st->name,
                    'sku'                 => null,
                    'description'         => null,
                    'unit_price'          => $st->default_price,
                    'unit'                => 'each',
                    'pricing_type'        => 'fixed',
                    'is_active'           => $st->is_active,
                    'sort_order'          => $st->sort_order,
                    'created_at'          => $st->created_at,
                    'updated_at'          => now(),
                ]);
            }

            // Point all service_requests that used this service_type to the new catalog item
            DB::table('service_requests')
                ->where('service_type_id', $st->id)
                ->update(['catalog_item_id' => $catalogItemId]);
        }

        // 3. Drop old service_type_id FK and column
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_type_id');
        });

        // 4. Drop service_types table
        Schema::dropIfExists('service_types');
    }

    public function down(): void
    {
        // Recreate service_types table
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('default_price', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Re-add service_type_id to service_requests
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('service_type_id')
                ->nullable()
                ->after('vehicle_id')
                ->constrained()
                ->nullOnDelete();
        });

        // Drop catalog_item_id
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('catalog_item_id');
        });
    }
};
