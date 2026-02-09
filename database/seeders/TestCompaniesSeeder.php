<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestCompaniesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if accounts already exist
        $providerExists = Company::where('email', 'provider@test.com')->exists();
        $clientExists = Company::where('email', 'client@test.com')->exists();

        if (!$providerExists) {
            $provider = Company::create([
                'name' => 'HR Provider Company - مزود الموارد البشرية',
                'commercial_registration_number' => '1234567890',
                'email' => 'provider@test.com',
                'password' => bcrypt('123'),
                'type' => 'provider',
                'city_id' => 1,
            ]);
            $this->command->info("✓ Created PROVIDER: {$provider->email}");
        } else {
            $this->command->warn("⊘ PROVIDER account already exists: provider@test.com");
        }

        if (!$clientExists) {
            $client = Company::create([
                'name' => 'Apple Company - شركة ابل',
                'commercial_registration_number' => '9876543210',
                'email' => 'client@test.com',
                'password' => bcrypt('123'),
                'type' => 'client',
                'city_id' => 1,
            ]);
            $this->command->info("✓ Created CLIENT: {$client->email}");
        } else {
            $this->command->warn("⊘ CLIENT account already exists: client@test.com");
        }

        $this->command->newLine();
        $this->command->info("========================================");
        $this->command->info("Test Accounts Summary:");
        $this->command->info("========================================");
        $this->command->info("1. PROVIDER: provider@test.com / 123");
        $this->command->info("2. CLIENT: client@test.com / 123");
        $this->command->info("========================================");
    }
}
