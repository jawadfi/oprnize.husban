<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryCutoff extends Model
{
    protected $fillable = [
        'company_id',
        'section',
        'payroll_month',
        'lock_at',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'lock_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
