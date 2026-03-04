<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('scope', 20)->default('general')->after('code');

            // Replace the single-column unique on code with a composite unique
            $table->dropUnique(['code']);
            $table->unique(['code', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropUnique(['code', 'scope']);
            $table->unique('code');
            $table->dropColumn('scope');
        });
    }
};
