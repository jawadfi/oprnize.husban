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
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;

class EmployeeEntries extends Page implements HasForms
{
    use InteractsWithForms;

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
            // Initialize with defaults  
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $this->attendanceData = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $date = Carbon::create($year, $month, $d);
                // Default: weekends (Friday/Saturday) = DO, rest = P
                if ($date->isFriday() || $date->isSaturday()) {
                    $this->attendanceData[$d] = 'DO';
                } else {
                    $this->attendanceData[$d] = 'P';
                }
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
    }

    public function getDaysInMonth(): int
    {
        $parts = explode('-', $this->selectedMonth ?? now()->format('Y-m'));
        return cal_days_in_month(CAL_GREGORIAN, (int) $parts[1], (int) $parts[0]);
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
        Notification::make()->title('تم إضافة ساعات العمل الإضافي بنجاح')->success()->send();
    }

    public function deleteOvertime(int $id): void
    {
        EmployeeOvertime::where('id', $id)->where('status', 'pending')->delete();
        $this->loadExistingEntries();
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
        Notification::make()->title('تم إضافة المبلغ الإضافي بنجاح')->success()->send();
    }

    public function deleteAddition(int $id): void
    {
        EmployeeAddition::where('id', $id)->where('status', 'pending')->delete();
        $this->loadExistingEntries();
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
        Notification::make()->title('تم إضافة الخصم بنجاح')->success()->send();
    }

    public function deleteDeduction(int $id): void
    {
        Deduction::where('id', $id)->where('status', 'pending')->delete();
        $this->loadExistingEntries();
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

        Notification::make()->title('تم حفظ التايم شيت بنجاح')->success()->send();
    }

    /**
     * Get timesheet summary counts
     */
    public function getTimesheetSummary(): array
    {
        $counts = [
            'P' => 0, 'A' => 0, 'DO' => 0, 'L' => 0,
            'AL' => 0, 'UL' => 0, 'SL' => 0, 'FR' => 0,
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
}
