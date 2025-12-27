<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id',
        'company_id',
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
}
