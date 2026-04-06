<?php

namespace App\Models;

use App\Enums\EndOfServiceRequestStatus;
use App\Enums\TerminationReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndOfServiceRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'company_id',
        'provider_company_id',
        'client_company_id',
        'current_approver_company_id',
        'termination_reason',
        'last_working_date',
        'service_start_date',
        'service_days',
        'salary_amount',
        'estimated_amount',
        'status',
        'notes',
        'approved_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'termination_reason' => TerminationReason::class,
            'last_working_date' => 'date',
            'service_start_date' => 'date',
            'service_days' => 'integer',
            'salary_amount' => 'decimal:2',
            'estimated_amount' => 'decimal:2',
            'status' => EndOfServiceRequestStatus::class,
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
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

    public function moveToClientApproval(): void
    {
        $clientCompanyId = $this->client_company_id
            ?? $this->employee->company_assigned_id
            ?? $this->company_id;

        $this->update([
            'client_company_id' => $clientCompanyId,
            'status' => EndOfServiceRequestStatus::PENDING_CLIENT_APPROVAL,
            'current_approver_company_id' => $clientCompanyId,
        ]);
    }

    public function moveToProviderApproval(): void
    {
        $providerCompanyId = $this->provider_company_id ?? $this->employee->company_id;

        $this->update([
            'provider_company_id' => $providerCompanyId,
            'company_id' => $providerCompanyId,
            'status' => EndOfServiceRequestStatus::PENDING_PROVIDER_APPROVAL,
            'current_approver_company_id' => $providerCompanyId,
        ]);
    }

    public function finalizeApproval(): void
    {
        $this->update([
            'status' => EndOfServiceRequestStatus::APPROVED,
            'current_approver_company_id' => null,
            'approved_at' => now(),
            'rejected_at' => null,
        ]);
    }

    public function reject(): void
    {
        $this->update([
            'status' => EndOfServiceRequestStatus::REJECTED,
            'current_approver_company_id' => null,
            'rejected_at' => now(),
        ]);
    }
}
