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

    /**
     * Get a value from normalized row data trying multiple possible column names.
     */
    protected function getField(array $normalized, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (isset($normalized[$key]) && $normalized[$key] !== '' && $normalized[$key] !== null) {
                return $normalized[$key];
            }
        }
        return $default;
    }

    protected function processRow(array $row, int $companyId): string
    {
        // Normalize column names: trim, lowercase, and also create underscore variant
        $normalized = [];
        foreach ($row as $key => $value) {
            $clean = strtolower(trim($key));
            $normalized[$clean] = is_string($value) ? trim($value) : $value;
            // Also store with spaces replaced by underscores for flexible matching
            $underscored = str_replace(' ', '_', $clean);
            if ($underscored !== $clean) {
                $normalized[$underscored] = is_string($value) ? trim($value) : $value;
            }
            // Also store without underscores/spaces for flexible matching
            $nospace = str_replace(['_', ' '], '', $clean);
            if ($nospace !== $clean && $nospace !== $underscored) {
                $normalized[$nospace] = is_string($value) ? trim($value) : $value;
            }
        }

        // Get identity_number (required)
        $identityNumber = $this->getField($normalized, [
            'identity_number', 'identitynumber', 'identity', 'id_number', 'idnumber',
            'رقم الهوية', 'رقم_الهوية', 'الهوية',
        ]);

        if (empty($identityNumber)) {
            throw new \Exception('Missing identity_number / رقم الهوية');
        }

        // Get required fields
        $name = $this->getField($normalized, [
            'name', 'employee_name', 'employeename', 'full_name', 'fullname',
            'الاسم', 'اسم الموظف', 'اسم_الموظف',
        ]);
        $jobTitle = $this->getField($normalized, [
            'job_title', 'jobtitle', 'job', 'title', 'position',
            'المسمى الوظيفي', 'المسمى_الوظيفي', 'الوظيفة', 'المسمي الوظيفي',
        ], 'N/A');
        $nationality = $this->getField($normalized, [
            'nationality', 'الجنسية',
        ], 'N/A');

        if (empty($name)) {
            throw new \Exception('Missing employee name / الاسم');
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

        // emp_id (Employee Number / الرقم الوظيفي)
        $empId = $this->getField($normalized, [
            'emp_id', 'empid', 'employee_id', 'employeeid', 'employee_number', 'employeenumber',
            'emp_no', 'empno', 'emp_number', 'empnumber',
            'الرقم الوظيفي', 'الرقم_الوظيفي', 'رقم الموظف', 'رقم_الموظف',
        ]);
        if ($empId !== null) {
            $employee->emp_id = $empId;
        }

        // department
        $department = $this->getField($normalized, [
            'department', 'dept', 'القسم', 'الإدارة', 'الادارة',
        ]);
        if ($department !== null) {
            $employee->department = $department;
        }

        // location
        $location = $this->getField($normalized, [
            'location', 'الموقع', 'المدينة', 'city',
        ]);
        if ($location !== null) {
            $employee->location = $location;
        }

        // iqama_no (Residence Number / رقم الإقامة) - separate from identity_number
        $iqamaNo = $this->getField($normalized, [
            'iqama_no', 'iqamano', 'iqama', 'iqama_number', 'iqamanumber',
            'residence_no', 'residenceno', 'residence_number', 'residencenumber',
            'رقم الإقامة', 'رقم_الإقامة', 'رقم الاقامة', 'رقم_الاقامة', 'الاقامة', 'الإقامة',
        ]);
        if ($iqamaNo !== null) {
            $employee->iqama_no = $iqamaNo;
        }

        // email
        $email = $this->getField($normalized, [
            'email', 'البريد', 'البريد الإلكتروني', 'بريد',
        ]);
        if ($email !== null) {
            $employee->email = $email;
        }

        // Handle hire_date
        $hireDate = $this->getField($normalized, [
            'hire_date', 'hiredate', 'start_date', 'startdate', 'join_date', 'joindate',
            'تاريخ التعيين', 'تاريخ_التعيين', 'تاريخ الالتحاق', 'تاريخ_الالتحاق',
        ]);
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

