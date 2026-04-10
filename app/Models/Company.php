<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class Company extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    use Notifiable,HasRoles;

    /**
     * The guard name for Spatie Permission
     */
    protected $guard_name = 'company';

    protected $fillable = [
        'name',
        'commercial_registration_number',
        'email',
        'type',
        'city_id',
        'password'
    ];

    protected $hidden = [
        'email_verified_at',
        'password'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed'
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Company $company) {
            $company->assignRole('super_admin');
        });
    }
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function original_employees()
    {
        return $this->hasMany(Employee::class);
    }
    public function used_employees(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_assigned')->withPivot(['status','start_date']);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function endOfServiceRequests()
    {
        return $this->hasMany(EndOfServiceRequest::class);
    }

    public function deductions()
    {
        return $this->hasMany(Deduction::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    // ─── Company Connection relationships ────────────────────────────────────

    /**
     * Connection requests sent by this provider to clients.
     */
    public function connectionRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CompanyConnection::class, 'provider_company_id');
    }

    /**
     * Connection requests received by this client from providers.
     */
    public function receivedConnections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CompanyConnection::class, 'client_company_id');
    }

    /**
     * Get the default guard name for roles/permissions
     */
    public function getDefaultGuardName(): string
    {
        return 'company';
    }
}
