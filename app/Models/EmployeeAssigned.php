<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EmployeeAssigned extends Pivot
{
    protected $table = 'employee_assigned';

    protected $fillable = [
        'employee_id',
        'status',
        'company_id',
        'branch_id',
        'start_date',
        'arrival_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'arrival_date' => 'date',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function updateStatus($status)
    {
        return $this->update(['status' => $status]);
    }
}
