<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('license_plate', 20)->nullable()->after('color');
            $table->string('vin', 32)->nullable()->after('license_plate');
            $table->index('license_plate');
            $table->index('vin');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->after('service_request_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vehicle_id');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['license_plate']);
            $table->dropIndex(['vin']);
            $table->dropColumn(['license_plate', 'vin']);
        });
    }
};