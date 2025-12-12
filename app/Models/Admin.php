<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable implements FilamentUser
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_url',
        'custom_fields'
    ];

    protected function casts(): array
    {
        return [
            'password'=>'hashed'
        ];
    }
    protected $hidden = [
        'password',
        'remember_token',
    ];


    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
