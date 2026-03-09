<?php

namespace App\Filament\Company\Resources\EmployeeResource\Pages;

use App\Models\Employee;
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
                    ->helperText('Upload a CSV or Excel (.xlsx/.xls) file with columns: name, job_title, identity_number, nationality (required). Optional: emp_id, department, hire_date, email'),
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

        return $isNew ? 'created' : 'updated';
    }
}

