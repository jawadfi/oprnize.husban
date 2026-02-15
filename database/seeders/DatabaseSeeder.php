<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\City;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        Admin::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin',
                'password' => '123',
            ]
        );

        $city = City::firstOrCreate(
            ['name' => 'الرياض']
        );

        // PROVIDER company
        $provider = Company::firstOrCreate(
            ['email' => 'provider@test.com'],
            [
                'name' => 'شركة المزود / Provider Co',
                'commercial_registration_number' => '1234567890',
                'password' => '123',
                'type' => 'provider',
                'city_id' => $city->id,
                'email_verified_at' => now(),
            ]
        );
        if (!$provider->email_verified_at) {
            $provider->update(['email_verified_at' => now()]);
        }

        // CLIENT company
        $client = Company::firstOrCreate(
            ['email' => 'client@test.com'],
            [
                'name' => 'شركة العميل / Client Co',
                'commercial_registration_number' => '0987654321',
                'password' => '123',
                'type' => 'client',
                'city_id' => $city->id,
                'email_verified_at' => now(),
            ]
        );
        if (!$client->email_verified_at) {
            $client->update(['email_verified_at' => now()]);
        }

        // Keep old company@test.com if exists
        $company = Company::firstOrCreate(
            ['email' => 'company@test.com'],
            [
                'name' => 'Test Company',
                'commercial_registration_number' => '1234567891',
                'password' => '123',
                'type' => 'client',
                'city_id' => $city->id,
                'email_verified_at' => now(),
            ]
        );
        if (!$company->email_verified_at) {
            $company->update(['email_verified_at' => now()]);
        }
    }
}
