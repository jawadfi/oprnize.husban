<?php

namespace App\Filament\Company\Resources\PayrollResource\Pages;

use App\Filament\Company\Resources\PayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayroll extends EditRecord
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Recalculate total_package from individual salary components
        // This ensures old records that may have had fees included are corrected on load
        $data['total_package'] =
            (float)($data['basic_salary'] ?? 0) +
            (float)($data['housing_allowance'] ?? 0) +
            (float)($data['transportation_allowance'] ?? 0) +
            (float)($data['food_allowance'] ?? 0) +
            (float)($data['other_allowance'] ?? 0);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
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

        // Always recalculate total_package from salary components
        $data['total_package'] =
            (float)($data['basic_salary'] ?? 0) +
            (float)($data['housing_allowance'] ?? 0) +
            (float)($data['transportation_allowance'] ?? 0) +
            (float)($data['food_allowance'] ?? 0) +
            (float)($data['other_allowance'] ?? 0);

        return $data;
    }
}
