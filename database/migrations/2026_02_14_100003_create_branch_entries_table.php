<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('payroll_month'); // Format: Y-m
            $table->string('entry_type'); // attendance, deduction, absence, overtime, addition
            
            // Attendance fields
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->date('attendance_date')->nullable();
            
            // Deduction fields
            $table->string('deduction_reason')->nullable();
            $table->text('deduction_description')->nullable();
            $table->integer('deduction_days')->nullable();
            $table->decimal('deduction_amount', 10, 2)->nullable();
            $table->decimal('deduction_daily_rate', 10, 2)->nullable();
            
            // Absence fields
            $table->integer('absence_days')->nullable();
            $table->date('absence_from')->nullable();
            $table->date('absence_to')->nullable();
            $table->string('absence_type')->nullable(); // paid, unpaid
            
            // Overtime fields
            $table->decimal('overtime_hours', 8, 2)->nullable();
            $table->decimal('overtime_amount', 10, 2)->nullable();
            
            // Addition fields  
            $table->decimal('addition_amount', 10, 2)->nullable();
            $table->string('addition_reason')->nullable();
            
            // General
            $table->text('notes')->nullable();
            $table->string('status')->default('draft'); // draft, submitted, approved, rejected
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable(); // Could be Company or User
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_entries');
    }
};
