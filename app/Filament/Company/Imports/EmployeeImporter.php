<?php

namespace App\Filament\Company\Imports;

use App\Models\Employee;
use App\Models\Payroll;
use App\Enums\PayrollStatus;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Carbon;

class EmployeeImporter extends Importer
{
    protected static ?string $model = Employee::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Employee Name')
                ->requiredMapping(),
            ImportColumn::make('emp_id')
                ->label('Employee ID'),
            ImportColumn::make('job_title')
                ->label('Job Title')
                ->requiredMapping(),
            ImportColumn::make('department')
                ->label('Department'),
            ImportColumn::make('location')
                ->label('Location'),
            ImportColumn::make('iqama_no')
                ->label('Iqama No'),
            ImportColumn::make('hire_date')
                ->label('Hire Date'),
            ImportColumn::make('identity_number')
                ->label('Identity Number')
                ->requiredMapping(),
            ImportColumn::make('nationality')
                ->label('Nationality')
                ->requiredMapping(),
            ImportColumn::make('email')
                ->label('Email'),
        ];
    }

    public function resolveRecord(): ?Employee
    {
        $companyId = $this->options['company_id'];

        // Find existing employee by identity_number + company_id, or create new
        $employee = Employee::firstOrNew([
            'identity_number' => $this->data['identity_number'],
            'company_id' => $companyId,
        ]);

        $employee->company_id = $companyId;

        return $employee;
    }

    public function beforeFill(): void
    {
        // Handle hire_date format
        if (!empty($this->data['hire_date'])) {
            try {
                $this->data['hire_date'] = Carbon::make($this->data['hire_date'])->format('Y-m-d');
            } catch (\Exception $exception) {
                $this->data['hire_date'] = null;
            }
        }

        // Clean empty strings to null
        foreach ($this->data as $key => $value) {
            if ($value === '') {
                $this->data[$key] = null;
            }
        }
    }

    public function afterCreate(): void
    {
        // Auto-create empty payroll record for newly imported employee
        $companyId = $this->options['company_id'];

        $existingPayroll = Payroll::where('employee_id', $this->record->id)
            ->where('company_id', $companyId)
            ->where('payroll_month', now()->format('Y-m'))
            ->exists();

        if (!$existingPayroll) {
            Payroll::create([
                'employee_id' => $this->record->id,
                'company_id' => $companyId,
                'payroll_month' => now()->format('Y-m'),
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

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Employee import completed: ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported successfully.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
