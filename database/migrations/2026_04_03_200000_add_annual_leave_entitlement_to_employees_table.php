<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // رصيد التعاقد السنوي — عدد أيام الإجازة المنصوص عليها في عقد الموظف
            $table->integer('annual_leave_entitlement')->default(21)->after('vacation_balance');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('annual_leave_entitlement');
        });
    }
};
