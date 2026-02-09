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

        $company = Company::firstOrCreate(
            ['email' => 'company@test.com'],
            [
                'name' => 'Test Company',
                'commercial_registration_number' => '1234567890',
                'password' => '123',
                'type' => 'client',
                'city_id' => $city->id,
                'email_verified_at' => now(),
            ]
        );
        
        // Mark as verified if not already
        if (!$company->email_verified_at) {
            $company->update(['email_verified_at' => now()]);
        }
    }
}
