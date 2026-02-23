<?php

namespace App\Filament\Company\Pages;

use App\Enums\AttendanceStatus;
use App\Enums\CompanyTypes;
use App\Enums\DeductionReason;
use App\Enums\DeductionStatus;
use App\Enums\DeductionType;
use App\Models\Company;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\EmployeeAddition;
use App\Models\EmployeeOvertime;
use App\Models\EmployeeTimesheet;
use App\Models\Payroll;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use League\Csv\Reader;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;

class EmployeeEntries extends Page implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';
    protected static ?string $navigationLabel = 'إدخالات الموظفين / Employee Entries';
    protected static ?string $title = 'إدخالات الموظفين / Employee Entries';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.company.pages.employee-entries';

    // Tab state
    public string $activeTab = 'overtime';

    // Search / Filter
    #[Url]
    public ?string $searchEmpId = null;
    public ?int $selectedEmployeeId = null;
    public ?string $selectedEmployeeName = null;
    public ?string $selectedMonth = null;

    // Overtime form  
    public ?float $overtimeHours = null;
    public ?float $overtimeRate = null;
    public ?float $overtimeAmount = null;
    public ?string $overtimeNotes = null;
    public bool $overtimeRecurring = false;

    // Addition form
    public ?float $additionAmount = null;
    public ?string $additionReason = null;
    public ?string $additionDescription = null;
    public bool $additionRecurring = false;

    // Deduction form
    public ?string $deductionType = null;
    public ?string $deductionReason = null;
    public ?string $deductionDescription = null;
    public ?int $deductionDays = null;
    public ?float $deductionDailyRate = null;
    public ?float $deductionAmount = null;
    public bool $deductionRecurring = false;

    // Timesheet data - attendance_data[day] = status
    public array $attendanceData = [];

    // All employees timesheet data: [employeeId => [day => status]]
    public array $allTimesheetData = [];
    public array $allBranchEmployees = [];

    // Bulk upload
    public $bulkFile = null;
    public bool $showBulkUpload = false;

    // Existing entries lists
    public array $existingOvertimes = [];
    public array $existingAdditions = [];
    public array $existingDeductions = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        if ($user instanceof Company) {
            return true;
        }
        if ($user instanceof \App\Models\User) {
            return $user->can('view_any_payroll') || $user->can('view_any_employee');
        }
        return false;
    }

    public function mount(): void
    {
        $this->selectedMonth = now()->format('Y-m');
        $this->loadAllTimesheetData();
    }

    public function getCompanyUser()
    {
        $user = Filament::auth()->user();
        if ($user instanceof Company) {
            return $user;
        }
        if ($user instanceof \App\Models\User && $user->company) {
            return $user->company;
        }
        return null;
    }

    /**
     * Check if current user is a PROVIDER company
     */
    public function isProvider(): bool
    {
        $company = $this->getCompanyUser();
        return $company && $company->type === CompanyTypes::PROVIDER;
    }

    /**
     * Search employee by emp_id
     */
    public function searchEmployee(): void
    {
        if (empty($this->searchEmpId)) {
            Notification::make()->title('أدخل الرقم الوظيفي')->warning()->send();
            return;
        }

        $company = $this->getCompanyUser();
        if (!$company) return;

        $query = Employee::where('emp_id', $this->searchEmpId);

        if ($company->type === CompanyTypes::PROVIDER) {
            $query->where('company_id', $company->id);
        } else {
            $query->whereHas('assigned', fn($q) =>
                $q->where('employee_assigned.company_id', $company->id)
                  ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
            );
        }

        $employee = $query->first();

        if (!$employee) {
            Notification::make()->title('الموظف غير موجود')->body('لم يتم العثور على موظف بهذا الرقم الوظيفي')->danger()->send();
            $this->selectedEmployeeId = null;
            $this->selectedEmployeeName = null;
            return;
        }

        $this->selectedEmployeeId = $employee->id;
        $this->selectedEmployeeName = $employee->name . ' (' . $employee->emp_id . ')';
        
        $this->loadExistingEntries();
        $this->loadTimesheetData();

        Notification::make()->title('تم العثور على الموظف')->body($this->selectedEmployeeName)->success()->send();
    }

    /**
     * Load existing overtime/addition/deduction entries for current employee & month
     */
    public function loadExistingEntries(): void
    {
        if (!$this->selectedEmployeeId || !$this->selectedMonth) return;
        
        $company = $this->getCompanyUser();
        if (!$company) return;

        $this->existingOvertimes = EmployeeOvertime::where('employee_id', $this->selectedEmployeeId)
            ->where('company_id', $company->id)
            ->where('payroll_month', $this->selectedMonth)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        $this->existingAdditions = EmployeeAddition::where('employee_id', $this->selectedEmployeeId)
            ->where('company_id', $company->id)
            ->where('payroll_month', $this->selectedMonth)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        $this->existingDeductions = Deduction::where('employee_id', $this->selectedEmployeeId)
            ->where('company_id', $company->id)
            ->where('payroll_month', $this->selectedMonth)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Load timesheet data for current employee & month
     */
    public function loadTimesheetData(): void
    {
        if (!$this->selectedEmployeeId || !$this->selectedMonth) return;
        
        $company = $this->getCompanyUser();
        if (!$company) return;

        $parts = explode('-', $this->selectedMonth);
        $year = (int) $parts[0];
        $month = (int) $parts[1];

        $timesheet = EmployeeTimesheet::where('employee_id', $this->selectedEmployeeId)
            ->where('company_id', $company->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($timesheet) {
            $this->attendanceData = $timesheet->attendance_data ?? [];
        } else {
            // Initialize with defaults - all days as Present
            $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
            $this->attendanceData = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $this->attendanceData[$d] = 'P';
            }
        }
    }

    /**
     * When month changes, reload data
     */
    public function updatedSelectedMonth(): void
    {
        if ($this->selectedEmployeeId) {
            $this->loadExistingEntries();
            $this->loadTimesheetData();
        }
        $this->loadAllTimesheetData();
    }

    /**
     * Load ALL employees' timesheet data for the selected month
     */
    public function loadAllTimesheetData(): void
    {
        $company = $this->getCompanyUser();
        if (!$company) return;

        $parts = explode('-', $this->selectedMonth ?? now()->format('Y-m'));
        $year = (int) $parts[0];
        $month = (int) $parts[1];
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        if ($company->type === CompanyTypes::PROVIDER) {
            $employees = Employee::where('company_id', $company->id)->orderBy('emp_id')->get();
        } else {
            $employees = Employee::whereHas('assigned', fn($q) =>
                $q->where('employee_assigned.company_id', $company->id)
                  ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
            )->orderBy('emp_id')->get();
        }

        $this->allBranchEmployees = $employees->map(fn($e) => [
            'id' => $e->id,
            'name' => $e->name,
            'emp_id' => $e->emp_id,
        ])->toArray();

        $timesheets = EmployeeTimesheet::where('company_id', $company->id)
            ->where('year', $year)
            ->where('month', $month)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy('employee_id');

        $this->allTimesheetData = [];
        foreach ($employees as $emp) {
            if (isset($timesheets[$emp->id])) {
                $this->allTimesheetData[$emp->id] = $timesheets[$emp->id]->attendance_data ?? [];
            } else {
                $data = [];
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $data[$d] = 'P';
                }
                $this->allTimesheetData[$emp->id] = $data;
            }
        }
    }

    /**
     * Save all employees' timesheets at once
     */
    public function saveAllTimesheets(): void
    {
        $company = $this->getCompanyUser();
        if (!$company) return;

        $parts = explode('-', $this->selectedMonth);
        $year = (int) $parts[0];
        $month = (int) $parts[1];

        foreach ($this->allTimesheetData as $employeeId => $attendance) {
            $timesheet = EmployeeTimesheet::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'company_id' => $company->id,
                    'year' => $year,
                    'month' => $month,
                ],
                [
                    'attendance_data' => $attendance,
                ]
            );
            $timesheet->recalculateTotals();
            $timesheet->save();

            Payroll::syncFromEntries($employeeId, $company->id, $this->selectedMonth);
        }

        Notification::make()->title('تم حفظ جميع التايم شيتات بنجاح / All timesheets saved')->success()->send();
    }

    /**
     * Get summary for all employees' timesheets
     */
    public function getEmployeeTimesheetSummary(int $employeeId): array
    {
        $counts = ['P' => 0, 'A' => 0, 'L' => 0, 'O' => 0, 'X' => 0];
        $data = $this->allTimesheetData[$employeeId] ?? [];
        foreach ($data as $status) {
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }
        return $counts;
    }

    public function getDaysInMonth(): int
    {
        $parts = explode('-', $this->selectedMonth ?? now()->format('Y-m'));
        return Carbon::create((int) $parts[0], (int) $parts[1], 1)->daysInMonth;
    }

    public function getMonthLabel(): string
    {
        return Carbon::parse($this->selectedMonth . '-01')->format('F Y');
    }

    // ========================
    // OVERTIME ACTIONS
    // ========================
    
    public function saveOvertime(): void
    {
        if (!$this->selectedEmployeeId) {
            Notification::make()->title('اختر موظف أولاً')->warning()->send();
            return;
        }

        if (!$this->overtimeHours || $this->overtimeHours <= 0) {
            Notification::make()->title('أدخل عدد الساعات')->warning()->send();
            return;
        }

        $company = $this->getCompanyUser();

        $amount = $this->overtimeAmount;
        if (!$amount && $this->overtimeHours && $this->overtimeRate) {
            $amount = $this->overtimeHours * $this->overtimeRate;
        }

        EmployeeOvertime::create([
            'employee_id' => $this->selectedEmployeeId,
            'company_id' => $company->id,
            'payroll_month' => $this->selectedMonth,
            'hours' => $this->overtimeHours,
            'rate_per_hour' => $this->overtimeRate ?? 0,
            'amount' => $amount ?? 0,
            'notes' => $this->overtimeNotes,
            'is_recurring' => $this->overtimeRecurring,
            'status' => 'pending',
            'created_by_company_id' => $company->id,
        ]);

        $this->resetOvertimeForm();
        $this->loadExistingEntries();
        Payroll::syncFromEntries($this->selectedEmployeeId, $company->id, $this->selectedMonth);
        Notification::make()->title('تم إضافة ساعات العمل الإضافي بنجاح')->success()->send();
    }

    public function deleteOvertime(int $id): void
    {
        EmployeeOvertime::where('id', $id)->where('status', 'pending')->delete();
        $this->loadExistingEntries();
        $company = $this->getCompanyUser();
        Payroll::syncFromEntries($this->selectedEmployeeId, $company->id, $this->selectedMonth);
        Notification::make()->title('تم الحذف')->success()->send();
    }

    protected function resetOvertimeForm(): void
    {
        $this->overtimeHours = null;
        $this->overtimeRate = null;
        $this->overtimeAmount = null;
        $this->overtimeNotes = null;
        $this->overtimeRecurring = false;
    }

    // ========================
    // ADDITION ACTIONS
    // ========================
    
    public function saveAddition(): void
    {
        if (!$this->selectedEmployeeId) {
            Notification::make()->title('اختر موظف أولاً')->warning()->send();
            return;
        }

        if (!$this->additionAmount || $this->additionAmount <= 0) {
            Notification::make()->title('أدخل المبلغ')->warning()->send();
            return;
        }

        $company = $this->getCompanyUser();

        EmployeeAddition::create([
            'employee_id' => $this->selectedEmployeeId,
            'company_id' => $company->id,
            'payroll_month' => $this->selectedMonth,
            'amount' => $this->additionAmount,
            'reason' => $this->additionReason,
            'description' => $this->additionDescription,
            'is_recurring' => $this->additionRecurring,
            'status' => 'pending',
            'created_by_company_id' => $company->id,
        ]);

        $this->resetAdditionForm();
        $this->loadExistingEntries();
        Payroll::syncFromEntries($this->selectedEmployeeId, $company->id, $this->selectedMonth);
        Notification::make()->title('تم إضافة المبلغ الإضافي بنجاح')->success()->send();
    }

    public function deleteAddition(int $id): void
    {
        EmployeeAddition::where('id', $id)->where('status', 'pending')->delete();
        $this->loadExistingEntries();
        $company = $this->getCompanyUser();
        Payroll::syncFromEntries($this->selectedEmployeeId, $company->id, $this->selectedMonth);
        Notification::make()->title('تم الحذف')->success()->send();
    }

    protected function resetAdditionForm(): void
    {
        $this->additionAmount = null;
        $this->additionReason = null;
        $this->additionDescription = null;
        $this->additionRecurring = false;
    }

    // ========================
    // DEDUCTION ACTIONS
    // ========================
    
    public function saveDeduction(): void
    {
        if (!$this->selectedEmployeeId) {
            Notification::make()->title('اختر موظف أولاً')->warning()->send();
            return;
        }

        if (!$this->deductionAmount || $this->deductionAmount <= 0) {
            // Try to calculate from days
            if ($this->deductionType === 'days' && $this->deductionDays && $this->deductionDailyRate) {
                $this->deductionAmount = $this->deductionDays * $this->deductionDailyRate;
            } else {
                Notification::make()->title('أدخل مبلغ الخصم')->warning()->send();
                return;
            }
        }

        $company = $this->getCompanyUser();

        Deduction::create([
            'employee_id' => $this->selectedEmployeeId,
            'company_id' => $company->id,
            'payroll_month' => $this->selectedMonth,
            'type' => $this->deductionType ?? 'fixed',
            'reason' => $this->deductionReason ?? 'other',
            'description' => $this->deductionDescription,
            'days' => $this->deductionDays ?? 0,
            'daily_rate' => $this->deductionDailyRate ?? 0,
            'amount' => $this->deductionAmount,
            'status' => 'pending',
            'is_recurring' => $this->deductionRecurring,
            'created_by_company_id' => $company->id,
        ]);

        $this->resetDeductionForm();
        $this->loadExistingEntries();
        Payroll::syncFromEntries($this->selectedEmployeeId, $company->id, $this->selectedMonth);
        Notification::make()->title('تم إضافة الخصم بنجاح')->success()->send();
    }

    public function deleteDeduction(int $id): void
    {
        Deduction::where('id', $id)->where('status', 'pending')->delete();
        $this->loadExistingEntries();
        $company = $this->getCompanyUser();
        Payroll::syncFromEntries($this->selectedEmployeeId, $company->id, $this->selectedMonth);
        Notification::make()->title('تم الحذف')->success()->send();
    }

    protected function resetDeductionForm(): void
    {
        $this->deductionType = null;
        $this->deductionReason = null;
        $this->deductionDescription = null;
        $this->deductionDays = null;
        $this->deductionDailyRate = null;
        $this->deductionAmount = null;
        $this->deductionRecurring = false;
    }

    // ========================
    // TIMESHEET ACTIONS
    // ========================
    
    public function saveTimesheet(): void
    {
        if (!$this->selectedEmployeeId) {
            Notification::make()->title('اختر موظف أولاً')->warning()->send();
            return;
        }

        $company = $this->getCompanyUser();
        $parts = explode('-', $this->selectedMonth);
        $year = (int) $parts[0];
        $month = (int) $parts[1];

        $timesheet = EmployeeTimesheet::updateOrCreate(
            [
                'employee_id' => $this->selectedEmployeeId,
                'company_id' => $company->id,
                'year' => $year,
                'month' => $month,
            ],
            [
                'attendance_data' => $this->attendanceData,
            ]
        );

        // Recalculate totals
        $timesheet->recalculateTotals();
        $timesheet->save();

        // Sync to payroll
        Payroll::syncFromEntries($this->selectedEmployeeId, $company->id, $this->selectedMonth);

        Notification::make()->title('تم حفظ التايم شيت بنجاح')->success()->send();
    }

    /**
     * Get timesheet summary counts
     */
    public function getTimesheetSummary(): array
    {
        $counts = [
            'P' => 0, 'A' => 0, 'L' => 0, 'O' => 0, 'X' => 0,
        ];

        foreach ($this->attendanceData as $status) {
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }

        return $counts;
    }

    /**
     * Get attendance status options for dropdowns
     */
    public function getAttendanceOptions(): array
    {
        return AttendanceStatus::getTranslatedEnum();
    }

    /**
     * Get deduction type options
     */
    public function getDeductionTypeOptions(): array
    {
        return DeductionType::getTranslatedEnum();
    }

    /**
     * Get deduction reason options
     */
    public function getDeductionReasonOptions(): array
    {
        return DeductionReason::getTranslatedEnum();
    }

    /**
     * Get all employees for dropdown
     */
    public function getEmployees(): array
    {
        $company = $this->getCompanyUser();
        if (!$company) return [];

        if ($company->type === CompanyTypes::PROVIDER) {
            return Employee::where('company_id', $company->id)
                ->orderBy('emp_id')
                ->get()
                ->mapWithKeys(fn($e) => [$e->id => $e->emp_id . ' - ' . $e->name])
                ->toArray();
        }

        return Employee::whereHas('assigned', fn($q) =>
            $q->where('employee_assigned.company_id', $company->id)
              ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
        )
        ->orderBy('emp_id')
        ->get()
        ->mapWithKeys(fn($e) => [$e->id => $e->emp_id . ' - ' . $e->name])
        ->toArray();
    }

    /**
     * Select employee from dropdown
     */
    public function selectEmployee(int $employeeId): void
    {
        $employee = Employee::find($employeeId);
        if ($employee) {
            $this->selectedEmployeeId = $employee->id;
            $this->selectedEmployeeName = $employee->name . ' (' . $employee->emp_id . ')';
            $this->searchEmpId = $employee->emp_id;
            $this->loadExistingEntries();
            $this->loadTimesheetData();
        }
    }

    // ========================
    // BULK UPLOAD
    // ========================

    public function toggleBulkUpload(): void
    {
        $this->showBulkUpload = !$this->showBulkUpload;
        $this->bulkFile = null;
    }

    public function importBulkEntries(): void
    {
        if (!$this->bulkFile) {
            Notification::make()->title('يرجى رفع ملف CSV')->warning()->send();
            return;
        }

        $company = $this->getCompanyUser();
        if (!$company) return;

        try {
            $filePath = $this->bulkFile->getRealPath();
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $records = $csv->getRecords();

            $created = 0;
            $skipped = 0;
            $errors = [];

            foreach ($records as $rowNum => $row) {
                try {
                    $normalized = [];
                    foreach ($row as $key => $value) {
                        $clean = strtolower(trim($key));
                        $normalized[$clean] = is_string($value) ? trim($value) : $value;
                    }

                    $empId = $normalized['emp_id'] ?? $normalized['employee_id'] ?? $normalized['رقم الموظف'] ?? null;
                    if (!$empId) { $skipped++; continue; }

                    $employee = Employee::where('emp_id', $empId)->first();
                    if (!$employee) { $errors[] = "Row {$rowNum}: Employee {$empId} not found"; continue; }

                    $month = $normalized['month'] ?? $normalized['الشهر'] ?? $this->selectedMonth;

                    if ($this->activeTab === 'overtime') {
                        $hours = floatval($normalized['hours'] ?? $normalized['الساعات'] ?? 0);
                        $rate = floatval($normalized['rate'] ?? $normalized['السعر'] ?? 0);
                        $amount = floatval($normalized['amount'] ?? $normalized['المبلغ'] ?? ($hours * $rate));
                        if ($hours <= 0 && $amount <= 0) { $skipped++; continue; }

                        EmployeeOvertime::create([
                            'employee_id' => $employee->id,
                            'company_id' => $company->id,
                            'payroll_month' => $month,
                            'hours' => $hours,
                            'rate_per_hour' => $rate,
                            'amount' => $amount,
                            'notes' => $normalized['notes'] ?? $normalized['ملاحظات'] ?? null,
                            'is_recurring' => false,
                            'status' => 'pending',
                            'created_by_company_id' => $company->id,
                        ]);
                        $created++;
                    } elseif ($this->activeTab === 'additions') {
                        $amount = floatval($normalized['amount'] ?? $normalized['المبلغ'] ?? 0);
                        if ($amount <= 0) { $skipped++; continue; }

                        EmployeeAddition::create([
                            'employee_id' => $employee->id,
                            'company_id' => $company->id,
                            'payroll_month' => $month,
                            'amount' => $amount,
                            'reason' => $normalized['reason'] ?? $normalized['السبب'] ?? null,
                            'description' => $normalized['description'] ?? $normalized['الوصف'] ?? null,
                            'is_recurring' => false,
                            'status' => 'pending',
                            'created_by_company_id' => $company->id,
                        ]);
                        $created++;
                    } elseif ($this->activeTab === 'deductions') {
                        $amount = floatval($normalized['amount'] ?? $normalized['المبلغ'] ?? 0);
                        if ($amount <= 0) { $skipped++; continue; }

                        Deduction::create([
                            'employee_id' => $employee->id,
                            'company_id' => $company->id,
                            'payroll_month' => $month,
                            'type' => $normalized['type'] ?? $normalized['النوع'] ?? 'fixed',
                            'reason' => $normalized['reason'] ?? $normalized['السبب'] ?? 'other',
                            'description' => $normalized['description'] ?? $normalized['الوصف'] ?? null,
                            'days' => intval($normalized['days'] ?? $normalized['الأيام'] ?? 0),
                            'daily_rate' => floatval($normalized['daily_rate'] ?? 0),
                            'amount' => $amount,
                            'status' => 'pending',
                            'is_recurring' => false,
                            'created_by_company_id' => $company->id,
                        ]);
                        $created++;
                    }

                    Payroll::syncFromEntries($employee->id, $company->id, $month);
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNum}: " . $e->getMessage();
                }
            }

            $this->showBulkUpload = false;
            $this->bulkFile = null;

            if ($this->selectedEmployeeId) {
                $this->loadExistingEntries();
            }

            $msg = "تم إنشاء {$created} سجل";
            if ($skipped > 0) $msg .= " | تم تخطي {$skipped}";
            if (count($errors) > 0) $msg .= " | أخطاء: " . count($errors);

            Notification::make()
                ->title('تم استيراد الملف')
                ->body($msg)
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في استيراد الملف')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
