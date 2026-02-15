<?php

namespace App\Models;

use App\Enums\BranchEntryStatus;
use App\Enums\BranchEntryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BranchEntry extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "إدخال فرع تم {$eventName}")
            ->useLogName('branch_entry');
    }
    protected $fillable = [
        'branch_id',
        'employee_id',
        'payroll_month',
        'entry_type',
        
        // Attendance
        'check_in',
        'check_out',
        'attendance_date',
        
        // Deduction
        'deduction_reason',
        'deduction_description',
        'deduction_days',
        'deduction_amount',
        'deduction_daily_rate',
        
        // Absence
        'absence_days',
        'absence_from',
        'absence_to',
        'absence_type',
        
        // Overtime
        'overtime_hours',
        'overtime_amount',
        
        // Addition
        'addition_amount',
        'addition_reason',
        
        // General
        'notes',
        'status',
        'submitted_by',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'datetime:H:i',
            'check_out' => 'datetime:H:i',
            'attendance_date' => 'date',
            'deduction_days' => 'integer',
            'deduction_amount' => 'decimal:2',
            'deduction_daily_rate' => 'decimal:2',
            'absence_days' => 'integer',
            'absence_from' => 'date',
            'absence_to' => 'date',
            'overtime_hours' => 'decimal:2',
            'overtime_amount' => 'decimal:2',
            'addition_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'entry_type' => BranchEntryType::class,
            'status' => BranchEntryStatus::class,
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function scopeForMonth($query, string $month)
    {
        return $query->where('payroll_month', $month);
    }

    public function scopeOfType($query, BranchEntryType $type)
    {
        return $query->where('entry_type', $type);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', BranchEntryStatus::SUBMITTED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', BranchEntryStatus::DRAFT);
    }
}
