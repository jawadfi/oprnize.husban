<?php

namespace App\Models;

use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'company_id',
        'current_approver_company_id',
        'leave_type',
        'start_date',
        'end_date',
        'days_count',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'days_count' => 'integer',
            'leave_type' => LeaveType::class,
            'status' => LeaveRequestStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function currentApproverCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'current_approver_company_id');
    }

    public function isPendingSupervisorApproval(): bool
    {
        return $this->status->value === LeaveRequestStatus::PENDING_SUPERVISOR_APPROVAL;
    }

    public function isPendingClientApproval(): bool
    {
        return $this->status->value === LeaveRequestStatus::PENDING_CLIENT_APPROVAL;
    }

    public function isPendingProviderApproval(): bool
    {
        return $this->status->value === LeaveRequestStatus::PENDING_PROVIDER_APPROVAL;
    }

    public function moveToClientApproval(): void
    {
        $clientCompanyId = $this->employee->company_assigned_id ?? $this->company_id;
        
        $this->update([
            'status' => LeaveRequestStatus::PENDING_CLIENT_APPROVAL,
            'current_approver_company_id' => $clientCompanyId,
        ]);
    }

    public function moveToProviderApproval(): void
    {
        $providerCompanyId = $this->employee->company_id;
        
        $this->update([
            'status' => LeaveRequestStatus::PENDING_PROVIDER_APPROVAL,
            'company_id' => $providerCompanyId,
            'current_approver_company_id' => $providerCompanyId,
        ]);
    }

    public function finalizeApproval(): void
    {
        $this->update([
            'status' => LeaveRequestStatus::APPROVED,
            'current_approver_company_id' => null,
        ]);

        // Deduct vacation balance for annual leave
        if ($this->leave_type->value === \App\Enums\LeaveType::ANNUAL) {
            $employee = $this->employee;
            $employee->decrement('vacation_balance', $this->days_count);
        }

        // Reflect approved leave in the client timesheet and payroll automatically.
        $this->applyApprovedLeaveToClientTimesheet();
    }

    public function applyApprovedLeaveToClientTimesheet(): void
    {
        $employee = $this->employee;
        if (! $employee || ! $this->start_date || ! $this->end_date) {
            return;
        }

        $clientCompanyId = $employee->company_assigned_id;
        if (! $clientCompanyId) {
            return;
        }

        $statusCode = $this->leave_type->value === LeaveType::UNPAID ? 'X' : 'L';

        $from = Carbon::parse($this->start_date)->startOfDay();
        $to = Carbon::parse($this->end_date)->endOfDay();
        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        $affectedMonths = [];
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            $year = $cursor->year;
            $month = $cursor->month;
            $day = (string) $cursor->day;
            $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

            $timesheet = EmployeeTimesheet::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'company_id' => $clientCompanyId,
                    'year' => $year,
                    'month' => $month,
                ],
                [
                    'attendance_data' => array_fill_keys(range(1, $daysInMonth), 'P'),
                ]
            );

            $attendanceData = $timesheet->attendance_data ?? [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                if (! array_key_exists($d, $attendanceData) && ! array_key_exists((string) $d, $attendanceData)) {
                    $attendanceData[$d] = 'P';
                }
            }

            $attendanceData[$day] = $statusCode;

            $timesheet->attendance_data = $attendanceData;
            $timesheet->recalculateTotals();
            $timesheet->save();

            $payrollMonth = sprintf('%04d-%02d', $year, $month);
            $affectedMonths[$payrollMonth] = true;

            $cursor->addDay();
        }

        foreach (array_keys($affectedMonths) as $payrollMonth) {
            Payroll::syncFromEntries($employee->id, $clientCompanyId, $payrollMonth);
        }
    }
}

