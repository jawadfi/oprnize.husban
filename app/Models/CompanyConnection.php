<?php

namespace App\Models;

use App\Enums\CompanyConnectionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyConnection extends Model
{
    protected $fillable = [
        'provider_company_id',
        'client_company_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => CompanyConnectionStatus::class,
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'provider_company_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'client_company_id');
    }
}
