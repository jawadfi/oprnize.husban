<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deductions', function (Blueprint $table) {
            $table->foreignId('loan_id')
                ->nullable()
                ->after('payroll_id')
                ->constrained('loans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deductions', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Loan::class);
            $table->dropColumn('loan_id');
        });
    }
};
