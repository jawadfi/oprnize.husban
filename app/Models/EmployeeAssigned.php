<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EmployeeAssigned extends Pivot
{
    protected $table = 'employee_assigned';

    protected $fillable =[
      'employee_id',
      'status',
      'company_id',
      'start_date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function updateStatus($status)
    {
        return $this->update(['status' => $status]);
    }
}
