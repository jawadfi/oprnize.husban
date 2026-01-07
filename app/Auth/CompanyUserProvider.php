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
        // Use session to determine which model type to retrieve
        $modelType = session('auth_model_type');
        
        if ($modelType === Company::class) {
            $company = Company::find($identifier);
            if ($company) {
                return $company;
            }
        } elseif ($modelType === User::class) {
            $user = User::find($identifier);
            if ($user) {
                return $user;
            }
        } else {
            // Fallback: try both if session type not set (for backward compatibility)
            // Check User first (since Users have company_id and are more specific)
            $user = User::find($identifier);
            if ($user) {
                return $user;
            }
            
            // Try Company if User not found
            $company = Company::find($identifier);
            if ($company) {
                return $company;
            }
        }
        
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

        // Try Company first
        $company = Company::where('email', $credentials['email'])->first();
        if ($company) {
            return $company;
        }
        
        // Try User
        return User::where('email', $credentials['email'])->whereNotNull('company_id')->first();
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

