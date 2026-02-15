<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_additions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('payroll_month', 7); // e.g. 2026-02
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('reason')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('created_by_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'payroll_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_additions');
    }
};
