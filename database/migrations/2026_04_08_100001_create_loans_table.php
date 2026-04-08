<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);                 // Total loan amount
            $table->unsignedInteger('months');                 // Repayment period
            $table->decimal('monthly_deduction', 10, 2);      // amount / months
            $table->decimal('remaining_amount', 12, 2);        // Decremented each month
            $table->string('start_month', 7);                  // YYYY-MM – first deduction month
            $table->string('status')->default('active');       // active | completed | paused
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
