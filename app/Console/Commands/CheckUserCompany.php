<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;

class CheckUserCompany extends Command
{
    protected $signature = 'check:user-company {email?}';
    protected $description = 'Check user company association';

    public function handle()
    {
        $email = $this->argument('email') ?? 'suhaib.istanbouli.dev.hr@gmail.com';
        
        $user = User::where('email', $email)->with('company')->first();
        
        if (!$user) {
            $this->error("User not found: {$email}");
            return;
        }
        
        $this->info("User Details:");
        $this->line("ID: {$user->id}");
        $this->line("Name: {$user->name}");
        $this->line("Email: {$user->email}");
        $this->line("Company ID: {$user->company_id}");
        
        if ($user->company) {
            $this->line("Company Name: {$user->company->name}");
            $this->line("Company Email: {$user->company->email}");
        } else {
            $this->error("Company not found for company_id: {$user->company_id}");
        }
        
        $this->newLine();
        $this->info("All Companies:");
        $companies = Company::all(['id', 'name', 'email']);
        foreach ($companies as $company) {
            $this->line("ID: {$company->id} - {$company->name} ({$company->email})");
        }
        
        $this->newLine();
        $this->info("All Users:");
        $users = User::with('company')->get(['id', 'name', 'email', 'company_id']);
        foreach ($users as $u) {
            $companyInfo = $u->company ? "{$u->company->name} ({$u->company->email})" : "NULL";
            $this->line("ID: {$u->id} - {$u->name} ({$u->email}) - Company: {$companyInfo}");
        }
    }
}
