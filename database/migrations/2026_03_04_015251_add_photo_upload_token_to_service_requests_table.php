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
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('photo_upload_token', 40)->nullable()->unique()->after('location_shared_at');
            $table->timestamp('photo_upload_token_expires_at')->nullable()->after('photo_upload_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn(['photo_upload_token', 'photo_upload_token_expires_at']);
        });
    }
};
