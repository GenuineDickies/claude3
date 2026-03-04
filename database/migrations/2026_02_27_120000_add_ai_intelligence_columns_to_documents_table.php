<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->json('ai_extracted_data')->nullable()->after('ai_tags');
            $table->string('ai_status', 20)->default('pending')->after('ai_extracted_data');
            $table->string('ai_suggested_category', 50)->nullable()->after('ai_status');
            $table->decimal('ai_confidence', 3, 2)->nullable()->after('ai_suggested_category');
            $table->timestamp('ai_processed_at')->nullable()->after('ai_confidence');
            $table->text('ai_error')->nullable()->after('ai_processed_at');

            $table->index('ai_status');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['ai_status']);
            $table->dropColumn([
                'ai_extracted_data',
                'ai_status',
                'ai_suggested_category',
                'ai_confidence',
                'ai_processed_at',
                'ai_error',
            ]);
        });
    }
};
