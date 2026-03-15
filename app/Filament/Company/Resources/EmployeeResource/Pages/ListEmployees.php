<?php

namespace App\Filament\Company\Resources\EmployeeResource\Pages;

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
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

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
            $this->getLocationHireImportAction(),
        ];
    }

    protected function getLocationHireImportAction(): Actions\Action
    {
        return Actions\Action::make('importEmployeeLocationHireDate')
            ->label('رفع المواقع وتاريخ التعيين')
            ->icon('heroicon-o-map-pin')
            ->color('warning')
            ->form([
                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('downloadLocationHireTemplate')
                        ->label('⬇️ تحميل نموذج المواقع والتعيين')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(fn () => $this->downloadLocationHireDemo()),
                ]),
                Forms\Components\FileUpload::make('file')
                    ->label('CSV / Excel / ملف CSV أو Excel')
                    ->required()
                    ->acceptedFileTypes([
                        'text/csv',
                        'application/vnd.ms-excel',
                        'text/plain',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/octet-stream',
                    ])
                    ->disk('local')
                    ->directory('imports')
                    ->helperText('Required: Emp.ID or Iqama No. Optional: Location, Hiring Date'),
            ])
            ->action(function (array $data): void {
                $this->processLocationHireImport($data['file']);
            });
    }

    protected function getCustomImportAction(): Actions\Action
    {
        return Actions\Action::make('importEmployees')
            ->label('استيراد الموظفين')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('info')
            ->form([
                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('downloadTemplate')
                        ->label('⬇️ تحميل النموذج / Download Template')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(fn () => $this->downloadEmployeeDemo()),
                ]),
                Forms\Components\FileUpload::make('file')
                    ->label('CSV / Excel / ملف CSV أو Excel')
                    ->required()
                    ->acceptedFileTypes([
                        'text/csv',
                        'application/vnd.ms-excel',
                        'text/plain',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/octet-stream',
                    ])
                    ->disk('local')
                    ->directory('imports')
                    ->helperText('Required: Name, Iqama No. Optional: Emp.ID, Nationality, Hiring Date, Title, Department, Basic Salary, Housing Allowance, Transportation Allowance, Food Allowance, Other Allowance, Fees'),
            ])
            ->action(function (array $data): void {
                $this->processImport($data['file']);
            });
    }

    public function downloadEmployeeDemo(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet()->setTitle('Employee Import');
            $sheet->fromArray([
                [
                    'Emp.ID', 'Name', 'Nationality', 'Iqama No', 'Hiring Date',
                    'Title', 'Department',
                    'Basic Salary', 'Housing Allowance', 'Transportation Allowance',
                    'Food Allowance', 'Other Allowance', 'Fees',
                ],
                [
                    '80132', 'Mohammad Masud Rana', 'Bangladesh', '2464871595', '2021-05-25',
                    'Salesman Assistant', 'Retail Sales',
                    3000, 750, 400, 300, 0, 0,
                ],
                [
                    '60459', 'Mohammad Zahangir Alom', 'Bangladesh', '2496901741', '2021-06-04',
                    'Salesman Assistant', 'Retail Sales',
                    3000, 750, 400, 300, 0, 0,
                ],
            ]);
            $sheet->getStyle('A1:M1')->getFont()->setBold(true);
            foreach (range('A', 'M') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            (new XlsxWriter($spreadsheet))->save('php://output');
        }, 'employee-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function downloadLocationHireDemo(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet()->setTitle('Location Hire Date Update');
            $sheet->fromArray([
                ['Emp.ID', 'Iqama No', 'Location', 'Hiring Date'],
                ['30100', '2464871595', 'Apple - Riyadh Branch', '2021-05-25'],
                ['60459', '2496901741', 'Apple - Jeddah Branch', '2021-06-04'],
            ]);
            $sheet->getStyle('A1:D1')->getFont()->setBold(true);
            foreach (range('A', 'D') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            (new XlsxWriter($spreadsheet))->save('php://output');
        }, 'employee-location-hiredate-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function processLocationHireImport(string $filePath): void
    {
        $user = Filament::auth()->user();
        $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);

        if (! $companyId) {
            Notification::make()
                ->title('خطأ / Error')
                ->body('Could not determine company. Please re-login.')
                ->danger()
                ->send();
            return;
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($filePath)) {
            Notification::make()
                ->title('خطأ / Error')
                ->body('Could not read the uploaded file. Please try again.')
                ->danger()
                ->send();
            return;
        }

        $fullPath = $disk->path($filePath);

        try {
            $updated = 0;
            $failed = 0;
            $errors = [];

            $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

            if (in_array($extension, ['xlsx', 'xls'])) {
                $spreadsheet = IOFactory::load($fullPath);
                $sheet = $spreadsheet->getActiveSheet();
                $allRows = $sheet->toArray(null, true, true, false);

                if (empty($allRows)) {
                    Notification::make()->title('الملف فارغ / Empty file')->danger()->send();
                    return;
                }

                $headers = array_map(fn ($h) => trim((string) ($h ?? '')), $allRows[0]);

                foreach (array_slice($allRows, 1) as $rowIndex => $row) {
                    if (empty(array_filter($row, fn ($v) => $v !== null && $v !== ''))) {
                        continue;
                    }

                    $associative = array_combine($headers, array_pad($row, count($headers), null));

                    try {
                        $didUpdate = $this->processLocationHireRow($associative, $companyId);
                        if ($didUpdate) {
                            $updated++;
                        }
                    } catch (\Throwable $e) {
                        $failed++;
                        if (count($errors) < 10) {
                            $errors[] = 'Row ' . ($rowIndex + 2) . ': ' . $e->getMessage();
                        }
                    }
                }
            } else {
                $csv = Reader::createFromPath($fullPath, 'r');
                $csv->setHeaderOffset(null);
                $allRows = iterator_to_array($csv->getRecords());

                if (empty($allRows)) {
                    Notification::make()->title('الملف فارغ / Empty file')->danger()->send();
                    return;
                }

                $rawHeaders = array_values($allRows[0]);
                $headers = [];
                $seen = [];

                foreach ($rawHeaders as $h) {
                    $key = strtolower(trim((string) $h));
                    if ($key === '') {
                        $key = '_empty';
                    }
                    if (isset($seen[$key])) {
                        $seen[$key]++;
                        $key .= '_' . $seen[$key];
                    } else {
                        $seen[$key] = 0;
                    }
                    $headers[] = $key;
                }

                foreach (array_slice($allRows, 1) as $offset => $row) {
                    $row = array_values($row);
                    $associative = array_combine($headers, array_pad($row, count($headers), null));

                    try {
                        $didUpdate = $this->processLocationHireRow($associative, $companyId);
                        if ($didUpdate) {
                            $updated++;
                        }
                    } catch (\Throwable $e) {
                        $failed++;
                        if (count($errors) < 10) {
                            $errors[] = 'Row ' . ($offset + 2) . ': ' . $e->getMessage();
                        }
                    }
                }
            }

            $body = "تم تحديث {$updated} موظف";
            if ($failed > 0) {
                $body .= "، فشل {$failed} صف";
                if (! empty($errors)) {
                    $body .= "\n" . implode("\n", $errors);
                }
            }

            Notification::make()
                ->title($failed === 0 ? 'تم تحديث المواقع وتاريخ التعيين ✓' : 'اكتمل التحديث مع أخطاء')
                ->body($body)
                ->when($failed === 0, fn ($n) => $n->success())
                ->when($failed > 0 && $updated > 0, fn ($n) => $n->warning())
                ->when($failed > 0 && $updated === 0, fn ($n) => $n->danger())
                ->persistent()
                ->send();
        } catch (\Throwable $e) {
            Log::error('Employee location/hire date import error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('خطأ في التحديث / Update Error')
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

    protected function processLocationHireRow(array $row, int $companyId): bool
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $clean = strtolower(trim((string) $key));
            $val = is_string($value) ? trim($value) : $value;

            $normalized[$clean] = $val;

            $underscored = str_replace(' ', '_', $clean);
            if ($underscored !== $clean) {
                $normalized[$underscored] = $val;
            }

            $nospace = str_replace(['_', ' '], '', $clean);
            if ($nospace !== $clean && $nospace !== $underscored) {
                $normalized[$nospace] = $val;
            }

            $nodot = str_replace(['.', '_', ' '], '', $clean);
            if ($nodot !== $clean && $nodot !== $underscored && $nodot !== $nospace) {
                $normalized[$nodot] = $val;
            }
        }

        $empId = $this->getField($normalized, [
            'emp_id', 'empid', 'emp.id', 'employee_id', 'employeeid',
            'nova emp id', 'nova_emp_id', 'novaempid',
            'الرقم الوظيفي', 'الرقم_الوظيفي', 'رقم الموظف', 'رقم_الموظف',
        ]);

        $iqamaNo = $this->getField($normalized, [
            'iqama no', 'iqama_no', 'iqamano', 'iqama_number', 'iqamanumber',
            'رقم الإقامة', 'رقم_الإقامة', 'رقم الاقامة', 'رقم_الاقامة',
        ]);

        if (empty($empId) && empty($iqamaNo)) {
            throw new \Exception('Missing Emp.ID or Iqama No');
        }

        $employeeQuery = Employee::query()->where('company_id', $companyId);

        $employee = null;
        if (! empty($empId)) {
            $employee = (clone $employeeQuery)->where('emp_id', $empId)->first();
        }
        if (! $employee && ! empty($iqamaNo)) {
            $employee = (clone $employeeQuery)->where('iqama_no', $iqamaNo)->first();
        }

        if (! $employee) {
            throw new \Exception('Employee not found in current company');
        }

        $location = $this->getField($normalized, [
            'location', 'الموقع', 'work location', 'work_location', 'site',
        ]);

        $hiringDate = $this->getField($normalized, [
            'hiring date', 'hiring_date', 'hire_date', 'hiredate',
            'تاريخ التعيين', 'تاريخ_التعيين',
        ]);

        $dirty = false;

        if (is_string($location) && $location !== '') {
            $employee->location = $location;
            $dirty = true;
        }

        if (! empty($hiringDate)) {
            try {
                $employee->hire_date = Carbon::make($hiringDate)?->format('Y-m-d');
                $dirty = true;
            } catch (\Throwable $e) {
                throw new \Exception('Invalid Hiring Date');
            }
        }

        if ($dirty) {
            $employee->save();
        }

        return $dirty;
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
            $imported = 0;
            $updated  = 0;
            $failed   = 0;
            $errors   = [];

            $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

            if (in_array($extension, ['xlsx', 'xls'])) {
                // ── Excel import via PhpSpreadsheet ──────────────────────────
                $spreadsheet = IOFactory::load($fullPath);
                $sheet       = $spreadsheet->getActiveSheet();
                $allRows     = $sheet->toArray(null, true, true, false);

                if (empty($allRows)) {
                    Notification::make()->title('الملف فارغ / Empty file')->danger()->send();
                    return;
                }

                // First row = headers
                $headers = array_map(fn ($h) => trim((string) ($h ?? '')), $allRows[0]);

                foreach (array_slice($allRows, 1) as $rowIndex => $row) {
                    // Skip completely empty rows
                    if (empty(array_filter($row, fn ($v) => $v !== null && $v !== ''))) {
                        continue;
                    }
                    $associative = array_combine($headers, array_pad($row, count($headers), null));
                    try {
                        $result = $this->processRow($associative, $companyId);
                        if ($result === 'created') $imported++;
                        elseif ($result === 'updated') $updated++;
                    } catch (\Throwable $e) {
                        $failed++;
                        if (count($errors) < 10) {
                            $errors[] = 'Row ' . ($rowIndex + 2) . ': ' . $e->getMessage();
                        }
                    }
                }
            } else {
                // ── CSV import via League\Csv ────────────────────────────────
                $csv = Reader::createFromPath($fullPath, 'r');
                $csv->setHeaderOffset(null);
                $allRows = iterator_to_array($csv->getRecords());

                if (empty($allRows)) {
                    Notification::make()->title('الملف فارغ / Empty file')->danger()->send();
                    return;
                }

                // Deduplicate headers (avoid League\Csv duplicate-column crash)
                $rawHeaders = array_values($allRows[0]);
                $headers    = [];
                $seen       = [];
                foreach ($rawHeaders as $h) {
                    $key = strtolower(trim((string) $h));
                    if ($key === '') $key = '_empty';
                    if (isset($seen[$key])) { $seen[$key]++; $key .= '_' . $seen[$key]; }
                    else { $seen[$key] = 0; }
                    $headers[] = $key;
                }

                foreach (array_slice($allRows, 1) as $offset => $row) {
                    $row = array_values($row);
                    $associative = array_combine($headers, array_pad($row, count($headers), null));
                    try {
                        $result = $this->processRow($associative, $companyId);
                        if ($result === 'created') $imported++;
                        elseif ($result === 'updated') $updated++;
                    } catch (\Throwable $e) {
                        $failed++;
                        if (count($errors) < 10) {
                            $errors[] = 'Row ' . ($offset + 2) . ': ' . $e->getMessage();
                        }
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

    /**
     * Parse a numeric value, stripping comma thousand-separators ("1,600" → 1600.0).
     */
    protected function parseNumber($value, float $default = 0): float
    {
        if ($value === null || $value === '') return $default;
        return (float) str_replace(',', '', (string) $value);
    }

    protected function processRow(array $row, int $companyId): string
    {
        // Normalize column names: trim, lowercase, then create underscore / no-space / no-dot variants
        $normalized = [];
        foreach ($row as $key => $value) {
            $clean = strtolower(trim((string) $key));
            $val   = is_string($value) ? trim($value) : $value;

            $normalized[$clean] = $val;

            // spaces → underscores
            $underscored = str_replace(' ', '_', $clean);
            if ($underscored !== $clean) {
                $normalized[$underscored] = $val;
            }

            // remove underscores + spaces
            $nospace = str_replace(['_', ' '], '', $clean);
            if ($nospace !== $clean && $nospace !== $underscored) {
                $normalized[$nospace] = $val;
            }

            // remove dots + underscores + spaces (handles "Emp.ID" → "empid")
            $nodot = str_replace(['.', '_', ' '], '', $clean);
            if ($nodot !== $clean && $nodot !== $underscored && $nodot !== $nospace) {
                $normalized[$nodot] = $val;
            }
        }

        // Get identity_number (required)
        // "Iqama No" column from Excel also counts as identity_number for foreign workers
        $identityNumber = $this->getField($normalized, [
            'identity_number', 'identitynumber', 'identity', 'id_number', 'idnumber',
            'iqama no', 'iqama_no', 'iqamano', 'iqama number', 'iqama_number', 'iqamanumber',
            'رقم الهوية', 'رقم_الهوية', 'الهوية', 'رقم الإقامة', 'رقم_الإقامة',
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

        // emp_id — "Emp.ID" in Excel normalises to 'empid' via dot-removal; "Nova Emp ID" as fallback
        $empId = $this->getField($normalized, [
            'emp_id', 'empid', 'emp.id', 'employee_id', 'employeeid', 'employee_number', 'employeenumber',
            'emp_no', 'empno', 'emp_number', 'empnumber',
            'nova emp id', 'nova_emp_id', 'novaempid',
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

        // Handle hire_date — "Hiring Date" column from the user's Excel
        $hireDate = $this->getField($normalized, [
            'hire_date', 'hiredate', 'hiring_date', 'hiringdate', 'hiring date',
            'start_date', 'startdate', 'join_date', 'joindate',
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

        // Handle salary fields → save/update the base Payroll template (payroll_month = null)
        // Note: "Basic After Increment" preferred over "Basic Salary" as it's the current value.
        // Strip comma thousand-separators ("1,600" → 1600) before casting to float.
        $basicSalary = $this->getField($normalized, [
            'basic after increment', 'basicafterincrement', 'basic_after_increment',
            'basic_salary', 'basicsalary', 'basic salary', 'basic',
            'الراتب الأساسي', 'الراتب_الأساسي', 'الراتب',
        ]);
        $housingAllowance = $this->getField($normalized, [
            'housing_allowance', 'housingallowance', 'housing allowance', 'housing',
            'بدل السكن', 'بدل_السكن',
        ]);
        $transportationAllowance = $this->getField($normalized, [
            'transportation_allowance', 'transportationallowance', 'transportation allowance', 'transportation',
            'بدل المواصلات', 'بدل_المواصلات',
        ]);
        $foodAllowance = $this->getField($normalized, [
            'food_allowance', 'foodallowance', 'food allowance', 'food',
            'بدل الطعام', 'بدل_الطعام',
        ]);
        $otherAllowance = $this->getField($normalized, [
            'other_allowance', 'otherallowance', 'other allowance', 'other allowances',
            'increment 2024 & 2025', 'increment2024&2025', 'increment',
            'بدل أخرى', 'بدلات أخرى',
        ]);
        $fees = $this->getField($normalized, [
            'fees', 'fee', 'monthly fees', 'monthlyfees',
            'الرسوم', 'رسوم',
        ]);

        if ($basicSalary !== null && $this->parseNumber($basicSalary) > 0) {
            Payroll::updateOrCreate(
                [
                    'employee_id'   => $employee->id,
                    'company_id'    => $companyId,
                    'payroll_month' => null,
                ],
                [
                    'basic_salary'              => $this->parseNumber($basicSalary),
                    'housing_allowance'         => $this->parseNumber($housingAllowance),
                    'transportation_allowance'  => $this->parseNumber($transportationAllowance),
                    'food_allowance'            => $this->parseNumber($foodAllowance),
                    'other_allowance'           => $this->parseNumber($otherAllowance),
                    'fees'                      => $this->parseNumber($fees),
                    'status'                    => 'draft',
                ]
            );
        }

        return $isNew ? 'created' : 'updated';
    }
}

