<?php

use App\Enums\EndOfServiceRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('end_of_service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('provider_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('client_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('current_approver_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->unsignedTinyInteger('termination_reason');
            $table->date('last_working_date');
            $table->date('service_start_date')->nullable();
            $table->unsignedInteger('service_days')->default(0);
            $table->decimal('salary_amount', 12, 2)->default(0);
            $table->decimal('estimated_amount', 12, 2)->default(0);
            $table->string('status')->default(EndOfServiceRequestStatus::PENDING_SUPERVISOR_APPROVAL);
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['provider_company_id', 'status']);
            $table->index(['client_company_id', 'status']);
            $table->index('current_approver_company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('end_of_service_requests');
    }
};
