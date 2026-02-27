<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // License
            $table->string('drivers_license_number', 50)->nullable();
            $table->date('drivers_license_expiry')->nullable();

            // Insurance
            $table->string('insurance_policy_number', 100)->nullable();
            $table->date('insurance_expiry')->nullable();

            // Background check
            $table->date('background_check_date')->nullable();
            $table->string('background_check_status', 20)->nullable(); // clear, pending, failed

            // Drug screen
            $table->date('drug_screen_date')->nullable();
            $table->string('drug_screen_status', 20)->nullable(); // clear, pending, failed

            // Certifications (JSON array of {name, issued_date, expiry_date})
            $table->json('certifications')->nullable();

            // Emergency contact
            $table->string('emergency_contact_name', 100)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();

            // Service vehicle
            $table->string('vehicle_year', 4)->nullable();
            $table->string('vehicle_make', 50)->nullable();
            $table->string('vehicle_model', 50)->nullable();
            $table->string('vehicle_plate', 20)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_profiles');
    }
};
