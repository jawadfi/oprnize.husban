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
        Schema::table('payrolls', function (Blueprint $table) {
            // Earnings fields
            $table->decimal('total_package', 10, 2)->default(0)->after('fees');
            $table->integer('work_days')->default(0)->after('total_package');
            $table->integer('added_days')->default(0)->after('work_days');
            $table->decimal('overtime_hours', 8, 2)->default(0)->after('added_days');
            $table->decimal('overtime_amount', 10, 2)->default(0)->after('overtime_hours');
            $table->decimal('added_days_amount', 10, 2)->default(0)->after('overtime_amount');
            $table->decimal('other_additions', 10, 2)->default(0)->after('added_days_amount');
            
            // Deductions fields
            $table->integer('absence_days')->default(0)->after('other_additions');
            $table->decimal('absence_unpaid_leave_deduction', 10, 2)->default(0)->after('absence_days');
            $table->decimal('food_subscription_deduction', 10, 2)->default(0)->after('absence_unpaid_leave_deduction');
            $table->decimal('other_deduction', 10, 2)->default(0)->after('food_subscription_deduction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'total_package',
                'work_days',
                'added_days',
                'overtime_hours',
                'overtime_amount',
                'added_days_amount',
                'other_additions',
                'absence_days',
                'absence_unpaid_leave_deduction',
                'food_subscription_deduction',
                'other_deduction',
            ]);
        });
    }
};
