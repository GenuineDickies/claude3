<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technician_profiles', function (Blueprint $table) {
            $table->string('sms_phone', 20)->nullable()->after('emergency_contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('technician_profiles', function (Blueprint $table) {
            $table->dropColumn('sms_phone');
        });
    }
};