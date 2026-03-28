<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_signatures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_request_id');
            $table->longText('signature_data'); // base64 PNG
            $table->string('signer_name', 200);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('signed_at');
            $table->string('token', 64)->nullable()->unique();
            $table->timestamp('token_expires_at')->nullable();
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
        Schema::dropIfExists('service_signatures');
    }
};
