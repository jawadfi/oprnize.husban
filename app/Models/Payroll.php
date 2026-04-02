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

    /**
     * Ensure overtime amount is always consistent with overtime hours and salary basis.
     *
     * This guards against stale records where overtime_hours was updated but
     * overtime_amount remained zero.
     */
    public function getOvertimeAmountAttribute($value): float
    {
        $hours = (float) ($this->attributes['overtime_hours'] ?? 0);
        if ($hours <= 0) {
            return 0.0;
        }

        $totalSalary = (float) (
            ($this->attributes['basic_salary'] ?? 0) +
            ($this->attributes['housing_allowance'] ?? 0) +
            ($this->attributes['transportation_allowance'] ?? 0) +
            ($this->attributes['food_allowance'] ?? 0) +
            ($this->attributes['other_allowance'] ?? 0)
        );
        $basicSalary = (float) ($this->attributes['basic_salary'] ?? 0);

        if ($totalSalary > 0 || $basicSalary > 0) {
            $computed = ($totalSalary / 240 * $hours) + ($basicSalary / 480 * $hours);
            return round($computed, 0);
        }

        // Fallback to persisted value if no salary basis is available.
        return round((float) ($value ?? 0), 0);
    }

    /**
     * Compute the number of payable work days for this payroll record.
     * Derived from hire date / assignment start date only.
     *
     * Absence must not reduce payable work days. Absence is handled separately
     * via absence deductions, not via effective work-day proration.
     */
    public function getEffectiveWorkDaysAttribute(): int
    {
        if (empty($this->payroll_month)) {
            $daysInMonth = (int) now()->daysInMonth;
            return $daysInMonth;
        }

        $monthStart = Carbon::createFromFormat('Y-m-d', $this->payroll_month . '-01')->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $today = Carbon::today();

        // Future payroll month should not accrue any payable days yet.
        if ($monthStart->greaterThan($today)) {
            return 0;
        }

        $serviceStartDate = $monthStart->copy();

        // Check approved OR ended assignment (ended assignments still need proration)
        $assignment = EmployeeAssigned::query()
            ->where('employee_id', $this->employee_id)
            ->where('company_id', $this->company_id)
            ->whereIn('status', [EmployeeAssignedStatus::APPROVED, EmployeeAssignedStatus::ENDED])
            ->orderByDesc('start_date')
            ->first();

        if ($assignment) {
            $start = Carbon::parse($assignment->start_date);
            if ($start->greaterThan($monthEnd)) {
                return 0;
            }

            $serviceStartDate = $start->greaterThan($monthStart)
                ? $start->copy()->startOfDay()
                : $monthStart->copy();
        } elseif ($this->employee && $this->employee->hire_date) {
            $hireDate = Carbon::parse($this->employee->hire_date);
            if ($hireDate->greaterThan($monthEnd)) {
                return 0;
            }

            $serviceStartDate = $hireDate->greaterThan($monthStart)
                ? $hireDate->copy()->startOfDay()
                : $monthStart->copy();
        }

        // Current month is in-progress: cap payable days at today.
        $serviceEndDate = ($monthStart->year === $today->year && $monthStart->month === $today->month)
            ? $today->copy()->endOfDay()
            : $monthEnd->copy();

        // Ended assignment: cap payable days at assignment end date.
        if ($assignment && $assignment->end_date) {
            $end = Carbon::parse($assignment->end_date);
            if ($end->lessThan($monthStart)) {
                return 0; // assignment already ended before this month
            }

            if ($end->lessThan($serviceEndDate)) {
                $serviceEndDate = $end->copy()->endOfDay();
            }
        }

        if ($serviceEndDate->lessThan($serviceStartDate)) {
            return 0;
        }

        return $serviceStartDate->diffInDays($serviceEndDate) + 1;
    }

    public function getEffectiveTotalPackageAttribute(): float
    {
        // Use computed total_salary (always in sync) instead of stored total_package
        $totalSalary = (float) $this->total_salary;
        if ($totalSalary <= 0 || empty($this->payroll_month)) {
            return round($totalSalary, 0);
        }

        $effectiveDays = $this->effective_work_days;
        if ($effectiveDays <= 0) {
            return 0.0;
        }

        $daysInMonth = (int) Carbon::createFromFormat('Y-m-d', $this->payroll_month . '-01')->daysInMonth;

        // Excel: =ROUND(X/AF*AG, 0) where X=total_salary, AF=monthly_days, AG=work_days
        return round(($totalSalary / $daysInMonth) * $effectiveDays, 0);
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
     * Net Salary = Effective Salary Package + Additions - Deductions
     *
     * Use effective_total_package so Net Payment stays consistent with
     * Total Earning and hire-date proration rules.
     */
    public function getNetSalaryAttribute(): float
    {
        return (float) ($this->effective_total_package + $this->total_additions - $this->total_deductions);
    }

    /**
     * Effective monthly fees prorated by hire-date work days.
     * Excel: =ROUND(T/AF*AG, 0) where T=fees, AF=monthly_days, AG=work_days
     */
    public function getEffectiveFeesAttribute(): float
    {
        $fees = (float) ($this->fees ?? 0);
        if ($fees <= 0 || empty($this->payroll_month)) {
            return round($fees, 0);
        }

        $effectiveDays = $this->effective_work_days;
        if ($effectiveDays <= 0) {
            return 0.0;
        }

        $daysInMonth = (int) Carbon::createFromFormat('Y-m-d', $this->payroll_month . '-01')->daysInMonth;

        return round(($fees / $daysInMonth) * $effectiveDays, 0);
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
     *   A (Absent): deduct from salary only (no fees) = absent_days * (total_salary / daysInMonth)
     *   L/O/X (Leave/Off/Excluded): deduct from salary + fees = days * ((total_salary + fees) / daysInMonth)
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
        // Collect all company IDs that may have entered OT/additions for this employee:
        // the payroll's own company + any client companies that have this employee assigned.
        $relatedCompanyIds = array_unique(array_merge(
            [$companyId],
            EmployeeAssigned::where('employee_id', $employeeId)
                ->where('status', EmployeeAssignedStatus::APPROVED)
                ->pluck('company_id')
                ->toArray()
        ));

        // 1. Overtime → overtime_hours & overtime_amount
        //    Formula: (total_salary / 240 * hours) + (basic_salary / 480 * hours)
        $overtimes = EmployeeOvertime::where('employee_id', $employeeId)
            ->whereIn('company_id', $relatedCompanyIds)
            ->where('payroll_month', $payrollMonth)
            ->where('status', 'approved')
            ->get();

        $totalOvertimeHours = $overtimes->sum('hours');
        $payroll->overtime_hours = $totalOvertimeHours;

        if ($totalOvertimeHours > 0 && ($totalSalary > 0 || $basicSalary > 0)) {
            // Excel: =ROUND(X/240*AM + M/480*AM, 0)
            $overtimeAmount = ($totalSalary / 240 * $totalOvertimeHours)
                            + ($basicSalary / 480 * $totalOvertimeHours);
            $payroll->overtime_amount = round($overtimeAmount, 0);
        } else {
            $payroll->overtime_amount = 0;
        }

        // 2. Additions → other_additions
        // 2. Additions → other_additions
        $additions = EmployeeAddition::where('employee_id', $employeeId)
            ->whereIn('company_id', $relatedCompanyIds)
            ->where('payroll_month', $payrollMonth)
            ->get();

        $payroll->other_additions = $additions->sum('amount');

        // 3. Timesheet → work_days, absence_days, deductions (salary-only vs salary+fees)
        $parts = explode('-', $payrollMonth);
        $year = (int) $parts[0];
        $month = (int) $parts[1];
        $daysInMonth = (int) Carbon::create($year, $month)->daysInMonth;

        $timesheet = EmployeeTimesheet::where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($timesheet) {
            // If the payroll month is the CURRENT month (not yet finished),
            // only count days up to today — future days must NOT be treated as absence.
            $today = Carbon::today();
            $isCurrentMonth = ($today->year === $year && $today->month === $month);

            if ($isCurrentMonth) {
                // Re-count from raw attendance_data, ignoring days after today.
                $attendanceData = $timesheet->attendance_data ?? [];
                $absentDays    = 0;
                $leaveDays     = 0;
                $offDays       = 0;
                $excludedDays  = 0;

                foreach ($attendanceData as $day => $status) {
                    if ((int) $day > $today->day) {
                        continue; // Skip future days that haven't happened yet
                    }
                    match ($status) {
                        'P' => null,
                        'A' => $absentDays++,
                        'L' => $leaveDays++,
                        'O' => $offDays++,
                        'X' => $excludedDays++,
                        default => null,
                    };
                }

                $payroll->absence_days = $absentDays;
            } else {
                // Month is complete — use pre-computed totals from timesheet.
                $payroll->absence_days = $timesheet->absent_days;

                $absentDays   = (int) $timesheet->absent_days;
                $leaveDays    = (int) ($timesheet->leave_days ?? 0);
                $offDays      = (int) ($timesheet->day_off_count ?? 0);
                $excludedDays = (int) ($timesheet->unpaid_leave_days ?? 0);
            }

            $salaryOnlyDeduction    = 0;
            $salaryAndFeesDeduction = 0;

            // A (Absent): deduct from salary ONLY — absence does NOT affect fees
            if ($absentDays > 0 && $totalSalary > 0) {
                $salaryOnlyDeduction = $absentDays * ($totalSalary / $daysInMonth);
            }

            // L + O + X: deduct from salary + fees
            $feeDeductDays = $leaveDays + $offDays + $excludedDays;
            if ($feeDeductDays > 0 && ($totalSalary + $fees) > 0) {
                $salaryAndFeesDeduction = $feeDeductDays * (($totalSalary + $fees) / $daysInMonth);
            }

            // Excel: =ROUND(X/AF*AD, 0)
            $payroll->absence_unpaid_leave_deduction = round($salaryOnlyDeduction + $salaryAndFeesDeduction, 0);
        } else {
            $payroll->absence_unpaid_leave_deduction = 0;
        }

        // NOTE: Hire-date proration is handled automatically by the effective_total_package
        // and effective_fees accessors (fees/days * effective_work_days).
        // We do NOT add it here to avoid double-deduction.

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
        $payroll->food_subscription_deduction = round($foodSubscriptionDeduction, 0);
        $payroll->other_deduction = round(max(0, $otherDeductions), 0);

        // Keep total_package in sync with salary components
        $payroll->total_package = $totalSalary;

        $payroll->save();
    }
}
