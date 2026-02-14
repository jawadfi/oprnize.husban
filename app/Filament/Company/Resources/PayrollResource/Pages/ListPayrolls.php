<?php

namespace App\Filament\Company\Resources\PayrollResource\Pages;

use App\Enums\CompanyTypes;
use App\Enums\EmployeeAssignedStatus;
use App\Filament\Company\Resources\PayrollResource;
use App\Filament\Company\Widgets\DeductionStatsWidget;
use App\Filament\Company\Widgets\EmployeeStatsWidget;
use App\Filament\Company\Widgets\LeaveRequestStatsWidget;
use App\Filament\Company\Widgets\PayrollStatsWidget;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListPayrolls extends ListRecords
{
    protected static string $resource = PayrollResource::class;

    protected static string $view = 'filament.company.resources.payroll-resource.pages.list-payrolls';

    #[Url]
    public ?string $selectedMonth = null;

    #[Url]
    public ?string $selectedCompany = 'all';

    public function mount(): void
    {
        parent::mount();
        
        if (!$this->selectedMonth) {
            $this->selectedMonth = now()->format('Y-m');
        }
    }

    /**
     * Check if current user is a PROVIDER company
     */
    public function isProvider(): bool
    {
        $user = Filament::auth()->user();
        return $user instanceof Company && $user->type === CompanyTypes::PROVIDER;
    }

    /**
     * Get list of client companies that have assigned employees from this provider
     * Plus "In-House" option for employees not assigned to any company
     */
    public function getCompanyOptions(): array
    {
        $user = Filament::auth()->user();
        if (!$this->isProvider()) return [];

        $options = [
            'all' => 'All Employees - جميع الموظفين',
            'in_house' => 'In-House - موظفين داخليين',
        ];

        // Get client companies that have approved assigned employees from this provider
        $clientCompanies = Company::where('type', CompanyTypes::CLIENT)
            ->whereHas('used_employees', function ($q) use ($user) {
                $q->where('employees.company_id', $user->id)
                  ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED);
            })
            ->get();

        foreach ($clientCompanies as $company) {
            $options[$company->id] = $company->name;
        }

        return $options;
    }

    /**
     * Called when company filter changes
     */
    public function updatedSelectedCompany(): void
    {
        $this->resetTable();
    }

    public function getTitle(): string
    {
        $date = Carbon::parse($this->selectedMonth . '-01');
        return 'Payroll - ' . $date->format('F Y');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EmployeeStatsWidget::class,
            PayrollStatsWidget::class,
            DeductionStatsWidget::class,
            LeaveRequestStatsWidget::class,
        ];
    }

    public function exportPayroll()
    {
        // Get the current filtered/searched payroll records
        $payrolls = $this->getTableQuery()->with('employee')->get();
        
        if ($payrolls->isEmpty()) {
            Notification::make()
                ->title('No data to export')
                ->warning()
                ->send();
            return;
        }
        
        // Simple CSV export
        $filename = 'payroll_' . $this->selectedMonth . '_' . now()->format('YmdHis') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=\"' . $filename . '\"',
        ];
        
        $callback = function() use ($payrolls) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'Emp. ID',
                'Emp. Name',
                'Basic Salary',
                'Housing Allowance',
                'Transportation Allow',
                'Food Allowance',
                'Other Allowance',
                'Total Other Allowance',
                'Total Salary',
                'Fees',
                'Monthly Cost',
                'Overtime Amount',
                'Net Payment'
            ]);
            
            // CSV Data
            foreach ($payrolls as $payroll) {
                fputcsv($file, [
                    $payroll->employee->emp_id ?? 'N/A',
                    $payroll->employee->name ?? 'N/A',
                    $payroll->basic_salary,
                    $payroll->housing_allowance,
                    $payroll->transportation_allowance,
                    $payroll->food_allowance,
                    $payroll->other_allowance,
                    $payroll->total_other_allow,
                    $payroll->total_salary,
                    $payroll->fees,
                    $payroll->monthly_cost,
                    $payroll->overtime_amount ?? 0,
                    $payroll->net_payment
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    public function updatedTableSearch(): void
    {
        $this->resetTable();
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        // Apply company filter for PROVIDER
        if ($this->isProvider() && $this->selectedCompany && $this->selectedCompany !== 'all') {
            if ($this->selectedCompany === 'in_house') {
                // In-House: employees NOT assigned to any client company (AVAILABLE)
                $query->whereHas('employee', function ($q) {
                    $q->whereDoesntHave('assigned', fn($sq) => 
                        $sq->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
                    );
                });
            } else {
                // Specific client company: employees assigned to this company
                $companyId = (int) $this->selectedCompany;
                $query->whereHas('employee.assigned', function ($q) use ($companyId) {
                    $q->where('employee_assigned.company_id', $companyId)
                      ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED);
                });
            }
        }
        
        // Filter by payroll_month field
        if ($this->selectedMonth) {
            $query->where(function ($q) {
                $q->where('payroll_month', $this->selectedMonth)
                  ->orWhere(function ($sq) {
                      // Fallback for old records without payroll_month
                      $date = Carbon::parse($this->selectedMonth . '-01');
                      $sq->whereNull('payroll_month')
                         ->whereYear('created_at', $date->year)
                         ->whereMonth('created_at', $date->month);
                  });
            });
        }
        
        // Apply custom search
        if ($this->tableSearch) {
            $search = $this->tableSearch;
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($employeeQuery) use ($search) {
                    $employeeQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('emp_id', 'like', "%{$search}%");
                });
            });
        }
        
        return $query;
    }

    public function getSelectedMonthYear(): string
    {
        if (!$this->selectedMonth) {
            return now()->format('F Y');
        }
        $date = Carbon::parse($this->selectedMonth . '-01');
        return $date->format('F Y');
    }

    public function previousMonth(): void
    {
        $date = Carbon::parse($this->selectedMonth . '-01');
        $this->selectedMonth = $date->subMonth()->format('Y-m');
        $this->resetTable();
    }

    public function nextMonth(): void
    {
        $date = Carbon::parse($this->selectedMonth . '-01');
        $this->selectedMonth = $date->addMonth()->format('Y-m');
        $this->resetTable();
    }

    public function getTotalEmployees(): int
    {
        return $this->getTableQuery()->count();
    }

    public function getTotalOvertime(): float
    {
        return (float) $this->getTableQuery()
            ->sum('overtime_amount') ?? 0.00;
    }

    public function getTotalWithoutOvertime(): float
    {
        // Total Without OT = Net Payment - Total Overtime
        return (float) ($this->getNetPayment() - $this->getTotalOvertime());
    }

    public function getNetPayment(): float
    {
        // Calculate net_payment using the accessor from Payroll model
        $payrolls = $this->getTableQuery()->get();
        return (float) $payrolls->sum('net_payment') ?? 0.00;
    }

    public function getEmployeePercentageChange(): string
    {
        // Calculate percentage change from previous month
        $current = $this->getTotalEmployees();
        $previous = $this->getPreviousMonthCount();
        
        if ($previous == 0) return '0.0';
        
        $change = (($current - $previous) / $previous) * 100;
        return number_format(abs($change), 1);
    }

    public function getOvertimePercentageChange(): string
    {
        $current = $this->getTotalOvertime();
        $previous = $this->getPreviousMonthOvertime();
        
        if ($previous == 0) return '0.0';
        
        $change = (($current - $previous) / $previous) * 100;
        return number_format(abs($change), 1);
    }

    public function getWithoutOvertimePercentageChange(): string
    {
        $current = $this->getTotalWithoutOvertime();
        $previous = $this->getPreviousMonthWithoutOvertime();
        
        if ($previous == 0) return '0.0';
        
        $change = (($current - $previous) / $previous) * 100;
        return number_format(abs($change), 1);
    }

    public function getNetPaymentPercentageChange(): string
    {
        $current = $this->getNetPayment();
        $previous = $this->getPreviousMonthNetPayment();
        
        if ($previous == 0) return '0.0';
        
        $change = (($current - $previous) / $previous) * 100;
        return number_format(abs($change), 1);
    }

    public function getPreviousMonthCount(): int
    {
        $date = Carbon::parse($this->selectedMonth . '-01');
        $previousMonth = $date->copy()->subMonth();
        
        $query = PayrollResource::getEloquentQuery();
        $user = Filament::auth()->user();
        
        if ($user->type === \App\Enums\CompanyTypes::PROVIDER) {
            $query->whereHas('employee', fn($q) => $q->where('company_id', $user->id));
        } else {
            $query->whereHas('employee.assigned', fn($q) => 
                $q->where('employee_assigned.company_id', $user->id)
                  ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
            );
        }
        
        return $query->where(function ($q) use ($previousMonth) {
                $q->where('payroll_month', $previousMonth->format('Y-m'))
                  ->orWhere(function ($sq) use ($previousMonth) {
                      $sq->whereNull('payroll_month')
                         ->whereYear('created_at', $previousMonth->year)
                         ->whereMonth('created_at', $previousMonth->month);
                  });
            })
                     ->count();
    }

    public function getPreviousMonthOvertime(): float
    {
        $date = Carbon::parse($this->selectedMonth . '-01');
        $previousMonth = $date->copy()->subMonth();
        
        $query = PayrollResource::getEloquentQuery();
        $user = Filament::auth()->user();
        
        if ($user->type === \App\Enums\CompanyTypes::PROVIDER) {
            $query->whereHas('employee', fn($q) => $q->where('company_id', $user->id));
        } else {
            $query->whereHas('employee.assigned', fn($q) => 
                $q->where('employee_assigned.company_id', $user->id)
                  ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
            );
        }
        
        return (float) $query->where(function ($q) use ($previousMonth) {
                $q->where('payroll_month', $previousMonth->format('Y-m'))
                  ->orWhere(function ($sq) use ($previousMonth) {
                      $sq->whereNull('payroll_month')
                         ->whereYear('created_at', $previousMonth->year)
                         ->whereMonth('created_at', $previousMonth->month);
                  });
            })
                             ->sum('overtime_amount') ?? 0.00;
    }

    public function getPreviousMonthWithoutOvertime(): float
    {
        $date = Carbon::parse($this->selectedMonth . '-01');
        $previousMonth = $date->copy()->subMonth();
        
        $query = PayrollResource::getEloquentQuery();
        $user = Filament::auth()->user();
        
        if ($user->type === \App\Enums\CompanyTypes::PROVIDER) {
            $query->whereHas('employee', fn($q) => $q->where('company_id', $user->id));
        } else {
            $query->whereHas('employee.assigned', fn($q) => 
                $q->where('employee_assigned.company_id', $user->id)
                  ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
            );
        }
        
        $payrolls = $query->where(function ($q) use ($previousMonth) {
                $q->where('payroll_month', $previousMonth->format('Y-m'))
                  ->orWhere(function ($sq) use ($previousMonth) {
                      $sq->whereNull('payroll_month')
                         ->whereYear('created_at', $previousMonth->year)
                         ->whereMonth('created_at', $previousMonth->month);
                  });
            })
            ->get();
        
        // Total Without OT = Net Payment - Overtime
        $netPayment = (float) $payrolls->sum('net_payment');
        $overtime = (float) $payrolls->sum('overtime_amount');
        return $netPayment - $overtime;
    }

    public function getPreviousMonthNetPayment(): float
    {
        $date = Carbon::parse($this->selectedMonth . '-01');
        $previousMonth = $date->copy()->subMonth();
        
        $query = PayrollResource::getEloquentQuery();
        $user = Filament::auth()->user();
        
        if ($user->type === \App\Enums\CompanyTypes::PROVIDER) {
            $query->whereHas('employee', fn($q) => $q->where('company_id', $user->id));
        } else {
            $query->whereHas('employee.assigned', fn($q) => 
                $q->where('employee_assigned.company_id', $user->id)
                  ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
            );
        }
        
        $payrolls = $query->where(function ($q) use ($previousMonth) {
                $q->where('payroll_month', $previousMonth->format('Y-m'))
                  ->orWhere(function ($sq) use ($previousMonth) {
                      $sq->whereNull('payroll_month')
                         ->whereYear('created_at', $previousMonth->year)
                         ->whereMonth('created_at', $previousMonth->month);
                  });
            })
                          ->get();
        
        return (float) $payrolls->sum('net_payment') ?? 0.00;
    }

    public function getLastUpdateDate(): string
    {
        $latest = $this->getTableQuery()->latest('updated_at')->first();
        if ($latest) {
            return $latest->updated_at->format('F d, Y');
        }
        return now()->format('F d, Y');
    }

    public function calculatePayroll(): void
    {
        $user = Filament::auth()->user();
        $date = Carbon::parse($this->selectedMonth . '-01');
        
        // Get employees based on company type AND company filter
        if ($user->type === CompanyTypes::PROVIDER) {
            $employeeQuery = Employee::where('company_id', $user->id);
            
            // Apply company filter
            if ($this->selectedCompany && $this->selectedCompany !== 'all') {
                if ($this->selectedCompany === 'in_house') {
                    // In-House: employees NOT assigned to any client
                    $employeeQuery->whereDoesntHave('assigned', fn($q) => 
                        $q->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
                    );
                } else {
                    // Specific client company
                    $companyId = (int) $this->selectedCompany;
                    $employeeQuery->whereHas('assigned', fn($q) => 
                        $q->where('employee_assigned.company_id', $companyId)
                          ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
                    );
                }
            }
            
            $employees = $employeeQuery->get();
        } else {
            $employees = Employee::whereHas('assigned', fn($q) => 
                $q->where('employee_assigned.company_id', $user->id)
                  ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
            )->get();
        }
        
        if ($employees->isEmpty()) {
            Notification::make()
                ->title('No employees found')
                ->warning()
                ->send();
            return;
        }
        
        $created = 0;
        $existing = 0;
        $updated = 0;
        $missingData = [];
        
        foreach ($employees as $employee) {
            // Check if payroll already exists for this employee and month
            $existingPayroll = Payroll::where('employee_id', $employee->id)
                ->where('company_id', $user->id)
                ->where('payroll_month', $this->selectedMonth)
                ->first();
            
            // Check if employee has a template payroll (any month) with filled data
            $templatePayroll = Payroll::where('employee_id', $employee->id)
                ->where('company_id', $user->id)
                ->where('basic_salary', '>', 0)
                ->latest()
                ->first();
            
            if ($existingPayroll) {
                // If payroll exists with data, skip
                if ($existingPayroll->basic_salary > 0) {
                    $existing++;
                    continue;
                }
                
                // If payroll exists but empty, check if we have template
                if (!$templatePayroll) {
                    // No template found - add to missing data
                    $empName = $employee->name . ($employee->emp_id ? " ({$employee->emp_id})" : '');
                    $missingData[] = $empName;
                    $existing++;
                    continue;
                }
                
                // Update existing empty payroll with template data
                $deductions = \App\Models\Deduction::where('employee_id', $employee->id)
                    ->where('payroll_month', $this->selectedMonth)
                    ->where('status', \App\Enums\DeductionStatus::APPROVED)
                    ->get();
                
                $totalDeductionAmount = $deductions->sum('amount');
                
                $existingPayroll->update([
                    'basic_salary' => $templatePayroll->basic_salary,
                    'housing_allowance' => $templatePayroll->housing_allowance,
                    'transportation_allowance' => $templatePayroll->transportation_allowance,
                    'food_allowance' => $templatePayroll->food_allowance,
                    'other_allowance' => $templatePayroll->other_allowance,
                    'fees' => $templatePayroll->fees,
                    'total_package' => $templatePayroll->total_package,
                    'work_days' => $templatePayroll->work_days,
                    'absence_days' => $deductions->where('reason', 'absence')->sum('days') ?? 0,
                    'absence_unpaid_leave_deduction' => $deductions->where('reason', 'absence')->sum('amount') ?? 0,
                    'food_subscription_deduction' => $deductions->where('reason', 'food_subscription')->sum('amount') ?? 0,
                    'other_deduction' => $totalDeductionAmount - ($deductions->where('reason', 'absence')->sum('amount') ?? 0) - ($deductions->where('reason', 'food_subscription')->sum('amount') ?? 0),
                ]);
                
                $deductions->each(fn($d) => $d->update(['payroll_id' => $existingPayroll->id]));
                $updated++;
                continue;
            }
            
            if (!$templatePayroll) {
                // No payroll data exists for this employee at all
                $empName = $employee->name . ($employee->emp_id ? " ({$employee->emp_id})" : '');
                $missingData[] = $empName;
                continue;
            }
            
            // Get approved deductions for this employee and month
            $deductions = \App\Models\Deduction::where('employee_id', $employee->id)
                ->where('payroll_month', $this->selectedMonth)
                ->where('status', \App\Enums\DeductionStatus::APPROVED)
                ->get();
            
            $totalDeductionAmount = $deductions->sum('amount');
            
            // Create new payroll using template data
            $payroll = Payroll::create([
                'employee_id' => $employee->id,
                'company_id' => $user->id,
                'payroll_month' => $this->selectedMonth,
                'status' => \App\Enums\PayrollStatus::DRAFT,
                'basic_salary' => $templatePayroll->basic_salary,
                'housing_allowance' => $templatePayroll->housing_allowance,
                'transportation_allowance' => $templatePayroll->transportation_allowance,
                'food_allowance' => $templatePayroll->food_allowance,
                'other_allowance' => $templatePayroll->other_allowance,
                'fees' => $templatePayroll->fees,
                'total_package' => $templatePayroll->total_package,
                'work_days' => $templatePayroll->work_days,
                'added_days' => 0,
                'overtime_hours' => 0,
                'overtime_amount' => 0,
                'added_days_amount' => 0,
                'other_additions' => 0,
                'absence_days' => $deductions->where('reason', 'absence')->sum('days') ?? 0,
                'absence_unpaid_leave_deduction' => $deductions->where('reason', 'absence')->sum('amount') ?? 0,
                'food_subscription_deduction' => $deductions->where('reason', 'food_subscription')->sum('amount') ?? 0,
                'other_deduction' => $totalDeductionAmount - ($deductions->where('reason', 'absence')->sum('amount') ?? 0) - ($deductions->where('reason', 'food_subscription')->sum('amount') ?? 0),
                'created_at' => $date,
                'updated_at' => now(),
            ]);
            
            // Link deductions to this payroll
            $deductions->each(fn($d) => $d->update(['payroll_id' => $payroll->id]));
            
            $created++;
        }
        
        $this->resetTable();
        
        // Show warning for employees with missing payroll data
        if (!empty($missingData)) {
            $names = implode('، ', $missingData);
            Notification::make()
                ->title('لا يمكن احتساب الراتب - بيانات ناقصة')
                ->body("الموظفون التالية أسماؤهم لا تتوفر لديهم بيانات رواتب: {$names}")
                ->danger()
                ->persistent()
                ->send();
        }
        
        if ($created > 0 || $updated > 0) {
            $message = [];
            if ($created > 0) $message[] = "تم إنشاء: {$created}";
            if ($updated > 0) $message[] = "تم تحديث: {$updated}";
            if ($existing > 0) $message[] = "موجود مسبقاً: {$existing}";
            
            Notification::make()
                ->title('تم احتساب الرواتب')
                ->body(implode(' | ', $message))
                ->success()
                ->send();
        } elseif (empty($missingData)) {
            Notification::make()
                ->title('جميع الرواتب موجودة مسبقاً')
                ->body("جميع الموظفين لديهم كشوف رواتب لهذا الشهر")
                ->info()
                ->send();
        }
    }
}
