<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Admin;
use App\Models\City;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeAssigned;
use App\Models\Payroll;
use App\Models\LeaveRequest;
use App\Models\User;

class ImportDataToRender extends Command
{
    protected $signature = 'db:import-to-render';
    protected $description = 'Import all data from local MySQL to Render PostgreSQL';

    public function handle()
    {
        $this->info('Starting data import...');
        
        // Get Render database credentials from environment
        $renderHost = env('RENDER_DB_HOST', 'dpg-d64nthv5r7bs739d2ipg-a');
        $renderPort = env('RENDER_DB_PORT', '5432');
        $renderDatabase = env('RENDER_DB_DATABASE', 'oprnize_db_6rxb');
        $renderUsername = env('RENDER_DB_USERNAME', 'oprnize_db_6rxb_user');
        $renderPassword = env('RENDER_DB_PASSWORD');
        
        if (!$renderPassword) {
            $this->error('Please set RENDER_DB_PASSWORD in your .env file');
            return 1;
        }
        
        // Configure temporary Render connection
        config(['database.connections.render' => [
            'driver' => 'pgsql',
            'host' => $renderHost,
            'port' => $renderPort,
            'database' => $renderDatabase,
            'username' => $renderUsername,
            'password' => $renderPassword,
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
        ]]);
        
        DB::purge('render');
        
        try {
            // Test connection
            DB::connection('render')->getPdo();
            $this->info('✓ Connected to Render database');
            
            // Import Cities
            $this->importCities();
            
            // Import Admins
            $this->importAdmins();
            
            // Import Companies
            $this->importCompanies();
            
            // Import Users
            $this->importUsers();
            
            // Import Employees
            $this->importEmployees();
            
            // Import Employee Assignments
            $this->importEmployeeAssignments();
            
            // Import Payrolls
            $this->importPayrolls();
            
            // Import Leave Requests
            $this->importLeaveRequests();
            
            $this->info('✓ All data imported successfully!');
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
    
    private function importCities()
    {
        $this->info('Importing cities...');
        $cities = DB::connection('mysql')->table('cities')->get();
        
        foreach ($cities as $city) {
            DB::connection('render')->table('cities')->updateOrInsert(
                ['id' => $city->id],
                [
                    'name' => $city->name,
                    'created_at' => $city->created_at,
                    'updated_at' => $city->updated_at,
                ]
            );
        }
        
        $this->info("✓ Imported {$cities->count()} cities");
    }
    
    private function importAdmins()
    {
        $this->info('Importing admins...');
        $admins = DB::connection('mysql')->table('admins')->get();
        
        foreach ($admins as $admin) {
            DB::connection('render')->table('admins')->updateOrInsert(
                ['id' => $admin->id],
                [
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'password' => $admin->password,
                    'remember_token' => $admin->remember_token ?? null,
                    'created_at' => $admin->created_at ?? now(),
                    'updated_at' => $admin->updated_at ?? now(),
                ]
            );
        }
        
        $this->info("✓ Imported {$admins->count()} admins");
    }
    
    private function importCompanies()
    {
        $this->info('Importing companies...');
        $companies = DB::connection('mysql')->table('companies')->get();
        
        foreach ($companies as $company) {
            DB::connection('render')->table('companies')->updateOrInsert(
                ['id' => $company->id],
                [
                    'name' => $company->name,
                    'commercial_registration_number' => $company->commercial_registration_number,
                    'email' => $company->email,
                    'type' => $company->type,
                    'city_id' => $company->city_id,
                    'password' => $company->password,
                    'email_verified_at' => $company->email_verified_at ?? null,
                    'remember_token' => $company->remember_token ?? null,
                    'created_at' => $company->created_at ?? now(),
                    'updated_at' => $company->updated_at ?? now(),
                ]
            );
        }
        
        $this->info("✓ Imported {$companies->count()} companies");
    }
    
    private function importUsers()
    {
        $this->info('Importing users...');
        $users = DB::connection('mysql')->table('users')->get();
        
        foreach ($users as $user) {
            DB::connection('render')->table('users')->updateOrInsert(
                ['id' => $user->id],
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at ?? null,
                    'password' => $user->password,
                    'remember_token' => $user->remember_token ?? null,
                    'company_id' => $user->company_id ?? null,
                    'created_at' => $user->created_at ?? now(),
                    'updated_at' => $user->updated_at ?? now(),
                ]
            );
        }
        
        $this->info("✓ Imported {$users->count()} users");
    }
    
    private function importEmployees()
    {
        $this->info('Importing employees...');
        $employees = DB::connection('mysql')->table('employees')->get();
        
        foreach ($employees as $employee) {
            DB::connection('render')->table('employees')->updateOrInsert(
                ['id' => $employee->id],
                [
                    'name' => $employee->name,
                    'job_title' => $employee->job_title ?? null,
                    'emp_id' => $employee->emp_id ?? null,
                    'department' => $employee->department ?? null,
                    'location' => $employee->location ?? null,
                    'iqama_no' => $employee->iqama_no ?? null,
                    'hire_date' => $employee->hire_date ?? null,
                    'identity_number' => $employee->identity_number ?? null,
                    'nationality' => $employee->nationality ?? null,
                    'company_id' => $employee->company_id,
                    'company_assigned_id' => $employee->company_assigned_id ?? null,
                    'email' => $employee->email ?? null,
                    'password' => $employee->password ?? null,
                    'email_verified_at' => $employee->email_verified_at ?? null,
                    'remember_token' => $employee->remember_token ?? null,
                    'created_at' => $employee->created_at ?? now(),
                    'updated_at' => $employee->updated_at ?? now(),
                ]
            );
        }
        
        $this->info("✓ Imported {$employees->count()} employees");
    }
    
    private function importEmployeeAssignments()
    {
        $this->info('Importing employee assignments...');
        $assignments = DB::connection('mysql')->table('employee_assigned')->get();
        
        foreach ($assignments as $assignment) {
            DB::connection('render')->table('employee_assigned')->updateOrInsert(
                ['id' => $assignment->id],
                [
                    'employee_id' => $assignment->employee_id,
                    'company_id' => $assignment->company_id,
                    'status' => $assignment->status,
                    'start_date' => $assignment->start_date ?? null,
                    'created_at' => $assignment->created_at ?? now(),
                    'updated_at' => $assignment->updated_at ?? now(),
                ]
            );
        }
        
        $this->info("✓ Imported {$assignments->count()} employee assignments");
    }
    
    private function importPayrolls()
    {
        $this->info('Importing payrolls...');
        $payrolls = DB::connection('mysql')->table('payrolls')->get();
        
        foreach ($payrolls as $payroll) {
            DB::connection('render')->table('payrolls')->updateOrInsert(
                ['id' => $payroll->id],
                (array) $payroll
            );
        }
        
        $this->info("✓ Imported {$payrolls->count()} payrolls");
    }
    
    private function importLeaveRequests()
    {
        $this->info('Importing leave requests...');
        $requests = DB::connection('mysql')->table('leave_requests')->get();
        
        foreach ($requests as $request) {
            DB::connection('render')->table('leave_requests')->updateOrInsert(
                ['id' => $request->id],
                (array) $request
            );
        }
        
        $this->info("✓ Imported {$requests->count()} leave requests");
    }
}
