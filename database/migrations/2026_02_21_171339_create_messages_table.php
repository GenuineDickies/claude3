<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('messages')) {
            return;
        }

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_request_id')->nullable();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->text('body')->nullable();
            $table->string('telnyx_message_id')->nullable()->unique();
            $table->string('status')->default('received'); // received, sent, delivered, failed
            $table->timestamps();

            $table->index('direction');

            if (Schema::hasTable('service_requests')) {
                $table->foreign('service_request_id')
                    ->references('id')
                    ->on('service_requests')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
