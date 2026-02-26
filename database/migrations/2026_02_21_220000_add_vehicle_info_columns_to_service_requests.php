<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('vehicle_year', 4)->nullable()->after('vehicle_id');
            $table->string('vehicle_make', 100)->nullable()->after('vehicle_year');
            $table->string('vehicle_model', 100)->nullable()->after('vehicle_make');
            $table->string('vehicle_color', 50)->nullable()->after('vehicle_model');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn(['vehicle_year', 'vehicle_make', 'vehicle_model', 'vehicle_color']);
        });
    }
};
