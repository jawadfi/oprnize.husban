<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->string('payroll_month', 7)->nullable()->after('company_id'); // e.g. 2026-02
            $table->string('status')->default('draft')->after('payroll_month');
            // draft → submitted_to_provider → calculated → submitted_to_client → reback → finalized
            $table->text('notes')->nullable()->after('other_deduction');
            $table->text('reback_reason')->nullable()->after('notes');
            $table->boolean('is_modified')->default(false)->after('reback_reason');
            $table->timestamp('submitted_at')->nullable()->after('is_modified');
            $table->timestamp('calculated_at')->nullable()->after('submitted_at');
            $table->timestamp('finalized_at')->nullable()->after('calculated_at');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'payroll_month', 'status', 'notes', 'reback_reason',
                'is_modified', 'submitted_at', 'calculated_at', 'finalized_at',
            ]);
        });
    }
};
