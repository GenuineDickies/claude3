<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->decimal('approved_total', 10, 2)->nullable()->after('total');
            $table->string('approval_token', 64)->nullable()->unique()->after('parent_version_id');
            $table->timestamp('approval_token_expires_at')->nullable()->after('approval_token');
            $table->longText('signature_data')->nullable()->after('approval_token_expires_at');
            $table->string('signer_name', 200)->nullable()->after('signature_data');
            $table->timestamp('approved_at')->nullable()->after('signer_name');
            $table->string('approval_ip_address', 45)->nullable()->after('approved_at');
            $table->string('approval_user_agent', 500)->nullable()->after('approval_ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn([
                'approved_total',
                'approval_token',
                'approval_token_expires_at',
                'signature_data',
                'signer_name',
                'approved_at',
                'approval_ip_address',
                'approval_user_agent',
            ]);
        });
    }
};
