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

        // NEW PROVIDER company for testing
        $provider = Company::firstOrCreate(
            ['email' => 'husban-provider@test.com'],
            [
                'name' => 'حسبان للتوظيف / Husban Provider',
                'commercial_registration_number' => '2024001234',
                'password' => '123',
                'type' => 'provider',
                'city_id' => $city->id,
                'email_verified_at' => now(),
            ]
        );
        if (!$provider->email_verified_at) {
            $provider->update(['email_verified_at' => now()]);
        }

        // NEW CLIENT company for testing
        $client = Company::firstOrCreate(
            ['email' => 'husban-client@test.com'],
            [
                'name' => 'شركة حسبان العميلة / Husban Client',
                'commercial_registration_number' => '2024005678',
                'password' => '123',
                'type' => 'client',
                'city_id' => $city->id,
                'email_verified_at' => now(),
            ]
        );
        if (!$client->email_verified_at) {
            $client->update(['email_verified_at' => now()]);
        }
    }
}
