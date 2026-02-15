<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->integer('year');
            $table->integer('month');
            $table->jsonb('attendance_data')->nullable(); // {"1":"P","2":"A","3":"DO",..."31":"P"}
            $table->integer('work_days')->default(0);
            $table->integer('absent_days')->default(0);
            $table->integer('day_off_count')->default(0);
            $table->integer('leave_days')->default(0);
            $table->integer('annual_leave_days')->default(0);
            $table->integer('unpaid_leave_days')->default(0);
            $table->integer('sick_leave_days')->default(0);
            $table->integer('failed_to_report_days')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'company_id', 'year', 'month']);
            $table->index(['company_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_timesheets');
    }
};
