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
        Schema::create('program_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('limit_ratio_bps');
            $table->unsignedBigInteger('max_limit_baisa');
            $table->unsignedBigInteger('min_advance_baisa');
            $table->string('fee_model');
            $table->unsignedBigInteger('flat_fee_baisa');
            $table->unsignedTinyInteger('min_tenure_months');
            $table->unsignedTinyInteger('cutoff_days_before_payday');
            $table->unsignedTinyInteger('max_draws_per_cycle');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_terms');
    }
};
