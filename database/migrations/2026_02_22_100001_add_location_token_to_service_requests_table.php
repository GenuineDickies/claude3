<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('location_token', 64)->nullable()->unique()->after('longitude');
            $table->timestamp('location_token_expires_at')->nullable()->after('location_token');
            $table->timestamp('location_shared_at')->nullable()->after('location_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn(['location_token', 'location_token_expires_at', 'location_shared_at']);
        });
    }
};
