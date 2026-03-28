<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_request_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_request_id');
            $table->string('old_status', 20);
            $table->string('new_status', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('service_request_id');

            // This migration runs before service_requests in this codebase.
            // Add the FK only when the referenced table exists.
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
        Schema::dropIfExists('service_request_status_logs');
    }
};
