<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_monitor_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('method', 10)->default('GET');
            $table->json('headers')->nullable();
            $table->unsignedSmallInteger('expected_status_code')->nullable();
            $table->unsignedInteger('check_interval_minutes')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->string('last_status', 20)->nullable(); // healthy, degraded, down
            $table->unsignedInteger('last_response_time_ms')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'last_checked_at']);
            $table->unique(['name', 'url']);
        });

        Schema::create('api_monitor_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('endpoint_id')->constrained('api_monitor_endpoints')->cascadeOnDelete();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->boolean('is_success')->default(false);
            $table->string('status', 20); // healthy, degraded, down
            $table->text('error_message')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['endpoint_id', 'checked_at']);
            $table->index(['status', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_monitor_runs');
        Schema::dropIfExists('api_monitor_endpoints');
    }
};
