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
        Schema::create('advances', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pay_cycle_id')->constrained();
            $table->foreignId('mandate_id')->constrained();
            $table->foreignId('bank_partner_id')->constrained('partners');
            $table->foreignId('funder_partner_id')->constrained('partners');
            $table->foreignId('disclosure_id')->constrained();
            $table->unsignedBigInteger('amount_baisa');
            $table->unsignedBigInteger('fee_baisa');
            $table->unsignedBigInteger('total_due_baisa');
            $table->date('due_date');
            $table->string('status')->index();
            $table->timestamp('disclosure_accepted_at');
            $table->timestamp('requested_at');
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->unsignedTinyInteger('collection_attempts')->default(0);
            $table->date('next_collection_attempt_on')->nullable()->index();
            $table->string('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advances');
    }
};
