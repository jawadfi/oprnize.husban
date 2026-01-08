<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class ShieldSuperAdmin extends Command
{
    protected $signature = 'shield:super-admin 
                            {--user= : The ID or email of the user to assign super admin role}
                            {--guard=company : The guard name to use}';

    protected $description = 'Create super admin role and assign it to a user (for company guard)';

    public function handle()
    {
        $guardName = $this->option('guard') ?? 'company';
        $userInput = $this->option('user');

        if (!$userInput) {
            $this->error('Please provide a user email or ID using --user option');
            return 1;
        }

        // Get super admin role name from config
        $superAdminRoleName = config('filament-shield.super_admin.name', 'super_admin');

        // Create or get the super_admin role with company guard
        $role = Role::firstOrCreate(
            ['name' => $superAdminRoleName, 'guard_name' => $guardName],
            ['name' => $superAdminRoleName, 'guard_name' => $guardName]
        );

        $this->info("Super admin role '{$superAdminRoleName}' with guard '{$guardName}' is ready.");

        // Find user by email or ID
        $user = null;
        
        // Try to find by email first (could be Company or User)
        if (filter_var($userInput, FILTER_VALIDATE_EMAIL)) {
            $user = Company::where('email', $userInput)->first();
            if (!$user) {
                $user = User::where('email', $userInput)->first();
            }
        } else {
            // Try as ID - first Company, then User
            $user = Company::find($userInput);
            if (!$user) {
                $user = User::find($userInput);
            }
        }

        if (!$user) {
            $this->error("User with email/ID '{$userInput}' not found.");
            return 1;
        }

        // Assign the role to the user
        if (!$user->hasRole($role)) {
            $user->assignRole($role);
            $this->info("Super admin role assigned to {$user->email} ({$user->getMorphClass()})");
        } else {
            $this->info("User {$user->email} already has the super admin role.");
        }

        return 0;
    }
}

