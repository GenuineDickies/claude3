<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_request_id');
            $table->string('event', 50); // status_change, note, photo_uploaded, signature_captured, payment_collected, etc.
            $table->json('details')->nullable();
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('logged_at');
            $table->timestamps();

            $table->index('service_request_id');

            if (Schema::hasTable('service_requests')) {
                $table->foreign('service_request_id')
                    ->references('id')
                    ->on('service_requests')
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_logs');
    }
};
