<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FixRoleGuardName extends Command
{
    protected $signature = 'fix:role-guard-name';
    protected $description = 'Fix roles and permissions to use company guard_name';

    public function handle()
    {
        $this->info('Fixing roles and permissions guard_name to "company"...');

        // Get all roles with 'web' guard
        $roles = Role::where('guard_name', 'web')->get();
        $this->info("Found {$roles->count()} roles with 'web' guard");

        foreach ($roles as $role) {
            // Check if role with same name and 'company' guard exists
            $existingCompanyRole = Role::where('name', $role->name)
                ->where('guard_name', 'company')
                ->first();

            if ($existingCompanyRole) {
                // Migrate permissions from web role to company role
                $permissions = $role->permissions;
                foreach ($permissions as $permission) {
                    // Get or create permission with company guard
                    $companyPermission = Permission::firstOrCreate(
                        ['name' => $permission->name, 'guard_name' => 'company'],
                        ['name' => $permission->name, 'guard_name' => 'company']
                    );
                    
                    if (!$existingCompanyRole->hasPermissionTo($companyPermission)) {
                        $existingCompanyRole->givePermissionTo($companyPermission);
                    }
                }
                
                $this->info("Migrated permissions from '{$role->name}' (web) to existing '{$role->name}' (company) role");
            } else {
                // Update role guard_name to company
                $role->guard_name = 'company';
                $role->save();
                
                // Update all permissions for this role to company guard
                $permissions = $role->permissions;
                foreach ($permissions as $permission) {
                    $companyPermission = Permission::firstOrCreate(
                        ['name' => $permission->name, 'guard_name' => 'company'],
                        ['name' => $permission->name, 'guard_name' => 'company']
                    );
                    
                    // Sync permission
                    $role->permissions()->detach($permission);
                    if (!$role->hasPermissionTo($companyPermission)) {
                        $role->givePermissionTo($companyPermission);
                    }
                }
                
                $this->info("Updated '{$role->name}' role guard_name to 'company'");
            }
        }

        // Update all permissions with 'web' guard to 'company'
        $permissions = Permission::where('guard_name', 'web')->get();
        $this->info("Found {$permissions->count()} permissions with 'web' guard");

        foreach ($permissions as $permission) {
            // Check if permission with same name and 'company' guard exists
            $existingCompanyPermission = Permission::where('name', $permission->name)
                ->where('guard_name', 'company')
                ->first();

            if (!$existingCompanyPermission) {
                // Create company version
                Permission::create([
                    'name' => $permission->name,
                    'guard_name' => 'company',
                ]);
                
                $this->info("Created '{$permission->name}' permission with 'company' guard");
            }
        }

        $this->info('Guard name fix completed!');
    }
}

