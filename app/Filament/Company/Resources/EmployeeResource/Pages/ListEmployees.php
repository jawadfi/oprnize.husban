<?php

namespace App\Filament\Company\Resources\EmployeeResource\Pages;

use App\Enums\PayrollStatus;
use App\Models\Employee;
use App\Models\Payroll;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;

use App\Filament\Company\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected static ?string $title = 'Employee and provider services';

    public function getTabs(): array
    {
        $user = Filament::auth()->user();
        $company = $user instanceof \App\Models\Company ? $user : ($user instanceof \App\Models\User ? $user->company : null);

        if (!$company) {
            return [];
        }

        return [
            'available' => Tab::make()
                ->badge(fn () => $company->original_employees()->byStatus(\App\Enums\EmployeeStatusStatus::AVAILABLE)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->byStatus(\App\Enums\EmployeeStatusStatus::AVAILABLE)),

            'in_service' => Tab::make()
                ->badge(fn () => $company->original_employees()->byStatus(\App\Enums\EmployeeStatusStatus::IN_SERVICE)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->byStatus(\App\Enums\EmployeeStatusStatus::IN_SERVICE)),

            'ended_service' => Tab::make()
                ->badge(fn () => $company->original_employees()->byStatus(\App\Enums\EmployeeStatusStatus::ENDED_SERVICE)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->byStatus(\App\Enums\EmployeeStatusStatus::ENDED_SERVICE)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            $this->getCustomImportAction(),
        ];
    }

    protected function getCustomImportAction(): Actions\Action
    {
        return Actions\Action::make('importEmployees')
            ->label('استيراد الموظفين')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('info')
            ->form([
                Forms\Components\FileUpload::make('file')
                    ->label('CSV File / ملف CSV')
                    ->required()
                    ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'text/plain'])
                    ->disk('local')
                    ->directory('imports')
                    ->helperText('Upload a CSV file with columns: name, job_title, identity_number, nationality (required). Optional: emp_id, department, location, iqama_no, hire_date, email'),
            ])
            ->action(function (array $data): void {
                $this->processImport($data['file']);
            });
    }

    protected function processImport(string $filePath): void
    {
        $user = Filament::auth()->user();
        $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);

        if (!$companyId) {
            Notification::make()
                ->title('خطأ / Error')
                ->body('Could not determine company. Please re-login.')
                ->danger()
                ->send();
            return;
        }

        // Get the full filesystem path from the Storage disk
        $disk = Storage::disk('local');

        if (!$disk->exists($filePath)) {
            Notification::make()
                ->title('خطأ / Error')
                ->body('Could not read the uploaded file. Please try again.')
                ->danger()
                ->send();
            return;
        }

        $fullPath = $disk->path($filePath);

        try {
            $csv = Reader::createFromPath($fullPath, 'r');
            $csv->setHeaderOffset(0);

            $records = $csv->getRecords();
            $imported = 0;
            $updated = 0;
            $failed = 0;
            $errors = [];

            foreach ($records as $offset => $row) {
                try {
                    $result = $this->processRow($row, $companyId);
                    if ($result === 'created') {
                        $imported++;
                    } elseif ($result === 'updated') {
                        $updated++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    if (count($errors) < 5) {
                        $rowNum = $offset + 2; // CSV rows are 1-indexed + header
                        $errors[] = "Row {$rowNum}: {$e->getMessage()}";
                    }
                }
            }

            $body = "تم استيراد {$imported} موظف جديد";
            if ($updated > 0) {
                $body .= "، تم تحديث {$updated} موظف";
            }
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
            Log::error('Employee import error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('خطأ في الاستيراد / Import Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            // Clean up uploaded file
            try {
                $disk->delete($filePath);
            } catch (\Throwable $e) {
                // Ignore cleanup errors
            }
        }
    }

    protected function processRow(array $row, int $companyId): string
    {
        // Normalize column names (trim whitespace, lowercase for matching)
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[strtolower(trim($key))] = is_string($value) ? trim($value) : $value;
        }

        // Get identity_number (required)
        $identityNumber = $normalized['identity_number'] ?? $normalized['identitynumber'] ?? $normalized['identity'] ?? $normalized['رقم الهوية'] ?? null;

        if (empty($identityNumber)) {
            throw new \Exception('Missing identity_number');
        }

        // Get required fields
        $name = $normalized['name'] ?? $normalized['الاسم'] ?? $normalized['employee_name'] ?? $normalized['اسم الموظف'] ?? null;
        $jobTitle = $normalized['job_title'] ?? $normalized['jobtitle'] ?? $normalized['المسمى الوظيفي'] ?? $normalized['الوظيفة'] ?? 'N/A';
        $nationality = $normalized['nationality'] ?? $normalized['الجنسية'] ?? 'N/A';

        if (empty($name)) {
            throw new \Exception('Missing employee name');
        }

        // Find or create employee
        $employee = Employee::firstOrNew([
            'identity_number' => $identityNumber,
            'company_id' => $companyId,
        ]);

        $isNew = !$employee->exists;

        // Set fields
        $employee->name = $name;
        $employee->job_title = $jobTitle;
        $employee->nationality = $nationality;
        $employee->company_id = $companyId;

        // Optional fields
        if (!empty($normalized['emp_id'] ?? $normalized['employee_id'] ?? $normalized['رقم الموظف'] ?? null)) {
            $employee->emp_id = $normalized['emp_id'] ?? $normalized['employee_id'] ?? $normalized['رقم الموظف'];
        }

        if (!empty($normalized['department'] ?? $normalized['القسم'] ?? null)) {
            $employee->department = $normalized['department'] ?? $normalized['القسم'];
        }

        if (!empty($normalized['location'] ?? $normalized['الموقع'] ?? null)) {
            $employee->location = $normalized['location'] ?? $normalized['الموقع'];
        }

        if (!empty($normalized['iqama_no'] ?? $normalized['رقم الإقامة'] ?? null)) {
            $employee->iqama_no = $normalized['iqama_no'] ?? $normalized['رقم الإقامة'];
        }

        if (!empty($normalized['email'] ?? $normalized['البريد'] ?? null)) {
            $employee->email = $normalized['email'] ?? $normalized['البريد'];
        }

        // Handle hire_date
        $hireDate = $normalized['hire_date'] ?? $normalized['hiredate'] ?? $normalized['تاريخ التعيين'] ?? null;
        if (!empty($hireDate)) {
            try {
                $employee->hire_date = Carbon::make($hireDate)?->format('Y-m-d');
            } catch (\Throwable $e) {
                // Skip invalid date
            }
        }

        $employee->save();

        // Create payroll record for new employees
        if ($isNew) {
            $existingPayroll = Payroll::where('employee_id', $employee->id)
                ->where('company_id', $companyId)
                ->where('payroll_month', now()->format('Y-m'))
                ->exists();

            if (!$existingPayroll) {
                Payroll::create([
                    'employee_id' => $employee->id,
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

        return $isNew ? 'created' : 'updated';
    }
}

