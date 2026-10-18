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
        Schema::create('employers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cr_number')->unique();
            $table->string('status')->default('pending')->index();
            $table->unsignedTinyInteger('payday_day')->default(25);
            $table->foreignId('bank_partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->foreignId('funder_partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->string('subscription_plan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employers');
    }
};
