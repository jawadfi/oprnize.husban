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

    protected ?Employee $employee = null;

    public function resolveRecord(): ?Deduction
    {
        $companyId = $this->options['company_id'];

        // Clean empty strings to null
        foreach ($this->data as $key => $value) {
            if ($value === '') {
                $this->data[$key] = null;
            }
        }

        // Find employee by emp_id or identity_number
        if (!empty($this->data['emp_id'])) {
            $this->employee = Employee::where('company_id', $companyId)
                ->where('emp_id', $this->data['emp_id'])
                ->first();
        }

        if (!$this->employee && !empty($this->data['identity_number'])) {
            $this->employee = Employee::where('company_id', $companyId)
                ->where('identity_number', $this->data['identity_number'])
                ->first();
        }

        if (!$this->employee) {
            return null; // Skip row if employee not found
        }

        // Calculate amounts
        $type = DeductionType::FIXED;
        $days = null;
        $dailyRate = null;
        $amount = 0;

        if (!empty($this->data['absence_days']) && $this->data['absence_days'] > 0) {
            $type = DeductionType::DAYS;
            $days = (int) $this->data['absence_days'];

            $payroll = $this->employee->payrolls()
                ->where('basic_salary', '>', 0)
                ->latest()
                ->first();

            if ($payroll) {
                $dailyRate = round($payroll->basic_salary / 30, 2);
                $amount = $dailyRate * $days;
            }
        }

        if (!empty($this->data['deduction_amount']) && $this->data['deduction_amount'] > 0) {
            $amount += (float) $this->data['deduction_amount'];
        }

        if ($amount <= 0) {
            return null; // Skip if no amount
        }

        // Create a new Deduction with computed values
        $deduction = new Deduction();
        $deduction->employee_id = $this->employee->id;
        $deduction->company_id = $companyId;
        $deduction->created_by_company_id = $companyId;
        $deduction->payroll_month = $this->data['payroll_month'] ?? now()->format('Y-m');
        $deduction->type = $type;
        $deduction->reason = !empty($this->data['absence_days']) ? DeductionReason::ABSENCE : DeductionReason::OTHER;
        $deduction->days = $days;
        $deduction->daily_rate = $dailyRate;
        $deduction->amount = $amount;
        $deduction->description = $this->data['description'] ?? null;
        $deduction->status = DeductionStatus::APPROVED;

        return $deduction;
    }

    public function afterCreate(): void
    {
        // Handle overtime and additions - update payroll directly
        if ((!empty($this->data['overtime_hours']) && $this->data['overtime_hours'] > 0)
            || (!empty($this->data['addition_amount']) && $this->data['addition_amount'] > 0)) {

            $companyId = $this->options['company_id'];
            $payrollMonth = $this->data['payroll_month'] ?? now()->format('Y-m');

            $payroll = $this->employee->payrolls()
                ->where('company_id', $companyId)
                ->where('payroll_month', $payrollMonth)
                ->first();

            if ($payroll) {
                $updates = [];

                if (!empty($this->data['overtime_hours'])) {
                    $overtimeHours = (float) $this->data['overtime_hours'];
                    $hourlyRate = $payroll->basic_salary / 240;
                    $overtimeAmount = $hourlyRate * 1.5 * $overtimeHours;

                    $updates['overtime_hours'] = ($payroll->overtime_hours ?? 0) + $overtimeHours;
                    $updates['overtime_amount'] = ($payroll->overtime_amount ?? 0) + $overtimeAmount;
                }

                if (!empty($this->data['addition_amount'])) {
                    $updates['other_additions'] = ($payroll->other_additions ?? 0) + (float) $this->data['addition_amount'];
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

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'اكتمل استيراد الخصومات والإضافات: ' . number_format($import->successful_rows) . ' ' . str('سجل')->plural($import->successful_rows) . ' تم استيراده بنجاح.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('سجل')->plural($failedRowsCount) . ' فشل في الاستيراد.';
        }

        return $body;
    }
}
