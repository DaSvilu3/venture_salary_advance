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
        Schema::table('program_terms', function (Blueprint $table) {
            $table->unsignedTinyInteger('collection_max_attempts')->default(3);
            $table->unsignedTinyInteger('collection_retry_days')->default(2);
            $table->unsignedTinyInteger('collection_offset_days')->default(0);
            $table->unsignedSmallInteger('disbursement_sla_hours')->default(24);
            $table->boolean('auto_approve')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_terms', function (Blueprint $table) {
            $table->dropColumn([
                'collection_max_attempts',
                'collection_retry_days',
                'collection_offset_days',
                'disbursement_sla_hours',
                'auto_approve',
            ]);
        });
    }
};
