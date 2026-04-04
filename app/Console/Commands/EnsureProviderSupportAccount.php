<?php

namespace App\Console\Commands;

use App\Enums\CompanyTypes;
use App\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class EnsureProviderSupportAccount extends Command
{
    protected $signature = 'app:ensure-provider-support
        {--email= : Support account email (default from env SUPPORT_ACCOUNT_EMAIL or support@init.com)}
        {--password= : Support account password (default from env SUPPORT_ACCOUNT_PASSWORD or Support@123)}
        {--provider-email= : Provider company email (default from env PROVIDER_COMPANY_EMAIL)}';

    protected $description = 'Ensure a provider support account exists and is linked to a provider company';

    public function handle(): int
    {
        $supportEmail = $this->option('email')
            ?: env('SUPPORT_ACCOUNT_EMAIL', 'support@init.com');

        $supportPassword = $this->option('password')
            ?: env('SUPPORT_ACCOUNT_PASSWORD', 'Support@123');

        $providerEmail = $this->option('provider-email')
            ?: env('PROVIDER_COMPANY_EMAIL');

        $provider = null;

        if ($providerEmail) {
            $provider = Company::where('email', $providerEmail)
                ->where('type', CompanyTypes::PROVIDER)
                ->first();
        }

        if (! $provider) {
            $provider = Company::where('type', CompanyTypes::PROVIDER)
                ->orderBy('id')
                ->first();
        }

        if (! $provider) {
            $this->warn('No provider company found. Skipping support account creation.');
            return self::SUCCESS;
        }

        $user = User::firstOrNew(['email' => $supportEmail]);

        $isNew = ! $user->exists;

        $user->name = 'support';
        $user->email = $supportEmail;
        $user->company_id = $provider->id;
        $user->email_verified_at = now();

        // Keep existing password unless this is a new user, or an explicit password was passed,
        // or password env value is set.
        if ($isNew || $this->option('password') || env('SUPPORT_ACCOUNT_PASSWORD')) {
            $user->password = Hash::make($supportPassword);
        }

        $user->save();

        $role = Role::where('guard_name', 'company')
            ->whereIn('name', ['hr_manager', 'super_admin'])
            ->orderByRaw("CASE WHEN name = 'hr_manager' THEN 0 ELSE 1 END")
            ->first();

        if ($role && ! $user->hasRole($role->name, 'company')) {
            $user->assignRole($role);
        }

        $status = $isNew ? 'created' : 'updated';

        $this->info("Support account {$status}: {$supportEmail}");
        $this->line("Provider: {$provider->name} ({$provider->email})");
        $this->line('Role: '.($role?->name ?? 'none (role not found)'));

        return self::SUCCESS;
    }
}
