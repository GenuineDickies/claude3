<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Allow inbox documents with no parent entity yet
            $table->string('documentable_type')->nullable()->change();
            $table->unsignedBigInteger('documentable_id')->nullable()->change();

            // Matching workflow columns
            $table->string('match_status', 20)->default('unmatched')->after('ai_error');
            $table->json('match_candidates')->nullable()->after('match_status');

            $table->index('match_status');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['match_status']);
            $table->dropColumn(['match_status', 'match_candidates']);

            $table->string('documentable_type')->nullable(false)->change();
            $table->unsignedBigInteger('documentable_id')->nullable(false)->change();
        });
    }
};
