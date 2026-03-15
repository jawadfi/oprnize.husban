<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->string('provider_review_status', 20)->nullable()->after('reback_reason');
            $table->timestamp('provider_reviewed_at')->nullable()->after('provider_review_status');
            $table->text('provider_rejection_reason')->nullable()->after('provider_reviewed_at');
            $table->string('tax_invoice_number', 50)->nullable()->after('provider_rejection_reason');
            $table->timestamp('tax_invoice_issued_at')->nullable()->after('tax_invoice_number');
            $table->decimal('tax_invoice_amount', 12, 2)->nullable()->after('tax_invoice_issued_at');

            $table->index(['company_id', 'payroll_month', 'provider_review_status'], 'payroll_provider_review_idx');
            $table->index(['tax_invoice_number'], 'payroll_tax_invoice_number_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropIndex('payroll_provider_review_idx');
            $table->dropIndex('payroll_tax_invoice_number_idx');

            $table->dropColumn([
                'provider_review_status',
                'provider_reviewed_at',
                'provider_rejection_reason',
                'tax_invoice_number',
                'tax_invoice_issued_at',
                'tax_invoice_amount',
            ]);
        });
    }
};
