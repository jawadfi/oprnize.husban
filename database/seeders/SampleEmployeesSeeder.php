<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Seeder;

/**
 * Seeds 13 sample employees (Bangladeshi Salesman Assistants).
 *
 * Run with:
 *   php artisan db:seed --class=SampleEmployeesSeeder
 *
 * By default assigns employees to the first provider company.
 * Override with:  SEED_COMPANY_EMAIL=xxx php artisan db:seed --class=SampleEmployeesSeeder
 */
class SampleEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        // Resolve company
        $email   = env('SEED_COMPANY_EMAIL');
        $company = $email
            ? Company::where('email', $email)->first()
            : null;

        $company ??= Company::where('type', 'provider')->first();
        $company ??= Company::first();

        if (! $company) {
            $this->command->error('No company found. Run DatabaseSeeder first or set SEED_COMPANY_EMAIL.');
            return;
        }

        $this->command->info("Assigning employees to: {$company->name} (ID: {$company->id})");

        // emp_id = "Emp.ID" column value (used by salary import to match employees)
        $employees = [
            [
                'emp_id'          => '80132',
                'name'            => 'Mohammad Masud Rana Mohammad',
                'identity_number' => '2464871595',
                'iqama_no'        => '2464871595',
                'nationality'     => 'Bangladesh',
                'location'        => 'Riyadh Sulay',
                'hire_date'       => '2021-05-25',
                'job_title'       => 'Salesman Assistant',
                'department'      => 'Retail Sales',
            ],
            [
                'emp_id'          => '60459',
                'name'            => 'Mohammad Zahangir Alom Md Hassen Ali Pramanik',
                'identity_number' => '2496901741',
                'iqama_no'        => '2496901741',
                'nationality'     => 'Bangladesh',
                'location'        => 'Khamis Mushait',
                'hire_date'       => '2021-06-04',
                'job_title'       => 'Salesman Assistant',
                'department'      => 'Retail Sales',
            ],
            [
                'emp_id'          => '60451',
                'name'            => 'Mohammad Alauddin Abul Khayer',
                'identity_number' => '2496901543',
                'iqama_no'        => '2496901543',
                'nationality'     => 'Bangladesh',
                'location'        => 'Riyadh Sulay',
                'hire_date'       => '2021-06-06',
                'job_title'       => 'Salesman Assistant',
                'department'      => 'Retail Sales',
            ],
            [
                'emp_id'          => '80136',
                'name'            => 'Md Najmul Hasan',
                'identity_number' => '2469291492',
                'iqama_no'        => '2469291492',
                'nationality'     => 'Bangladesh',
                'location'        => 'Jazan',
                'hire_date'       => '2021-06-09',
                'job_title'       => 'Salesman Assistant',
                'department'      => 'Retail Sales',
            ],
            [
                'emp_id'          => '80130',
                'name'            => 'Md Raisul',
                'identity_number' => '2469287458',
                'iqama_no'        => '2469287458',
                'nationality'     => 'Bangladesh',
                'location'        => 'Riyadh Sulay',
                'hire_date'       => '2021-06-21',
                'job_title'       => 'Salesman Assistant',
                'department'      => 'Retail Sales',
            ],
            [
                'emp_id'          => '60270',
                'name'            => 'Md Ripon Md Yousuf',
                'identity_number' => '2481426092',
                'iqama_no'        => '2481426092',
                'nationality'     => 'Bangladesh',
                'location'        => 'Jazan',
                'hire_date'       => '2021-08-19',
                'job_title'       => 'Salesman Assistant',
                'department'      => 'Retail Sales',
            ],
            [
                'emp_id'          => '60481',
                'name'            => 'Tofayel Abul Kalam Aeyeasa',
                'identity_number' => '2499940506',
                'iqama_no'        => '2499940506',
                'nationality'     => 'Bangladesh',
                'location'        => 'Riyadh Sulay',
                'hire_date'       => '2021-08-22',
                'job_title'       => 'Salesman Assistant',
                'department'      => 'Retail Sales',
            ],
            [
                'emp_id'          => '60487',
                'name'            => 'Montu Motaleb Gazi',
                'identity_number' => '2499940761',
                'iqama_no'        => '2499940761',
                'nationality'     => 'Bangladesh',
                'location'        => 'North Riyadh',
                'hire_date'       => '2021-08-22',
                'job_title'       => 'Salesman Assistant',
                'department'      => 'Retail Sales',
            ],
            [
                'emp_id'          => '60205',
                'name'            => 'Md Taj Uddin Md',
                'identity_number' => '2480294657',
                'iqama_no'        => '2480294657',
                'nationality'     => 'Bangladesh',
                'location'        => 'Dammam',
                'hire_date'       => '2021-09-01',
                'job_title'       => 'Salesman Assistant',
                'department'      => 'DTC',
            ],
            [
                'emp_id'          => '60498',
                'name'            => 'Md Sojol Biswas',
                'identity_number' => '2500486135',
                'iqama_no'        => '2500486135',
                'nationality'     => 'Bangladesh',
                'location'        => 'Jeddah',
                'hire_date'       => '2021-09-01',
                'job_title'       => 'Salesman Assistant',
                'department'      => 'DTC',
            ],
            [
                'emp_id'          => '80131',
                'name'            => 'Shomon Howlader',
                'identity_number' => '2469287649',
                'iqama_no'        => '2469287649',
                'nationality'     => 'Bangladesh',
                'location'        => 'Jeddah',
                'hire_date'       => '2021-09-01',
                'job_title'       => 'Salesman Assistant',
                'department'      => 'DTC',
            ],
            [
                'emp_id'          => '80133',
                'name'            => 'Md Kawsar Alam Saifullah Chan Miah Sarker',
                'identity_number' => '2469291641',
                'iqama_no'        => '2469291641',
                'nationality'     => 'Bangladesh',
                'location'        => 'Dammam',
                'hire_date'       => '2021-10-05',
                'job_title'       => 'Salesman Assistant',
                'department'      => 'Retail Sales',
            ],
            [
                'emp_id'          => '60105',
                'name'            => 'Md Mostakin',
                'identity_number' => '2475726705',
                'iqama_no'        => '2475726705',
                'nationality'     => 'Bangladesh',
                'location'        => 'Khamis Mushait',
                'hire_date'       => '2021-10-29',
                'job_title'       => 'Salesman Assistant',
                'department'      => 'DTC',
            ],
        ];

        $created = 0;
        $updated = 0;

        foreach ($employees as $data) {
            $exists = Employee::where('identity_number', $data['identity_number'])
                ->where('company_id', $company->id)
                ->exists();

            Employee::updateOrCreate(
                [
                    'identity_number' => $data['identity_number'],
                    'company_id'      => $company->id,
                ],
                array_merge($data, ['company_id' => $company->id])
            );

            $exists ? $updated++ : $created++;
        }

        $this->command->info("Done — created: {$created}, updated: {$updated}");
    }
}
