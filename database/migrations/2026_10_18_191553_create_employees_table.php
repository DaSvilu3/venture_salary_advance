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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('staff_no');
            $table->string('civil_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email');
            $table->unsignedBigInteger('net_salary_baisa');
            $table->date('employment_start_date');
            $table->string('employment_status')->default('active');
            $table->timestamp('salary_verified_at')->nullable();
            $table->string('iban')->nullable();
            $table->string('enrolment_status')->default('invited')->index();
            $table->string('invitation_token', 64)->nullable()->unique();
            $table->timestamp('invited_at')->nullable();
            $table->timestamps();

            $table->unique(['employer_id', 'staff_no']);
            $table->unique(['employer_id', 'civil_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
