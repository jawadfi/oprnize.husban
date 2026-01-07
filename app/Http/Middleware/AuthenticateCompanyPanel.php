<?php

namespace App\Http\Middleware;

use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Http\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Database\Eloquent\Model;

class AuthenticateCompanyPanel extends BaseAuthenticate
{
    /**
     * @param  array<string>  $guards
     */
    protected function authenticate($request, array $guards): void
    {
        $guard = Filament::auth();

        if (! $guard->check()) {
            $this->unauthenticated($request, $guards);
            return; /** @phpstan-ignore-line */
        }

        $this->auth->shouldUse(Filament::getAuthGuard());

        /** @var Model $user */
        $user = $guard->user();
        $panel = Filament::getCurrentPanel();

        $canAccess = $user instanceof FilamentUser 
            ? $user->canAccessPanel($panel) 
            : (config('app.env') === 'local');

        abort_if(
            !$canAccess,
            403,
        );
    }
}

