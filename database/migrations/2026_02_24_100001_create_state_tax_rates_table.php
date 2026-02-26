<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('state_tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('state_code', 2)->unique();
            $table->string('state_name');
            $table->decimal('tax_rate', 6, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('state_tax_rates');
    }
};
