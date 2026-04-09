<?php

namespace App\Filament\Company\Pages;

use App\Enums\CompanyTypes;
use App\Enums\EmployeeAssignedStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeAssigned;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ClientCompaniesListing extends Page implements HasActions
{
    use WithPagination;
    use InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static string $view = 'filament.company.pages.client-companies-listing';

    protected static ?string $navigationLabel = 'العملاء / Clients';

    protected static ?string $title = 'شركات العملاء / Client Companies';

    protected static ?int $navigationSort = 1;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        abort_unless($this->canAccess(), 403);
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        if ($user instanceof Company) {
            return $user->type === CompanyTypes::PROVIDER;
        }

        if ($user instanceof User) {
            return $user->company?->type === CompanyTypes::PROVIDER;
        }

        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getCompaniesProperty(): LengthAwarePaginator
    {
        $user = Filament::auth()->user();
        $providerId = $user instanceof Company ? $user->id : ($user instanceof User ? $user->company_id : null);

        $query = Company::query()
            ->where('type', CompanyTypes::CLIENT)
            ->withCount([
                'used_employees as assigned_employees_count' => function ($q) use ($providerId) {
                    $q->where('employees.company_id', $providerId)
                      ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED);
                },
                'used_employees as pending_employees_count' => function ($q) use ($providerId) {
                    $q->where('employees.company_id', $providerId)
                      ->where('employee_assigned.status', EmployeeAssignedStatus::PENDING);
                },
            ])
            ->orderBy('name');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return $query->paginate(12, pageName: 'page');
    }

    public function updatedSearch(): void
    {
        $this->resetPage('page');
    }

    // ─── Bulk Excel Import ────────────────────────────────────────────────────

    /**
     * Filament Action – opened per company card with the card's ID as argument.
     */
    public function uploadEmployeesAction(): Action
    {
        return Action::make('uploadEmployees')
            ->label('استيراد موظفين / Import Employees')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('success')
            ->modalHeading(function (array $arguments): string {
                $name = Company::find($arguments['companyId'] ?? 0)?->name ?? '';
                return "استيراد موظفين لشركة: {$name}";
            })
            ->modalDescription('ارفع ملف Excel يحتوي على الأعمدة: emp_id, start_date, branch_name')
            ->modalSubmitActionLabel('استيراد / Import')
            ->form([
                Forms\Components\FileUpload::make('excel_file')
                    ->label('ملف Excel / Excel File')
                    ->helperText('emp_id | start_date (YYYY-MM-DD) | branch_name')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'text/csv',
                        'application/octet-stream',
                    ])
                    ->required()
                    ->storeFiles(false),
            ])
            ->action(function (array $data, array $arguments): void {
                $this->processExcelImport((int) ($arguments['companyId'] ?? 0), $data['excel_file']);
            });
    }

    protected function processExcelImport(int $clientCompanyId, mixed $uploadedFile): void
    {
        $user     = Filament::auth()->user();
        $providerId = $user instanceof Company ? $user->id
                    : ($user instanceof User   ? $user->company_id : null);

        if (! $providerId || ! $clientCompanyId) {
            Notification::make()->title('خطأ: بيانات غير مكتملة')->danger()->send();
            return;
        }

        // Load spreadsheet
        try {
            $spreadsheet = IOFactory::load($uploadedFile->getRealPath());
        } catch (\Throwable $e) {
            Notification::make()->title('فشل قراءة الملف / Cannot read file')->danger()->send();
            return;
        }

        $sheet = $spreadsheet->getActiveSheet();
        // toArray(null, true, true, false) → 0-indexed columns, 0-indexed rows
        $allRows = $sheet->toArray(null, true, true, false);

        if (count($allRows) < 2) {
            Notification::make()->title('الملف فارغ / File is empty')->danger()->send();
            return;
        }

        // Map header names → column index
        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $allRows[0]);
        $empIdIdx    = array_search('emp_id',      $headers, true);
        $dateIdx     = array_search('start_date',  $headers, true);
        $branchIdx   = array_search('branch_name', $headers, true);

        if ($empIdIdx === false || $dateIdx === false || $branchIdx === false) {
            Notification::make()
                ->title('أعمدة مفقودة / Missing columns')
                ->body('يجب أن يحتوي الملف على الأعمدة: emp_id, start_date, branch_name')
                ->danger()
                ->send();
            return;
        }

        $processed = 0;
        $skipped   = 0;
        $errorLog  = [];

        foreach (array_slice($allRows, 1) as $rowNum => $row) {
            $empId     = trim((string) ($row[$empIdIdx]  ?? ''));
            $startDate = trim((string) ($row[$dateIdx]   ?? ''));
            $branchName = trim((string) ($row[$branchIdx] ?? ''));

            if ($empId === '') {
                continue; // skip blank rows
            }

            // Find employee owned by this provider
            $employee = Employee::where('company_id', $providerId)
                ->where('emp_id', $empId)
                ->first();

            if (! $employee) {
                $errorLog[] = "صف " . ($rowNum + 2) . ": موظف emp_id='{$empId}' غير موجود";
                $skipped++;
                continue;
            }

            // Find branch belonging to the CLIENT company
            $branch = Branch::where('company_id', $clientCompanyId)
                ->whereRaw('LOWER(name) = ?', [strtolower($branchName)])
                ->first();

            if (! $branch) {
                $errorLog[] = "صف " . ($rowNum + 2) . ": فرع '{$branchName}' غير موجود في شركة العميل";
                $skipped++;
                continue;
            }

            // Parse date (handle Carbon/PhpSpreadsheet numeric dates too)
            try {
                if (is_numeric($startDate)) {
                    $parsed = Carbon::createFromTimestamp(
                        \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp((float) $startDate)
                    )->toDateString();
                } else {
                    $parsed = Carbon::parse($startDate)->toDateString();
                }
            } catch (\Throwable) {
                $errorLog[] = "صف " . ($rowNum + 2) . ": تاريخ غير صالح '{$startDate}'";
                $skipped++;
                continue;
            }

            // Upsert the assignment record
            $assignment = EmployeeAssigned::where('employee_id', $employee->id)
                ->where('company_id', $clientCompanyId)
                ->first();

            if ($assignment) {
                $assignment->update([
                    'status'     => EmployeeAssignedStatus::APPROVED,
                    'start_date' => $parsed,
                    'branch_id'  => $branch->id,
                ]);
            } else {
                EmployeeAssigned::create([
                    'employee_id' => $employee->id,
                    'company_id'  => $clientCompanyId,
                    'status'      => EmployeeAssignedStatus::APPROVED,
                    'start_date'  => $parsed,
                    'branch_id'   => $branch->id,
                ]);
            }

            // Update employee's active assignment pointer
            $employee->update(['company_assigned_id' => $clientCompanyId]);

            // Sync branch_employee pivot
            $employee->branches()->syncWithoutDetaching([
                $branch->id => [
                    'start_date' => $parsed,
                    'is_active'  => true,
                ],
            ]);

            $processed++;
        }

        if ($processed > 0) {
            Notification::make()
                ->title("تم تعيين {$processed} موظف بنجاح" . ($skipped ? " | تم تخطي {$skipped}" : ''))
                ->body($errorLog ? implode("\n", array_slice($errorLog, 0, 5)) : null)
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('لم يتم تعيين أي موظف / No employees assigned')
                ->body(implode("\n", array_slice($errorLog, 0, 5)))
                ->warning()
                ->send();
        }
    }

    /**
     * Generate and stream an Excel template for the provider to fill out.
     * Called via a plain web route (not Livewire) to allow file streaming.
     */
    public static function buildTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bulk Assignment');

        // Headers
        $headers = ['emp_id', 'start_date', 'branch_name'];
        foreach ($headers as $col => $header) {
            $cell = $sheet->getCellByColumnAndRow($col + 1, 1);
            $cell->setValue($header);
        }

        // Style header row
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

        // Sample rows
        $samples = [
            ['EMP001', date('Y-m-d'), 'الفرع الرئيسي / Main Branch'],
            ['EMP002', date('Y-m-d', strtotime('+7 days')), 'فرع الرياض / Riyadh Branch'],
        ];
        foreach ($samples as $rowIdx => $sample) {
            foreach ($sample as $col => $value) {
                $sheet->getCellByColumnAndRow($col + 1, $rowIdx + 2)->setValue($value);
            }
        }

        // Auto-size columns
        foreach (['A', 'B', 'C'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $instructionsSheet = new Worksheet($spreadsheet, 'Instructions');
        $spreadsheet->addSheet($instructionsSheet);

        $instructionsSheet->setCellValue('A1', 'Bulk Assignment Upload Guide');
        $instructionsSheet->setCellValue('A3', 'Required columns in the first sheet:');
        $instructionsSheet->setCellValue('A4', 'emp_id: Employee ID exactly as saved in the system');
        $instructionsSheet->setCellValue('A5', 'start_date: Assignment start date (YYYY-MM-DD)');
        $instructionsSheet->setCellValue('A6', 'branch_name: Branch name in the selected client company');
        $instructionsSheet->setCellValue('A8', 'Important notes:');
        $instructionsSheet->setCellValue('A9', '1) Do not rename the headers in row 1.');
        $instructionsSheet->setCellValue('A10', '2) One employee per row.');
        $instructionsSheet->setCellValue('A11', '3) If emp_id or branch_name is not found, that row will be skipped.');

        $instructionsSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $instructionsSheet->getStyle('A3:A8')->getFont()->setBold(true);
        $instructionsSheet->getColumnDimension('A')->setWidth(110);
        $instructionsSheet->getStyle('A1:A11')->getAlignment()->setWrapText(true);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }
}
