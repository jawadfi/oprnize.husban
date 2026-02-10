<?php

namespace App\Filament\Company\Resources\EmployeeResource\Pages;

use App\Enums\CompanyTypes;
use App\Enums\PayrollStatus;
use App\Filament\Company\Resources\EmployeeResource;
use App\Models\Payroll;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();
        $data['company_id'] = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);
        return $data;
    }

    protected function afterCreate(): void
    {
        $user = Filament::auth()->user();
        $company = $user instanceof \App\Models\Company ? $user : ($user instanceof \App\Models\User ? $user->company : null);

        // Only auto-create payroll template for PROVIDER companies
        if ($company && $company->type === CompanyTypes::PROVIDER) {
            $currentMonth = now()->format('Y-m');

            // Create an empty payroll record as template (salary fields = 0, to be filled later)
            Payroll::create([
                'employee_id' => $this->record->id,
                'company_id' => $company->id,
                'payroll_month' => $currentMonth,
                'status' => PayrollStatus::DRAFT,
                'basic_salary' => 0,
                'housing_allowance' => 0,
                'transportation_allowance' => 0,
                'food_allowance' => 0,
                'other_allowance' => 0,
                'fees' => 0,
                'total_package' => 0,
                'work_days' => 0,
                'added_days' => 0,
                'overtime_hours' => 0,
                'overtime_amount' => 0,
                'added_days_amount' => 0,
                'other_additions' => 0,
                'absence_days' => 0,
                'absence_unpaid_leave_deduction' => 0,
                'food_subscription_deduction' => 0,
                'other_deduction' => 0,
            ]);
        }
    }
}
