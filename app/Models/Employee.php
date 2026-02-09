<?php

namespace App\Models;

use App\Enums\EmployeeAssignedStatus;
use App\Enums\EmployeeStatusStatus;
use App\Models\LeaveRequest;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Employee extends Authenticatable implements FilamentUser
{
    use Notifiable;
    protected $fillable = [
        'name',
        'job_title',
        'emp_id',
        'department',
        'location',
        'iqama_no',
        'hire_date',
        'identity_number',
        'nationality',
        'company_id',
        'company_assigned_id',
        'email',
        'password',
        'email_verified_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'=>'datetime',
            'password'=>'hashed'
        ];
    }

    public function scopeByStatus(Builder $query, $status)
    {
        return $query->when($status === EmployeeStatusStatus::AVAILABLE, function (Builder $query) {
            return $query->whereDoesntHave('assigned');
        })->when($status === EmployeeStatusStatus::IN_SERVICE, function (Builder $query) {
            return $query->whereHas('assigned',fn(Builder $query) => $query->where('employee_assigned.status',EmployeeAssignedStatus::APPROVED));
        })->when($status === EmployeeStatusStatus::ENDED_SERVICE, function (Builder $query) {
            return $query->where('identity_number', false);
        });
    }

    public function assigned()
    {
        return $this->belongsToMany(Company::class, 'employee_assigned')
            ->withPivot(['status', 'start_date'])
            ->withTimestamps();
    }
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function currentCompanyAssigned()
    {
        return $this->belongsTo(Company::class, 'company_assigned_id');
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function currentPayroll()
    {
        return $this->hasOne(Payroll::class)->latestOfMany();
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function deductions()
    {
        return $this->hasMany(Deduction::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'employee';
    }
}
