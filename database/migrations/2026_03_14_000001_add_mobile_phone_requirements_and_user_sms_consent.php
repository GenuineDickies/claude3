<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('requires_mobile_phone')->default(false)->after('description');
            $table->boolean('requires_sms_consent')->default(false)->after('requires_mobile_phone');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('sms_consent_at')->nullable()->after('phone');
            $table->json('sms_consent_meta')->nullable()->after('sms_consent_at');
        });

        DB::table('roles')->get(['id', 'role_name'])->each(function (object $role): void {
            if (in_array(strtolower((string) $role->role_name), ['administrator', 'technician'], true)) {
                DB::table('roles')->where('id', $role->id)->update([
                    'requires_mobile_phone' => true,
                    'requires_sms_consent' => true,
                ]);
            }
        });

        DB::table('technician_profiles')
            ->whereNotNull('sms_consent_at')
            ->get(['user_id', 'sms_consent_at', 'sms_consent_meta'])
            ->each(function (object $profile): void {
                DB::table('users')->where('id', $profile->user_id)->update([
                    'sms_consent_at' => $profile->sms_consent_at,
                    'sms_consent_meta' => $profile->sms_consent_meta,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sms_consent_at', 'sms_consent_meta']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['requires_mobile_phone', 'requires_sms_consent']);
        });
    }
};