<?php

namespace App\Filament\Company\Resources\PayrollResource\Pages;

use App\Filament\Company\Resources\PayrollResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreatePayroll extends CreateRecord
{
    protected static string $resource = PayrollResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();
        $data['company_id'] = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);
        $data['status'] = \App\Enums\PayrollStatus::DRAFT;
        if (empty($data['payroll_month'])) {
            $data['payroll_month'] = now()->format('Y-m');
        }
        
        // Convert empty numeric fields to 0 to prevent null constraint violations
        $numericFields = [
            'basic_salary',
            'housing_allowance',
            'transportation_allowance',
            'food_allowance',
            'other_allowance',
            'fees',
            'total_package',
            'work_days',
            'added_days',
            'overtime_hours',
            'overtime_amount',
            'added_days_amount',
            'other_additions',
            'absence_days',
            'absence_unpaid_leave_deduction',
            'food_subscription_deduction',
            'other_deduction',
        ];

        foreach ($numericFields as $field) {
            if (isset($data[$field]) && ($data[$field] === '' || $data[$field] === null)) {
                $data[$field] = 0;
            }
        }
        
        return $data;
    }
}
