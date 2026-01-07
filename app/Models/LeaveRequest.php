<?php

namespace App\Models;

use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveType;
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

    public function isPendingClientApproval(): bool
    {
        return $this->status->value === LeaveRequestStatus::PENDING_CLIENT_APPROVAL;
    }

    public function isPendingProviderApproval(): bool
    {
        return $this->status->value === LeaveRequestStatus::PENDING_PROVIDER_APPROVAL;
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
    }
}

