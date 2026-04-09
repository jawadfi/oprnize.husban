<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Company;
use Illuminate\Database\Seeder;

/**
 * Seeds the "Support" provider company (support@init.com).
 *
 * Run standalone:
 *   php artisan db:seed --class=SupportProviderSeeder
 *
 * Already called automatically by DatabaseSeeder.
 */
class SupportProviderSeeder extends Seeder
{
    public function run(): void
    {
        $city = City::firstOrCreate(['name' => 'الرياض']);

        $company = Company::firstOrCreate(
            ['email' => 'support@init.com'],
            [
                'name'                            => 'Support Provider',
                'commercial_registration_number'  => '1111111111',
                'password'                        => '123',
                'type'                            => 'provider',
                'city_id'                         => $city->id,
                'email_verified_at'               => now(),
            ]
        );

        // Make sure the account is verified even if the row pre-existed
        if (! $company->email_verified_at) {
            $company->update(['email_verified_at' => now()]);
        }

        $this->command?->info("Support provider company ready → ID: {$company->id} | email: support@init.com | password: 123");
    }
}
