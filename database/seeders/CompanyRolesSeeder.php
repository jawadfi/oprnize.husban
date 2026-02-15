<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CompanyRolesSeeder extends Seeder
{
    /**
     * Seed default roles for the company guard.
     */
    public function run(): void
    {
        $guard = 'company';

        // Create default roles
        $roles = [
            'super_admin' => 'Full access to all features',
            'admin' => 'Company administrator with full management access',
            'hr_manager' => 'HR manager - manage employees, payroll, deductions',
            'branch_manager' => 'Branch manager - manage branch entries',
            'payroll_officer' => 'Payroll officer - manage payroll processing',
            'viewer' => 'Read-only access to view data',
        ];

        foreach ($roles as $roleName => $description) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => $guard],
                ['name' => $roleName, 'guard_name' => $guard]
            );
        }

        // Define permissions for each role
        $allPermissions = [
            // User management
            'view_any_user', 'view_user', 'create_user', 'update_user', 'delete_user', 'delete_any_user',
            // Role management
            'view_any_role', 'view_role', 'create_role', 'update_role', 'delete_role', 'delete_any_role',
            // Employee management
            'view_any_employee', 'view_employee', 'create_employee', 'update_employee', 'delete_employee',
            // Payroll management
            'view_any_payroll', 'view_payroll', 'create_payroll', 'update_payroll', 'delete_payroll',
            // Deduction management
            'view_any_deduction', 'view_deduction', 'create_deduction', 'update_deduction', 'delete_deduction',
            // Branch management
            'view_any_branch', 'view_branch', 'create_branch', 'update_branch', 'delete_branch',
            // Branch Entry management
            'view_any_branch_entry', 'view_branch_entry', 'create_branch_entry', 'update_branch_entry', 'delete_branch_entry',
            // Leave Request management
            'view_any_leave_request', 'view_leave_request', 'create_leave_request', 'update_leave_request', 'delete_leave_request',
        ];

        // Create all permissions for company guard
        foreach ($allPermissions as $permName) {
            Permission::firstOrCreate(
                ['name' => $permName, 'guard_name' => $guard],
                ['name' => $permName, 'guard_name' => $guard]
            );
        }

        // Assign all permissions to super_admin
        $superAdmin = Role::where('name', 'super_admin')->where('guard_name', $guard)->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::where('guard_name', $guard)->get());
        }

        // Assign all permissions to admin too
        $admin = Role::where('name', 'admin')->where('guard_name', $guard)->first();
        if ($admin) {
            $admin->syncPermissions(Permission::where('guard_name', $guard)->get());
        }

        // HR Manager permissions
        $hrManager = Role::where('name', 'hr_manager')->where('guard_name', $guard)->first();
        if ($hrManager) {
            $hrPerms = Permission::where('guard_name', $guard)
                ->whereIn('name', [
                    'view_any_employee', 'view_employee', 'create_employee', 'update_employee',
                    'view_any_payroll', 'view_payroll', 'create_payroll', 'update_payroll',
                    'view_any_deduction', 'view_deduction', 'create_deduction', 'update_deduction', 'delete_deduction',
                    'view_any_branch_entry', 'view_branch_entry',
                    'view_any_leave_request', 'view_leave_request', 'update_leave_request',
                    'view_any_branch', 'view_branch',
                ])->get();
            $hrManager->syncPermissions($hrPerms);
        }

        // Branch Manager permissions
        $branchManager = Role::where('name', 'branch_manager')->where('guard_name', $guard)->first();
        if ($branchManager) {
            $branchPerms = Permission::where('guard_name', $guard)
                ->whereIn('name', [
                    'view_any_branch_entry', 'view_branch_entry', 'create_branch_entry', 'update_branch_entry',
                    'view_any_employee', 'view_employee',
                    'view_any_branch', 'view_branch',
                    'view_any_deduction', 'view_deduction', 'create_deduction',
                ])->get();
            $branchManager->syncPermissions($branchPerms);
        }

        // Payroll Officer permissions
        $payrollOfficer = Role::where('name', 'payroll_officer')->where('guard_name', $guard)->first();
        if ($payrollOfficer) {
            $payrollPerms = Permission::where('guard_name', $guard)
                ->whereIn('name', [
                    'view_any_payroll', 'view_payroll', 'create_payroll', 'update_payroll',
                    'view_any_deduction', 'view_deduction', 'update_deduction',
                    'view_any_employee', 'view_employee',
                    'view_any_branch_entry', 'view_branch_entry',
                ])->get();
            $payrollOfficer->syncPermissions($payrollPerms);
        }

        // Viewer - read-only
        $viewer = Role::where('name', 'viewer')->where('guard_name', $guard)->first();
        if ($viewer) {
            $viewerPerms = Permission::where('guard_name', $guard)
                ->where('name', 'like', 'view%')
                ->get();
            $viewer->syncPermissions($viewerPerms);
        }

        $this->command->info('Company roles and permissions seeded successfully!');
    }
}
