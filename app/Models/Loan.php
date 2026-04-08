<?php

namespace App\Models;

use App\Enums\DeductionReason;
use App\Enums\DeductionStatus;
use App\Enums\DeductionType;
use App\Enums\LoanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    protected $fillable = [
        'employee_id',
        'company_id',
        'amount',
        'months',
        'monthly_deduction',
        'remaining_amount',
        'start_month',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount'             => 'decimal:2',
            'monthly_deduction'  => 'decimal:2',
            'remaining_amount'   => 'decimal:2',
            'months'             => 'integer',
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
     * Create a monthly installment deduction for the given payroll month.
     *
     * Must be wrapped in a DB::transaction by the caller.
     * Returns true when a deduction was created, false when skipped.
     */
    public function processMonthlyDeduction(string $payrollMonth): bool
    {
        if ($this->status !== LoanStatus::ACTIVE) {
            return false;
        }

        if ((float) $this->remaining_amount <= 0) {
            $this->update(['status' => LoanStatus::COMPLETED]);
            return false;
        }

        // Prevent duplicate deduction for same month
        if ($this->deductions()->where('payroll_month', $payrollMonth)->exists()) {
            return false;
        }

        $paidCount = $this->deductions()->count();
        $isLastInstallment = ($paidCount + 1) >= $this->months;

        // Last installment takes exact remaining amount to avoid float rounding gaps
        $deductionAmount = $isLastInstallment
            ? round((float) $this->remaining_amount, 2)
            : round((float) $this->monthly_deduction, 2);

        Deduction::create([
            'employee_id'           => $this->employee_id,
            'company_id'            => $this->company_id,
            'loan_id'               => $this->id,
            'payroll_month'         => $payrollMonth,
            'type'                  => DeductionType::FIXED,
            'reason'                => DeductionReason::LOAN,
            'description'           => "قسط قرض - {$payrollMonth}",
            'amount'                => $deductionAmount,
            'status'                => DeductionStatus::APPROVED,
            'created_by_company_id' => $this->company_id,
        ]);

        $newRemaining = round((float) $this->remaining_amount - $deductionAmount, 2);
        $newStatus    = ($isLastInstallment || $newRemaining <= 0)
            ? LoanStatus::COMPLETED
            : LoanStatus::ACTIVE;

        $this->update([
            'remaining_amount' => max(0, $newRemaining),
            'status'           => $newStatus,
        ]);

        return true;
    }
}
