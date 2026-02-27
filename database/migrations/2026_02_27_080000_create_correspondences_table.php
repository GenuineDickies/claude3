<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correspondences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 30); // sms, phone, email, in_person, other
            $table->string('direction', 10); // inbound, outbound
            $table->string('subject', 255)->nullable();
            $table->text('body')->nullable();
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('logged_at');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('outcome', 100)->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'logged_at']);
            $table->index(['service_request_id', 'logged_at']);
            $table->index('channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correspondences');
    }
};
