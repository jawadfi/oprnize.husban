<?php

namespace App\Filament\Company\Imports;

use App\Enums\DeductionReason;
use App\Enums\DeductionStatus;
use App\Enums\DeductionType;
use App\Models\Deduction;
use App\Models\Employee;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class DeductionImporter extends Importer
{
    protected static ?string $model = Deduction::class;

    public function import(array $row): void
    {
        $companyId = $this->options['company_id'];

        // Clean empty strings to null
        foreach ($row as $key => $value) {
            if ($value === '') {
                $row[$key] = null;
            }
        }

        // Find employee by emp_id or identity_number
        $employee = null;
        if (!empty($row['emp_id'])) {
            $employee = Employee::where('company_id', $companyId)
                ->where('emp_id', $row['emp_id'])
                ->first();
        }

        if (!$employee && !empty($row['identity_number'])) {
            $employee = Employee::where('company_id', $companyId)
                ->where('identity_number', $row['identity_number'])
                ->first();
        }

        if (!$employee) {
            // Skip if employee not found
            return;
        }

        // Determine deduction type and calculate amount
        $type = DeductionType::FIXED;
        $days = null;
        $dailyRate = null;
        $amount = 0;

        // If absence_days is provided, it's a DAYS deduction
        if (!empty($row['absence_days']) && $row['absence_days'] > 0) {
            $type = DeductionType::DAYS;
            $days = (int) $row['absence_days'];

            // Get daily rate from payroll if exists
            $payroll = $employee->payrolls()
                ->where('basic_salary', '>', 0)
                ->latest()
                ->first();

            if ($payroll) {
                $dailyRate = round($payroll->basic_salary / 30, 2);
                $amount = $dailyRate * $days;
            }
        }

        // If deduction_amount is provided, add to total
        if (!empty($row['deduction_amount']) && $row['deduction_amount'] > 0) {
            $amount += (float) $row['deduction_amount'];
        }

        // Skip if no amount
        if ($amount <= 0) {
            return;
        }

        // Create deduction
        Deduction::create([
            'employee_id' => $employee->id,
            'company_id' => $companyId,
            'created_by_company_id' => $companyId,
            'payroll_month' => $row['payroll_month'] ?? now()->format('Y-m'),
            'type' => $type,
            'reason' => !empty($row['absence_days']) ? DeductionReason::ABSENCE : DeductionReason::OTHER,
            'days' => $days,
            'daily_rate' => $dailyRate,
            'amount' => $amount,
            'description' => $row['description'] ?? null,
            'status' => DeductionStatus::APPROVED,
        ]);

        // Handle overtime and additions - update payroll directly
        if ((!empty($row['overtime_hours']) && $row['overtime_hours'] > 0)
            || (!empty($row['addition_amount']) && $row['addition_amount'] > 0)) {

            $payrollMonth = $row['payroll_month'] ?? now()->format('Y-m');
            $payroll = $employee->payrolls()
                ->where('company_id', $companyId)
                ->where('payroll_month', $payrollMonth)
                ->first();

            if ($payroll) {
                $updates = [];

                if (!empty($row['overtime_hours'])) {
                    $overtimeHours = (float) $row['overtime_hours'];
                    // Calculate overtime amount: (basic_salary / 240) * 1.5 * overtime_hours
                    $hourlyRate = $payroll->basic_salary / 240;
                    $overtimeAmount = $hourlyRate * 1.5 * $overtimeHours;

                    $updates['overtime_hours'] = ($payroll->overtime_hours ?? 0) + $overtimeHours;
                    $updates['overtime_amount'] = ($payroll->overtime_amount ?? 0) + $overtimeAmount;
                }

                if (!empty($row['addition_amount'])) {
                    $updates['other_additions'] = ($payroll->other_additions ?? 0) + (float) $row['addition_amount'];
                }

                $payroll->update($updates);
            }
        }
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('emp_id')
                ->label('رقم الموظف')
                ->example('EMP001'),

            ImportColumn::make('identity_number')
                ->label('رقم الهوية')
                ->example('1234567890'),

            ImportColumn::make('payroll_month')
                ->label('شهر الراتب')
                ->example('2026-02'),

            ImportColumn::make('absence_days')
                ->label('عدد أيام الغياب')
                ->example('2'),

            ImportColumn::make('deduction_amount')
                ->label('مبلغ الخصم')
                ->example('500.00'),

            ImportColumn::make('overtime_hours')
                ->label('ساعات العمل الإضافي')
                ->example('10'),

            ImportColumn::make('addition_amount')
                ->label('مبلغ الإضافة')
                ->example('300.00'),

            ImportColumn::make('description')
                ->label('ملاحظات')
                ->example('خصم غياب'),
        ];
    }

    public function resolveRecord(): ?Deduction
    {
        return null;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'اكتمل استيراد الخصومات والإضافات: ' . number_format($import->successful_rows) . ' ' . str('سجل')->plural($import->successful_rows) . ' تم استيراده بنجاح.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('سجل')->plural($failedRowsCount) . ' فشل في الاستيراد.';
        }

        return $body;
    }
}
