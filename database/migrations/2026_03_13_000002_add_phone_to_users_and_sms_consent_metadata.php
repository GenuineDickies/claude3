<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->json('sms_consent_meta')->nullable()->after('notification_preferences');
            $table->json('sms_opt_out_meta')->nullable()->after('sms_consent_meta');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['sms_consent_meta', 'sms_opt_out_meta']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};