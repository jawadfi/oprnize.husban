<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payroll_month', 7); // e.g. 2026-02
            $table->string('type'); // days, fixed
            $table->string('reason'); // absence, late, penalty, food_subscription, other
            $table->string('description')->nullable();
            $table->integer('days')->nullable(); // number of days (if type = days)
            $table->decimal('amount', 10, 2)->default(0); // calculated or fixed amount
            $table->decimal('daily_rate', 10, 2)->nullable(); // for auto-calculation
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('created_by_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deductions');
    }
};
