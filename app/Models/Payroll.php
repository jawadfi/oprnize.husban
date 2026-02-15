<?php

namespace App\Models;

use App\Enums\PayrollStatus;
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
            'is_modified' => 'boolean',
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
        return (float) ($this->total_salary + $this->fees);
    }

    public function getTotalAdditionsAttribute(): float
    {
        return (float) (
            ($this->overtime_amount ?? 0) +
            ($this->added_days_amount ?? 0) +
            ($this->other_additions ?? 0)
        );
    }

    public function getTotalEarningAttribute(): float
    {
        return (float) (
            ($this->total_package ?? 0) +
            ($this->fees ?? 0) +
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
        return (float) ($this->total_earning - $this->total_deductions);
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

        // 1. Overtime → overtime_hours & overtime_amount
        $overtimes = EmployeeOvertime::where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->where('payroll_month', $payrollMonth)
            ->get();

        $payroll->overtime_hours = $overtimes->sum('hours');
        $payroll->overtime_amount = $overtimes->sum('amount');

        // 2. Additions → other_additions
        $additions = EmployeeAddition::where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->where('payroll_month', $payrollMonth)
            ->get();

        $payroll->other_additions = $additions->sum('amount');

        // 3. Timesheet → work_days, absence_days, absence_unpaid_leave_deduction
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

            // Calculate absence deduction: (total salary / days in month) * absent days
            $totalSalary = (float) $payroll->basic_salary
                + (float) $payroll->housing_allowance
                + (float) $payroll->transportation_allowance
                + (float) $payroll->food_allowance
                + (float) $payroll->other_allowance;

            if ($totalSalary > 0 && $timesheet->absent_days > 0) {
                $daysInMonth = \Carbon\Carbon::create($year, $month, 1)->daysInMonth;
                $dailyRate = $totalSalary / $daysInMonth;
                $payroll->absence_unpaid_leave_deduction = round($timesheet->absent_days * $dailyRate, 2);
            } else {
                $payroll->absence_unpaid_leave_deduction = 0;
            }
        }

        // 4. Deductions → other_deduction
        $deductions = Deduction::where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->where('payroll_month', $payrollMonth)
            ->get();

        $payroll->other_deduction = $deductions->sum('amount');

        $payroll->save();
    }
}
