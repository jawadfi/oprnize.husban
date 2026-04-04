<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('provider_company_id')->nullable()->after('company_id')->constrained('companies')->nullOnDelete();
            $table->foreignId('client_company_id')->nullable()->after('provider_company_id')->constrained('companies')->nullOnDelete();
        });

        $leaveRequests = DB::table('leave_requests')->get();

        foreach ($leaveRequests as $leaveRequest) {
            $employee = DB::table('employees')
                ->where('id', $leaveRequest->employee_id)
                ->first(['company_id', 'company_assigned_id']);

            $providerCompanyId = $employee->company_id ?? $leaveRequest->company_id;
            $clientCompanyId = $this->resolveClientCompanyId($leaveRequest, $employee, $providerCompanyId);

            DB::table('leave_requests')
                ->where('id', $leaveRequest->id)
                ->update([
                    'provider_company_id' => $providerCompanyId,
                    'client_company_id' => $clientCompanyId,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign(['provider_company_id']);
            $table->dropForeign(['client_company_id']);
            $table->dropColumn(['provider_company_id', 'client_company_id']);
        });
    }

    private function resolveClientCompanyId(object $leaveRequest, ?object $employee, $providerCompanyId): ?int
    {
        $assignmentCompanyId = DB::table('employee_assigned')
            ->where('employee_id', $leaveRequest->employee_id)
            ->whereDate('start_date', '<=', $leaveRequest->end_date)
            ->where(function ($query) use ($leaveRequest) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $leaveRequest->start_date);
            })
            ->orderByDesc('start_date')
            ->value('company_id');

        if ($assignmentCompanyId) {
            return (int) $assignmentCompanyId;
        }

        if (! empty($leaveRequest->company_id) && (int) $leaveRequest->company_id !== (int) $providerCompanyId) {
            return (int) $leaveRequest->company_id;
        }

        if (! empty($employee?->company_assigned_id)) {
            return (int) $employee->company_assigned_id;
        }

        return null;
    }
};
