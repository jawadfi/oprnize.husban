<?php

namespace App\Filament\Company\Resources\DeductionResource\Pages;

use App\Enums\DeductionReason;
use App\Enums\DeductionStatus;
use App\Enums\DeductionType;
use App\Filament\Company\Resources\DeductionResource;
use App\Models\Deduction;
use App\Models\Employee;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class ListDeductions extends ListRecords
{
    protected static string $resource = DeductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('importDeductions')
                ->label('استيراد الخصومات والإضافات')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('CSV File / ملف CSV')
                        ->required()
                        ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'text/plain'])
                        ->disk('local')
                        ->directory('imports')
                        ->helperText('Columns: emp_id or identity_number (required), payroll_month, absence_days, deduction_amount, overtime_hours, addition_amount, description'),
                ])
                ->action(function (array $data): void {
                    $this->processDeductionImport($data['file']);
                }),
            Actions\CreateAction::make(),
        ];
    }

    protected function processDeductionImport(string $filePath): void
    {
        $user = Filament::auth()->user();
        $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);

        if (!$companyId) {
            Notification::make()->title('خطأ / Error')->body('Could not determine company.')->danger()->send();
            return;
        }

        $disk = Storage::disk('local');
        if (!$disk->exists($filePath)) {
            Notification::make()->title('خطأ / Error')->body('Could not read the uploaded file.')->danger()->send();
            return;
        }
        $fullPath = $disk->path($filePath);

        try {
            $csv = Reader::createFromPath($fullPath, 'r');
            $csv->setHeaderOffset(0);

            $records = $csv->getRecords();
            $imported = 0;
            $failed = 0;
            $errors = [];

            foreach ($records as $offset => $row) {
                try {
                    $this->processDeductionRow($row, $companyId);
                    $imported++;
                } catch (\Throwable $e) {
                    $failed++;
                    if (count($errors) < 5) {
                        $rowNum = $offset + 2;
                        $errors[] = "Row {$rowNum}: {$e->getMessage()}";
                    }
                }
            }

            $body = "تم استيراد {$imported} خصم/إضافة";
            if ($failed > 0) {
                $body .= "، فشل {$failed} صف";
                if (!empty($errors)) {
                    $body .= "\n" . implode("\n", $errors);
                }
            }

            Notification::make()
                ->title($failed === 0 ? 'تم الاستيراد بنجاح ✓' : 'اكتمل الاستيراد مع أخطاء')
                ->body($body)
                ->when($failed === 0, fn ($n) => $n->success())
                ->when($failed > 0 && $imported > 0, fn ($n) => $n->warning())
                ->when($failed > 0 && $imported === 0, fn ($n) => $n->danger())
                ->persistent()
                ->send();

        } catch (\Throwable $e) {
            Log::error('Deduction import error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('خطأ في الاستيراد / Import Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            try {
                $disk->delete($filePath);
            } catch (\Throwable $e) {
                // Ignore cleanup errors
            }
        }
    }

    protected function processDeductionRow(array $row, int $companyId): void
    {
        // Normalize column names
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[strtolower(trim($key))] = is_string($value) ? trim($value) : $value;
        }

        // Find employee
        $employee = null;
        $empId = $normalized['emp_id'] ?? $normalized['employee_id'] ?? $normalized['رقم الموظف'] ?? null;
        $identityNumber = $normalized['identity_number'] ?? $normalized['رقم الهوية'] ?? null;

        if (!empty($empId)) {
            $employee = Employee::where('company_id', $companyId)->where('emp_id', $empId)->first();
        }
        if (!$employee && !empty($identityNumber)) {
            $employee = Employee::where('company_id', $companyId)->where('identity_number', $identityNumber)->first();
        }

        if (!$employee) {
            throw new \Exception('Employee not found: ' . ($empId ?: $identityNumber ?: 'unknown'));
        }

        $payrollMonth = $normalized['payroll_month'] ?? $normalized['شهر_الراتب'] ?? now()->format('Y-m');

        // Handle absence deduction
        $absenceDays = !empty($normalized['absence_days'] ?? $normalized['أيام_الغياب'] ?? null) ? (int)($normalized['absence_days'] ?? $normalized['أيام_الغياب']) : 0;
        $deductionAmount = !empty($normalized['deduction_amount'] ?? $normalized['مبلغ_الخصم'] ?? null) ? (float)($normalized['deduction_amount'] ?? $normalized['مبلغ_الخصم']) : 0;

        $type = DeductionType::FIXED;
        $days = null;
        $dailyRate = null;
        $amount = 0;

        if ($absenceDays > 0) {
            $type = DeductionType::DAYS;
            $days = $absenceDays;

            $payroll = $employee->payrolls()->where('basic_salary', '>', 0)->latest()->first();
            if ($payroll) {
                $dailyRate = round($payroll->basic_salary / 30, 2);
                $amount = $dailyRate * $days;
            }
        }

        if ($deductionAmount > 0) {
            $amount += $deductionAmount;
        }

        if ($amount > 0) {
            Deduction::create([
                'employee_id' => $employee->id,
                'company_id' => $companyId,
                'created_by_company_id' => $companyId,
                'payroll_month' => $payrollMonth,
                'type' => $type,
                'reason' => $absenceDays > 0 ? DeductionReason::ABSENCE : DeductionReason::OTHER,
                'days' => $days,
                'daily_rate' => $dailyRate,
                'amount' => $amount,
                'description' => $normalized['description'] ?? $normalized['ملاحظات'] ?? null,
                'status' => DeductionStatus::APPROVED,
            ]);
        }

        // Handle overtime and additions → update payroll directly
        $overtimeHours = !empty($normalized['overtime_hours'] ?? $normalized['ساعات_الإضافي'] ?? null) ? (float)($normalized['overtime_hours'] ?? $normalized['ساعات_الإضافي']) : 0;
        $additionAmount = !empty($normalized['addition_amount'] ?? $normalized['مبلغ_الإضافة'] ?? null) ? (float)($normalized['addition_amount'] ?? $normalized['مبلغ_الإضافة']) : 0;

        if ($overtimeHours > 0 || $additionAmount > 0) {
            $payroll = $employee->payrolls()
                ->where('company_id', $companyId)
                ->where('payroll_month', $payrollMonth)
                ->first();

            if ($payroll) {
                $updates = [];
                if ($overtimeHours > 0) {
                    $hourlyRate = $payroll->basic_salary / 240;
                    $updates['overtime_hours'] = ($payroll->overtime_hours ?? 0) + $overtimeHours;
                    $updates['overtime_amount'] = ($payroll->overtime_amount ?? 0) + ($hourlyRate * 1.5 * $overtimeHours);
                }
                if ($additionAmount > 0) {
                    $updates['other_additions'] = ($payroll->other_additions ?? 0) + $additionAmount;
                }
                $payroll->update($updates);
            }
        }

        if ($amount <= 0 && $overtimeHours <= 0 && $additionAmount <= 0) {
            throw new \Exception('No deduction, overtime, or addition amounts found');
        }
    }
}
