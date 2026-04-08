<?php

namespace App\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class CompanyUserProvider implements UserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        // Use session to determine which model type to retrieve.
        // Do NOT fall back to guessing: Company and User share the same integer ID
        // namespace, so User::find(company_id) could return the wrong record and
        // expose another company's employees.
        $modelType = session('auth_model_type');

        if ($modelType === Company::class) {
            return Company::find($identifier);
        }

        if ($modelType === User::class) {
            return User::find($identifier);
        }

        // Session type is unknown – force the user to re-authenticate rather than
        // risk returning the wrong model and leaking data.
        return null;
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        // Check User first (same logic as retrieveById)
        $user = User::where('id', $identifier)->where('remember_token', $token)->first();
        if ($user) {
            return $user;
        }
        
        // Try Company if User not found
        return Company::where('id', $identifier)->where('remember_token', $token)->first();
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        $user->setRememberToken($token);
        $user->save();
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (!isset($credentials['email'])) {
            return null;
        }

        // Try User first to allow company staff/support accounts to authenticate
        // even if a Company record happens to share the same email.
        $user = User::where('email', $credentials['email'])
            ->whereNotNull('company_id')
            ->first();

        if ($user) {
            return $user;
        }

        // Fallback to Company account authentication.
        return Company::where('email', $credentials['email'])->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        if (!isset($credentials['password'])) {
            return false;
        }

        return \Hash::check($credentials['password'], $user->getAuthPassword());
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        if (!isset($credentials['password'])) {
            return;
        }

        if ($force || \Hash::needsRehash($user->getAuthPassword())) {
            $user->setAuthPassword(\Hash::make($credentials['password']));
            $user->save();
        }
    }
}

