<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeTimesheet extends Model
{
    protected $fillable = [
        'employee_id',
        'company_id',
        'year',
        'month',
        'attendance_data',
        'work_days',
        'absent_days',
        'day_off_count',
        'leave_days',
        'annual_leave_days',
        'unpaid_leave_days',
        'sick_leave_days',
        'failed_to_report_days',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attendance_data' => 'array',
            'year' => 'integer',
            'month' => 'integer',
            'work_days' => 'integer',
            'absent_days' => 'integer',
            'day_off_count' => 'integer',
            'leave_days' => 'integer',
            'annual_leave_days' => 'integer',
            'unpaid_leave_days' => 'integer',
            'sick_leave_days' => 'integer',
            'failed_to_report_days' => 'integer',
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

    /**
     * Recalculate summary totals from attendance_data
     */
    public function recalculateTotals(): void
    {
        $data = $this->attendance_data ?? [];
        
        $counts = [
            'work_days' => 0,
            'absent_days' => 0,
            'day_off_count' => 0,
            'leave_days' => 0,
            'annual_leave_days' => 0,
            'unpaid_leave_days' => 0,
            'sick_leave_days' => 0,
            'failed_to_report_days' => 0,
        ];

        foreach ($data as $day => $status) {
            match($status) {
                'P' => $counts['work_days']++,
                'A' => $counts['absent_days']++,
                'DO' => $counts['day_off_count']++,
                'L' => $counts['leave_days']++,
                'AL' => $counts['annual_leave_days']++,
                'UL' => $counts['unpaid_leave_days']++,
                'SL' => $counts['sick_leave_days']++,
                'FR' => $counts['failed_to_report_days']++,
                default => null,
            };
        }

        $this->fill($counts);
    }

    /**
     * Get the payroll_month format (YYYY-MM)
     */
    public function getPayrollMonthAttribute(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }

    /**
     * Get the number of days in this month
     */
    public function getDaysInMonthAttribute(): int
    {
        return cal_days_in_month(CAL_GREGORIAN, $this->month, $this->year);
    }
}
