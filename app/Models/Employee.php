<?php

namespace App\Models;

use App\Enums\EmployeeStatusStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
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
        'company_assigned_id'
    ];

    public function scopeByStatus(Builder $query, $status)
    {
        return $query->when($status === EmployeeStatusStatus::AVAILABLE, function (Builder $query) {
            return $query->whereDoesntHave('assigned');
        })->when($status === EmployeeStatusStatus::IN_SERVICE, function (Builder $query) {
            return $query->whereHas('assigned');
        })->when($status === EmployeeStatusStatus::ENDED_SERVICE, function (Builder $query) {
            return $query->where('identity_number', false);
        });
    }

    public function assigned()
    {
        return $this->belongsToMany(Company::class, 'employee_assigned')->withPivot(['status', 'start_date']);
    }
    public function currentCompanyAssigned()
    {
        return $this->belongsTo(Company::class, 'company_assigned_id');
    }
}
