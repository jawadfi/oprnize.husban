<?php

namespace App\Models;

use App\Enums\DeductionReason;
use App\Enums\DeductionStatus;
use App\Enums\DeductionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deduction extends Model
{
    protected $fillable = [
        'employee_id',
        'company_id',
        'payroll_id',
        'payroll_month',
        'type',
        'reason',
        'description',
        'days',
        'amount',
        'daily_rate',
        'status',
        'created_by_company_id',
    ];

    protected function casts(): array
    {
        return [
            'days' => 'integer',
            'amount' => 'decimal:2',
            'daily_rate' => 'decimal:2',
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

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function createdByCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'created_by_company_id');
    }

    /**
     * Calculate the deduction amount based on type
     */
    public function calculateAmount(): float
    {
        if ($this->type === DeductionType::FIXED) {
            return (float) $this->amount;
        }

        // Type = DAYS: amount = days * daily_rate
        if ($this->type === DeductionType::DAYS && $this->days && $this->daily_rate) {
            return (float) ($this->days * $this->daily_rate);
        }

        return (float) $this->amount;
    }

    /**
     * Get translated type label
     */
    public function getTypeLabelAttribute(): string
    {
        return DeductionType::getTranslatedEnum()[$this->type] ?? $this->type;
    }

    /**
     * Get translated reason label
     */
    public function getReasonLabelAttribute(): string
    {
        return DeductionReason::getTranslatedEnum()[$this->reason] ?? $this->reason;
    }

    /**
     * Get translated status label
     */
    public function getStatusLabelAttribute(): string
    {
        return DeductionStatus::getTranslatedEnum()[$this->status] ?? $this->status;
    }
}
