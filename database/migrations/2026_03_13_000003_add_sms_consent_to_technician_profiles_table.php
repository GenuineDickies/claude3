<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technician_profiles', function (Blueprint $table) {
            $table->timestamp('sms_consent_at')->nullable()->after('sms_phone');
            $table->json('sms_consent_meta')->nullable()->after('sms_consent_at');
        });
    }

    public function down(): void
    {
        Schema::table('technician_profiles', function (Blueprint $table) {
            $table->dropColumn(['sms_consent_at', 'sms_consent_meta']);
        });
    }
};