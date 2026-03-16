<?php

namespace App\Models;

use App\Enums\DeductionReason;
use App\Enums\EmployeeAssignedStatus;
use App\Enums\PayrollStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payroll extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'basic_salary', 'net_payment', 'total_salary', 'payroll_month'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "مسير الرواتب تم {$eventName}")
            ->useLogName('payroll');
    }
    protected $fillable = [
        'employee_id',
        'company_id',
        'payroll_month',
        'status',
        'basic_salary',
        'housing_allowance',
        'transportation_allowance',
        'food_allowance',
        'other_allowance',
        'fees',
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
        'notes',
        'reback_reason',
        'provider_review_status',
        'provider_reviewed_at',
        'provider_rejection_reason',
        'tax_invoice_number',
        'tax_invoice_issued_at',
        'tax_invoice_amount',
        'is_modified',
        'submitted_at',
        'calculated_at',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'housing_allowance' => 'decimal:2',
            'transportation_allowance' => 'decimal:2',
            'food_allowance' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'fees' => 'decimal:2',
            'total_package' => 'decimal:2',
            'work_days' => 'integer',
            'added_days' => 'integer',
            'overtime_hours' => 'decimal:2',
            'overtime_amount' => 'decimal:2',
            'added_days_amount' => 'decimal:2',
            'other_additions' => 'decimal:2',
            'absence_days' => 'integer',
            'absence_unpaid_leave_deduction' => 'decimal:2',
            'food_subscription_deduction' => 'decimal:2',
            'other_deduction' => 'decimal:2',
            'tax_invoice_amount' => 'decimal:2',
            'is_modified' => 'boolean',
            'provider_reviewed_at' => 'datetime',
            'tax_invoice_issued_at' => 'datetime',
            'submitted_at' => 'datetime',
            'calculated_at' => 'datetime',
            'finalized_at' => 'datetime',
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

    public function deductions(): HasMany
    {
        return $this->hasMany(Deduction::class);
    }

    /**
     * Get total from linked deductions records
     */
    public function getLinkedDeductionsTotalAttribute(): float
    {
        return (float) $this->deductions()->where('status', 'approved')->sum('amount');
    }

    /**
     * Get status label in Arabic
     */
    public function getStatusLabelAttribute(): string
    {
        return PayrollStatus::getTranslatedEnum()[$this->status] ?? $this->status;
    }

    /**
     * Get status color for badges
     */
    public function getStatusColorAttribute(): string
    {
        return PayrollStatus::getColors()[$this->status] ?? 'gray';
    }

    // Calculated accessors
    public function getTotalOtherAllowAttribute(): float
    {
        return (float) (
            $this->housing_allowance +
            $this->transportation_allowance +
            $this->food_allowance +
            $this->other_allowance
        );
    }

    public function getTotalSalaryAttribute(): float
    {
        return (float) ($this->basic_salary + $this->total_other_allow);
    }

    public function getMonthlyCostAttribute(): float
    {
        return (float) ($this->total_salary + $this->effective_fees);
    }

    public function getTotalAdditionsAttribute(): float
    {
        return (float) (
            ($this->overtime_amount ?? 0) +
            ($this->added_days_amount ?? 0) +
            ($this->other_additions ?? 0)
        );
    }

    public function getEffectiveTotalPackageAttribute(): float
    {
        $totalPackage = (float) ($this->total_package ?? 0);
        if ($totalPackage <= 0) {
            return 0.0;
        }

        if (empty($this->payroll_month)) {
            return round($totalPackage, 2);
        }

        $monthStart = Carbon::createFromFormat('Y-m-d', $this->payroll_month . '-01')->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $daysInMonth = (int) $monthStart->daysInMonth;

        $serviceStartDay = 1;

        $assignmentStart = EmployeeAssigned::query()
            ->where('employee_id', $this->employee_id)
            ->where('company_id', $this->company_id)
            ->where('status', EmployeeAssignedStatus::APPROVED)
            ->orderByDesc('start_date')
            ->value('start_date');

        if ($assignmentStart) {
            $start = Carbon::parse($assignmentStart);

            if ($start->greaterThan($monthEnd)) {
                return 0.0;
            }

            if ($start->year === $monthStart->year && $start->month === $monthStart->month) {
                $serviceStartDay = (int) $start->day;
            }
        } elseif ($this->employee && $this->employee->hire_date) {
            $hireDate = Carbon::parse($this->employee->hire_date);

            if ($hireDate->greaterThan($monthEnd)) {
                return 0.0;
            }

            if ($hireDate->year === $monthStart->year && $hireDate->month === $monthStart->month) {
                $serviceStartDay = (int) $hireDate->day;
            }
        }

        // Inclusive calculation: start on day 3 in 31-day month => 29 payable days.
        $payableDays = $serviceStartDay <= 1
            ? $daysInMonth
            : max(0, ($daysInMonth - $serviceStartDay) + 1);

        return round(($totalPackage / $daysInMonth) * $payableDays, 2);
    }

    public function getTotalEarningAttribute(): float
    {
        return (float) (
            $this->effective_total_package +
            $this->effective_fees +
            $this->total_additions
        );
    }

    public function getTotalDeductionsAttribute(): float
    {
        return (float) (
            ($this->absence_unpaid_leave_deduction ?? 0) +
            ($this->food_subscription_deduction ?? 0) +
            ($this->other_deduction ?? 0)
        );
    }

    public function getNetPaymentAttribute(): float
    {
        return (float) ($this->net_salary + $this->effective_fees);
    }

    /**
     * Net Salary = Total Salary + Additions - Deductions
     */
    public function getNetSalaryAttribute(): float
    {
        return (float) ($this->total_salary + $this->total_additions - $this->total_deductions);
    }

    /**
     * Effective monthly fees after start-day proration and absence deduction.
     * Rules:
     * - If employee starts on day 10, fees apply from day 10 to month end.
     * - Absent days are excluded from fee charge.
     */
    public function getEffectiveFeesAttribute(): float
    {
        $fees = (float) ($this->fees ?? 0);
        if ($fees <= 0) {
            return 0.0;
        }

        if (empty($this->payroll_month)) {
            return round($fees, 2);
        }

        $monthStart = Carbon::createFromFormat('Y-m-d', $this->payroll_month . '-01')->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $daysInMonth = (int) $monthStart->daysInMonth;

        $serviceStartDay = 1;

        $assignmentStart = EmployeeAssigned::query()
            ->where('employee_id', $this->employee_id)
            ->where('company_id', $this->company_id)
            ->where('status', EmployeeAssignedStatus::APPROVED)
            ->orderByDesc('start_date')
            ->value('start_date');

        if ($assignmentStart) {
            $start = Carbon::parse($assignmentStart);

            if ($start->greaterThan($monthEnd)) {
                return 0.0;
            }

            if ($start->year === $monthStart->year && $start->month === $monthStart->month) {
                $serviceStartDay = (int) $start->day;
            }
        } elseif ($this->employee && $this->employee->hire_date) {
            $hireDate = Carbon::parse($this->employee->hire_date);

            if ($hireDate->greaterThan($monthEnd)) {
                return 0.0;
            }

            if ($hireDate->year === $monthStart->year && $hireDate->month === $monthStart->month) {
                $serviceStartDay = (int) $hireDate->day;
            }
        }

        // If work_days is set, use it directly for fee proration (fees/30*work_days)
        if (!is_null($this->work_days) && $this->work_days > 0) {
            return round(($fees / 30) * $this->work_days, 2);
        }

        // Otherwise, fallback to old proration logic
        $eligibleDays = $serviceStartDay <= 1
            ? $daysInMonth
            : max(0, ($daysInMonth - $serviceStartDay) + 1);
        $absenceDays = max(0, (int) ($this->absence_days ?? 0));
        $payableDays = max(0, $eligibleDays - $absenceDays);

        return round(($fees / $daysInMonth) * $payableDays, 2);
    }

    /**
     * Total Without OT = Net Payment - Overtime Amount
     * فرق بين العمل الاضافي (overtime) والمبلغ الاضافي (other_additions)
     */
    public function getTotalWithoutOtAttribute(): float
    {
        return (float) ($this->net_payment - ($this->overtime_amount ?? 0));
    }

    /**
     * Sync payroll fields from EmployeeEntries data (overtime, additions, timesheet, deductions).
     * Finds or creates the Payroll record, then aggregates entry data into it.
     *
     * Overtime formula: (total_salary / 240 * hours) + (basic_salary / 480 * hours)
     * Deduction rules:
     *   A (Absent): deduct from salary only (no fees) = absent_days * (total_salary / 30)
     *   L/O/X (Leave/Off/Excluded): deduct from salary + fees = days * ((total_salary + fees) / 30)
     */
    public static function syncFromEntries(int $employeeId, int $companyId, string $payrollMonth): void
    {
        // Find or create payroll for this employee + company + month
        $payroll = static::firstOrCreate(
            [
                'employee_id' => $employeeId,
                'company_id' => $companyId,
                'payroll_month' => $payrollMonth,
            ],
            [
                'basic_salary' => 0,
                'status' => 'draft',
            ]
        );

        // Pre-calculate salary totals used in formulas
        // If current month payroll has empty salary basis, fallback to template/latest payroll basis.
        $hasSalaryBasis = (
            (float) ($payroll->basic_salary ?? 0) > 0 ||
            (float) ($payroll->housing_allowance ?? 0) > 0 ||
            (float) ($payroll->transportation_allowance ?? 0) > 0 ||
            (float) ($payroll->food_allowance ?? 0) > 0 ||
            (float) ($payroll->other_allowance ?? 0) > 0 ||
            (float) ($payroll->fees ?? 0) > 0
        );

        if (!$hasSalaryBasis) {
            $fallbackPayroll = static::query()
                ->where('employee_id', $employeeId)
                ->where('company_id', $companyId)
                ->where(function ($query) use ($payrollMonth) {
                    $query->whereNull('payroll_month')
                        ->orWhere('payroll_month', '!=', $payrollMonth);
                })
                ->where(function ($query) {
                    $query->where('basic_salary', '>', 0)
                        ->orWhere('housing_allowance', '>', 0)
                        ->orWhere('transportation_allowance', '>', 0)
                        ->orWhere('food_allowance', '>', 0)
                        ->orWhere('other_allowance', '>', 0)
                        ->orWhere('fees', '>', 0);
                })
                ->orderByRaw('CASE WHEN payroll_month IS NULL THEN 0 ELSE 1 END')
                ->orderByDesc('payroll_month')
                ->first();

            if ($fallbackPayroll) {
                $payroll->basic_salary = (float) ($fallbackPayroll->basic_salary ?? 0);
                $payroll->housing_allowance = (float) ($fallbackPayroll->housing_allowance ?? 0);
                $payroll->transportation_allowance = (float) ($fallbackPayroll->transportation_allowance ?? 0);
                $payroll->food_allowance = (float) ($fallbackPayroll->food_allowance ?? 0);
                $payroll->other_allowance = (float) ($fallbackPayroll->other_allowance ?? 0);
                $payroll->fees = (float) ($fallbackPayroll->fees ?? 0);
            }
        }

        $totalSalary = (float) $payroll->basic_salary
            + (float) $payroll->housing_allowance
            + (float) $payroll->transportation_allowance
            + (float) $payroll->food_allowance
            + (float) $payroll->other_allowance;
        $basicSalary = (float) $payroll->basic_salary;
        $fees = (float) $payroll->fees;

        // 1. Overtime → overtime_hours & overtime_amount
        //    Formula: (total_salary / 240 * hours) + (basic_salary / 480 * hours)
        $overtimes = EmployeeOvertime::where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->where('payroll_month', $payrollMonth)
            ->get();

        $totalOvertimeHours = $overtimes->sum('hours');
        $payroll->overtime_hours = $totalOvertimeHours;

        if ($totalOvertimeHours > 0 && ($totalSalary > 0 || $basicSalary > 0)) {
            $overtimeAmount = ($totalSalary / 240 * $totalOvertimeHours)
                            + ($basicSalary / 480 * $totalOvertimeHours);
            $payroll->overtime_amount = round($overtimeAmount, 2);
        } else {
            $payroll->overtime_amount = 0;
        }

        // 2. Additions → other_additions
        $additions = EmployeeAddition::where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->where('payroll_month', $payrollMonth)
            ->get();

        $payroll->other_additions = $additions->sum('amount');

        // 3. Timesheet → work_days, absence_days, deductions (salary-only vs salary+fees)
        $parts = explode('-', $payrollMonth);
        $year = (int) $parts[0];
        $month = (int) $parts[1];

        $timesheet = EmployeeTimesheet::where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($timesheet) {
            $payroll->work_days = $timesheet->work_days;
            $payroll->absence_days = $timesheet->absent_days;

            $absentDays = (int) $timesheet->absent_days;          // A status
            $leaveDays  = (int) ($timesheet->leave_days ?? 0);    // L status
            $offDays    = (int) ($timesheet->day_off_count ?? 0); // O status
            $excludedDays = (int) ($timesheet->unpaid_leave_days ?? 0); // X status

            $salaryOnlyDeduction = 0;
            $salaryAndFeesDeduction = 0;

            // A (Absent): deduct from salary only — no fees
            if ($absentDays > 0 && $totalSalary > 0) {
                $salaryOnlyDeduction = $absentDays * ($totalSalary / 30);
            }

            // L + O + X: deduct from salary + fees
            $feeDeductDays = $leaveDays + $offDays + $excludedDays;
            if ($feeDeductDays > 0 && ($totalSalary + $fees) > 0) {
                $salaryAndFeesDeduction = $feeDeductDays * (($totalSalary + $fees) / 30);
            }

            $payroll->absence_unpaid_leave_deduction = round($salaryOnlyDeduction + $salaryAndFeesDeduction, 2);
        } else {
            $payroll->absence_unpaid_leave_deduction = 0;
        }

        // Proration for new employees: if hired in the middle of the payroll month,
        // deduct for the days before the hire date (from first day of month to hire day - 1).
        // Formula: pre_hire_days * ((totalSalary + fees) / daysInMonth)
        $employee = Employee::find($employeeId);
        if ($employee && $employee->hire_date) {
            $hireDate = Carbon::parse($employee->hire_date);
            $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

            if ($hireDate->year === $year && $hireDate->month === $month && $hireDate->day > 1) {
                $preHireDays = $hireDate->day - 1;
                $dailyRate = ($totalSalary + $fees) / $daysInMonth;
                $hireProratedDeduction = round($preHireDays * $dailyRate, 2);
                $payroll->absence_unpaid_leave_deduction = round(
                    ($payroll->absence_unpaid_leave_deduction ?? 0) + $hireProratedDeduction,
                    2
                );
            }
        }

        // Note: For terminated employees (ended service before month end), the proration
        // (first day of month to last working day) requires a service_end_date field which
        // can be set directly via the payroll's work_days and timesheet data.

        // 4. Deductions → other_deduction
        $deductions = Deduction::where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->where('payroll_month', $payrollMonth)
            ->where('status', 'approved')
            ->get();

        $absenceFromDeductions = (float) $deductions
            ->where('reason', DeductionReason::ABSENCE)
            ->sum('amount');

        $foodSubscriptionDeduction = (float) $deductions
            ->where('reason', DeductionReason::FOOD_SUBSCRIPTION)
            ->sum('amount');

        $otherDeductions = (float) $deductions->sum('amount')
            - $absenceFromDeductions
            - $foodSubscriptionDeduction;

        // Keep absence deduction from timesheet logic, only map food/other from deduction entries.
        $payroll->food_subscription_deduction = round($foodSubscriptionDeduction, 2);
        $payroll->other_deduction = round(max(0, $otherDeductions), 2);

        $payroll->save();
    }
}
