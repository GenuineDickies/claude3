<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->after('vehicle_id')->constrained()->nullOnDelete();
            $table->decimal('quoted_price', 8, 2)->nullable()->after('service_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vehicle_id');
            $table->dropConstrainedForeignId('service_type_id');
            $table->dropColumn('quoted_price');
        });
    }
};
