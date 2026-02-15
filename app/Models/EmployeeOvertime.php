<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeOvertime extends Model
{
    protected $fillable = [
        'employee_id',
        'company_id',
        'payroll_month',
        'hours',
        'rate_per_hour',
        'amount',
        'notes',
        'is_recurring',
        'status',
        'created_by_company_id',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
            'rate_per_hour' => 'decimal:2',
            'amount' => 'decimal:2',
            'is_recurring' => 'boolean',
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

    public function createdByCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'created_by_company_id');
    }

    /**
     * Calculate amount from hours × rate_per_hour
     */
    public function calculateAmount(): float
    {
        if ($this->hours && $this->rate_per_hour) {
            return (float) ($this->hours * $this->rate_per_hour);
        }
        return (float) $this->amount;
    }
}
