<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('leads')->where('stage', 'contacted')->update(['stage' => 'intake_verified']);
        DB::table('leads')->where('stage', 'qualified')->update(['stage' => 'dispatch_ready']);
        DB::table('leads')->where('stage', 'proposal_sent')->update(['stage' => 'waiting_customer']);
        DB::table('leads')->where('stage', 'won')->update(['stage' => 'converted']);
        DB::table('leads')->where('stage', 'lost')->update(['stage' => 'closed_no_service']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('leads')->where('stage', 'intake_verified')->update(['stage' => 'contacted']);
        DB::table('leads')->where('stage', 'dispatch_ready')->update(['stage' => 'qualified']);
        DB::table('leads')->where('stage', 'waiting_customer')->update(['stage' => 'proposal_sent']);
        DB::table('leads')->where('stage', 'converted')->update(['stage' => 'won']);
        DB::table('leads')->where('stage', 'closed_no_service')->update(['stage' => 'lost']);
    }
};
