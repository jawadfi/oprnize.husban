<?php

namespace App\Filament\Company\Resources\PayrollResource\Pages;

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
use Livewire\WithFileUploads;

class ListPayrolls extends ListRecords
{
    use WithFileUploads;
    protected static string $resource = PayrollResource::class;

    protected static string $view = 'filament.company.resources.payroll-resource.pages.list-payrolls';

    #[Url]
    public ?string $selectedMonth = null;

    #[Url]
    public ?string $clientCompany = null;

    #[Url]
    public ?string $providerCompany = null;

    public ?string $clientCompanyName = null;
    public ?string $providerCompanyName = null;

    public $salaryFile = null;
    public bool $showSalaryImport = false;

    public function mount(): void
    {
        parent::mount();
        
        if (!$this->selectedMonth) {
            $this->selectedMonth = now()->format('Y-m');
        }

        // Load client company name for display (PROVIDER view)
        if ($this->clientCompany && $this->clientCompany !== 'all') {
            if ($this->clientCompany === 'in_house') {
                $this->clientCompanyName = 'موظفين داخليين / In-House';
            } elseif ($this->clientCompany === 'no_payroll') {
                $this->clientCompanyName = 'بدون رواتب / No Payroll';
            } else {
                $company = Company::find($this->clientCompany);
                $this->clientCompanyName = $company?->name;
            }
        }

        // Load provider company name for display (CLIENT view)
        if ($this->providerCompany && $this->providerCompany !== 'all') {
            $company = Company::find($this->providerCompany);
            $this->providerCompanyName = $company?->name;
        }
    }

    public function getTitle(): string
    {
        $date = Carbon::parse($this->selectedMonth . '-01');
        $title = 'Payroll - ' . $date->format('F Y');
        if ($this->clientCompanyName) {
            $title .= ' - ' . $this->clientCompanyName;
        }
        if ($this->providerCompanyName) {
            $title .= ' - ' . $this->providerCompanyName;
        }
        return $title;
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
        
        // Filter by selected client company (for PROVIDER)
        if ($this->clientCompany && $this->clientCompany !== 'all') {
            if ($this->clientCompany === 'in_house') {
                // In-House: employees NOT assigned to any client company
                $query->whereHas('employee', fn($q) =>
                    $q->whereDoesntHave('assigned', fn($sq) =>
                        $sq->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                    )
                );
            } elseif ($this->clientCompany === 'no_payroll') {
                // No Payroll: show only payrolls with basic_salary = 0 (empty records)
                // These are auto-created when user clicks the No Payroll card
                $query->where('basic_salary', 0);
            } else {
                $clientId = (int) $this->clientCompany;
                $query->whereHas('employee.assigned', fn($q) =>
                    $q->where('employee_assigned.company_id', $clientId)
                      ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                );
            }
        }

        // Filter by selected provider company (for CLIENT)
        if ($this->providerCompany && $this->providerCompany !== 'all') {
            $providerId = (int) $this->providerCompany;
            $query->whereHas('employee', fn($q) =>
                $q->where('company_id', $providerId)
            );
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
            
            // Apply client company filter
            if ($this->clientCompany && $this->clientCompany !== 'all') {
                if ($this->clientCompany === 'in_house') {
                    $query->whereHas('employee', fn($q) =>
                        $q->whereDoesntHave('assigned', fn($sq) =>
                            $sq->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                        )
                    );
                } elseif ($this->clientCompany === 'no_payroll') {
                    $query->where('basic_salary', 0);
                } else {
                    $clientId = (int) $this->clientCompany;
                    $query->whereHas('employee.assigned', fn($q) =>
                        $q->where('employee_assigned.company_id', $clientId)
                          ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                    );
                }
            }
        } else {
            $query->whereHas('employee.assigned', fn($q) => 
                $q->where('employee_assigned.company_id', $user->id)
                  ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
            );
            
            // Apply provider company filter
            if ($this->providerCompany && $this->providerCompany !== 'all') {
                $providerId = (int) $this->providerCompany;
                $query->whereHas('employee', fn($q) => $q->where('company_id', $providerId));
            }
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
            
            if ($this->clientCompany && $this->clientCompany !== 'all') {
                if ($this->clientCompany === 'in_house') {
                    $query->whereHas('employee', fn($q) =>
                        $q->whereDoesntHave('assigned', fn($sq) =>
                            $sq->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                        )
                    );
                } elseif ($this->clientCompany === 'no_payroll') {
                    $query->where('basic_salary', 0);
                } else {
                    $clientId = (int) $this->clientCompany;
                    $query->whereHas('employee.assigned', fn($q) =>
                        $q->where('employee_assigned.company_id', $clientId)
                          ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                    );
                }
            }
        } else {
            $query->whereHas('employee.assigned', fn($q) => 
                $q->where('employee_assigned.company_id', $user->id)
                  ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
            );
            
            if ($this->providerCompany && $this->providerCompany !== 'all') {
                $providerId = (int) $this->providerCompany;
                $query->whereHas('employee', fn($q) => $q->where('company_id', $providerId));
            }
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
            
            if ($this->clientCompany && $this->clientCompany !== 'all') {
                if ($this->clientCompany === 'in_house') {
                    $query->whereHas('employee', fn($q) =>
                        $q->whereDoesntHave('assigned', fn($sq) =>
                            $sq->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                        )
                    );
                } elseif ($this->clientCompany === 'no_payroll') {
                    $query->where('basic_salary', 0);
                } else {
                    $clientId = (int) $this->clientCompany;
                    $query->whereHas('employee.assigned', fn($q) =>
                        $q->where('employee_assigned.company_id', $clientId)
                          ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                    );
                }
            }
        } else {
            $query->whereHas('employee.assigned', fn($q) => 
                $q->where('employee_assigned.company_id', $user->id)
                  ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
            );
            
            if ($this->providerCompany && $this->providerCompany !== 'all') {
                $providerId = (int) $this->providerCompany;
                $query->whereHas('employee', fn($q) => $q->where('company_id', $providerId));
            }
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
            
            if ($this->clientCompany && $this->clientCompany !== 'all') {
                if ($this->clientCompany === 'in_house') {
                    $query->whereHas('employee', fn($q) =>
                        $q->whereDoesntHave('assigned', fn($sq) =>
                            $sq->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                        )
                    );
                } elseif ($this->clientCompany === 'no_payroll') {
                    $query->where('basic_salary', 0);
                } else {
                    $clientId = (int) $this->clientCompany;
                    $query->whereHas('employee.assigned', fn($q) =>
                        $q->where('employee_assigned.company_id', $clientId)
                          ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                    );
                }
            }
        } else {
            $query->whereHas('employee.assigned', fn($q) => 
                $q->where('employee_assigned.company_id', $user->id)
                  ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
            );
            
            if ($this->providerCompany && $this->providerCompany !== 'all') {
                $providerId = (int) $this->providerCompany;
                $query->whereHas('employee', fn($q) => $q->where('company_id', $providerId));
            }
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
        
        // Get employees based on company type and selected client company
        if ($user->type === \App\Enums\CompanyTypes::PROVIDER) {
            $employeesQuery = \App\Models\Employee::where('company_id', $user->id);
            
            // If a specific client company is selected, only get employees assigned to that company
            if ($this->clientCompany && $this->clientCompany !== 'all') {
                if ($this->clientCompany === 'in_house') {
                    // In-House: employees NOT assigned to any client
                    $employeesQuery->whereDoesntHave('assigned', fn($q) =>
                        $q->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                    );
                } elseif ($this->clientCompany === 'no_payroll') {
                    // No Payroll: employees without payroll for this month
                    $employeesQuery->whereDoesntHave('payrolls', fn($q) =>
                        $q->where('payroll_month', $date->format('Y-m'))
                    );
                } else {
                    $clientId = (int) $this->clientCompany;
                    $employeesQuery->whereHas('assigned', fn($q) =>
                        $q->where('employee_assigned.company_id', $clientId)
                          ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                    );
                }
            }
            
            $employees = $employeesQuery->get();
        } else {
            $employeesQuery = \App\Models\Employee::whereHas('assigned', fn($q) => 
                $q->where('employee_assigned.company_id', $user->id)
                  ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
            );
            
            // If a specific provider company is selected, only get employees from that provider
            if ($this->providerCompany && $this->providerCompany !== 'all') {
                $providerId = (int) $this->providerCompany;
                $employeesQuery->where('company_id', $providerId);
            }
            
            $employees = $employeesQuery->get();
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
            // Check if payroll already exists for this employee and month (own records only)
            $existingPayroll = Payroll::where('employee_id', $employee->id)
                ->where('company_id', $user->id)
                ->where('payroll_month', $this->selectedMonth)
                ->first();
            
            // Check if employee has a template payroll (any month) with filled data
            // First check own records, then fallback to provider's records (for CLIENT)
            $templatePayroll = Payroll::where('employee_id', $employee->id)
                ->where('company_id', $user->id)
                ->where('basic_salary', '>', 0)
                ->latest()
                ->first();
            
            // CLIENT fallback: if no own template, use PROVIDER's payroll as template
            if (!$templatePayroll && $user->type === \App\Enums\CompanyTypes::CLIENT) {
                $templatePayroll = Payroll::where('employee_id', $employee->id)
                    ->where('company_id', $employee->company_id) // PROVIDER owns the employee
                    ->where('basic_salary', '>', 0)
                    ->latest()
                    ->first();
            }
            
            if ($existingPayroll) {
                // If payroll exists with data, skip
                if ($existingPayroll->basic_salary > 0) {
                    $existing++;
                    continue;
                }
                
                // If payroll exists but empty, check if we have template
                if (!$templatePayroll) {
                    // No template found - leave the empty record for manual entry
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
                // No template found - create empty DRAFT payroll so provider can fill manually
                $payroll = Payroll::create([
                    'employee_id' => $employee->id,
                    'company_id' => $user->id,
                    'payroll_month' => $this->selectedMonth,
                    'status' => \App\Enums\PayrollStatus::DRAFT,
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
                    'created_at' => $date,
                    'updated_at' => now(),
                ]);
                $created++;
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

    public function toggleSalaryImport(): void
    {
        $this->showSalaryImport = !$this->showSalaryImport;
        $this->salaryFile = null;
    }

    /**
     * Get a salary field from normalized row data trying multiple possible column names.
     */
    protected function getSalaryField(array $normalized, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (isset($normalized[$key]) && $normalized[$key] !== '' && $normalized[$key] !== null) {
                return $normalized[$key];
            }
        }
        return $default;
    }

    public function importSalaries(): void
    {
        if (!$this->salaryFile) {
            Notification::make()
                ->title('يرجى رفع ملف CSV')
                ->warning()
                ->send();
            return;
        }

        $user = Filament::auth()->user();

        if ($user->type !== \App\Enums\CompanyTypes::PROVIDER) {
            Notification::make()
                ->title('فقط شركة المزود يمكنها رفع الرواتب')
                ->danger()
                ->send();
            return;
        }

        try {
            // Get file path from Livewire temporary upload
            $filePath = $this->salaryFile->getRealPath();

            $csv = \League\Csv\Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $records = $csv->getRecords();

            $updated = 0;
            $created = 0;
            $skipped = 0;
            $errors = [];
            $rowNum = 1;

            foreach ($records as $row) {
                $rowNum++;

                try {
                    // Normalize column names
                    $normalized = [];
                    foreach ($row as $key => $value) {
                        $clean = strtolower(trim($key));
                        $normalized[$clean] = is_string($value) ? trim($value) : $value;
                        $underscored = str_replace(' ', '_', $clean);
                        if ($underscored !== $clean) {
                            $normalized[$underscored] = is_string($value) ? trim($value) : $value;
                        }
                        $nospace = str_replace(['_', ' '], '', $clean);
                        if ($nospace !== $clean && $nospace !== $underscored) {
                            $normalized[$nospace] = is_string($value) ? trim($value) : $value;
                        }
                    }

                    // Find employee by emp_id or identity_number
                    $empId = $this->getSalaryField($normalized, [
                        'emp_id', 'empid', 'employee_id', 'employeeid', 'emp_no', 'empno',
                        'employee_number', 'employeenumber', 'emp_number', 'empnumber',
                        'الرقم الوظيفي', 'الرقم_الوظيفي', 'رقم الموظف', 'رقم_الموظف',
                    ]);

                    $identityNumber = $this->getSalaryField($normalized, [
                        'identity_number', 'identitynumber', 'identity', 'id_number', 'idnumber',
                        'رقم الهوية', 'رقم_الهوية', 'الهوية',
                    ]);

                    $iqamaNo = $this->getSalaryField($normalized, [
                        'iqama_no', 'iqamano', 'iqama', 'iqama_number', 'iqamanumber',
                        'رقم الإقامة', 'رقم_الإقامة', 'رقم الاقامة', 'رقم_الاقامة', 'الاقامة', 'الإقامة',
                    ]);

                    $employee = null;

                    if ($empId) {
                        $employee = Employee::where('company_id', $user->id)
                            ->where('emp_id', $empId)
                            ->first();
                    }

                    if (!$employee && $identityNumber) {
                        $employee = Employee::where('company_id', $user->id)
                            ->where('identity_number', $identityNumber)
                            ->first();
                    }

                    if (!$employee && $iqamaNo) {
                        $employee = Employee::where('company_id', $user->id)
                            ->where('iqama_no', $iqamaNo)
                            ->first();
                    }

                    if (!$employee) {
                        $identifier = $empId ?: ($identityNumber ?: ($iqamaNo ?: "Row {$rowNum}"));
                        $errors[] = "Row {$rowNum}: Employee not found ({$identifier})";
                        $skipped++;
                        continue;
                    }

                    // Get salary fields
                    $basicSalary = $this->getSalaryField($normalized, [
                        'basic_salary', 'basicsalary', 'basic', 'salary', 'base_salary', 'basesalary',
                        'الراتب الأساسي', 'الراتب_الأساسي', 'الراتب الاساسي', 'الراتب_الاساسي', 'الراتب',
                    ]);

                    $housingAllowance = $this->getSalaryField($normalized, [
                        'housing_allowance', 'housingallowance', 'housing', 'بدل السكن', 'بدل_السكن', 'السكن',
                    ]);

                    $transportationAllowance = $this->getSalaryField($normalized, [
                        'transportation_allowance', 'transportationallowance', 'transportation',
                        'transport_allowance', 'transportallowance', 'transport',
                        'بدل النقل', 'بدل_النقل', 'النقل', 'بدل المواصلات', 'بدل_المواصلات',
                    ]);

                    $foodAllowance = $this->getSalaryField($normalized, [
                        'food_allowance', 'foodallowance', 'food', 'بدل الطعام', 'بدل_الطعام', 'الطعام',
                    ]);

                    $otherAllowance = $this->getSalaryField($normalized, [
                        'other_allowance', 'otherallowance', 'other', 'بدلات أخرى', 'بدلات_أخرى', 'بدلات اخرى',
                    ]);

                    $fees = $this->getSalaryField($normalized, [
                        'fees', 'fee', 'الرسوم', 'رسوم',
                    ]);

                    $workDays = $this->getSalaryField($normalized, [
                        'work_days', 'workdays', 'days', 'أيام العمل', 'أيام_العمل', 'ايام العمل',
                    ]);

                    if (!$basicSalary || (float) $basicSalary <= 0) {
                        $errors[] = "Row {$rowNum}: Missing or zero basic_salary for {$employee->name}";
                        $skipped++;
                        continue;
                    }

                    // Build salary data
                    $salaryData = [
                        'basic_salary' => (float) $basicSalary,
                        'housing_allowance' => (float) ($housingAllowance ?? 0),
                        'transportation_allowance' => (float) ($transportationAllowance ?? 0),
                        'food_allowance' => (float) ($foodAllowance ?? 0),
                        'other_allowance' => (float) ($otherAllowance ?? 0),
                        'fees' => (float) ($fees ?? 0),
                        'work_days' => (int) ($workDays ?? 30),
                    ];

                    // Calculate total_package
                    $salaryData['total_package'] = $salaryData['basic_salary']
                        + $salaryData['housing_allowance']
                        + $salaryData['transportation_allowance']
                        + $salaryData['food_allowance']
                        + $salaryData['other_allowance']
                        + $salaryData['fees'];

                    // Find or create payroll for this employee + month
                    $payroll = Payroll::where('employee_id', $employee->id)
                        ->where('company_id', $user->id)
                        ->where('payroll_month', $this->selectedMonth)
                        ->first();

                    if ($payroll) {
                        $payroll->update($salaryData);
                        $updated++;
                    } else {
                        Payroll::create(array_merge($salaryData, [
                            'employee_id' => $employee->id,
                            'company_id' => $user->id,
                            'payroll_month' => $this->selectedMonth,
                            'status' => \App\Enums\PayrollStatus::DRAFT,
                            'added_days' => 0,
                            'overtime_hours' => 0,
                            'overtime_amount' => 0,
                            'added_days_amount' => 0,
                            'other_additions' => 0,
                            'absence_days' => 0,
                            'absence_unpaid_leave_deduction' => 0,
                            'food_subscription_deduction' => 0,
                            'other_deduction' => 0,
                        ]));
                        $created++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNum}: " . $e->getMessage();
                    $skipped++;
                }
            }

            // Clean up
            $this->salaryFile = null;
            $this->showSalaryImport = false;
            $this->resetTable();

            // Show results
            $message = [];
            if ($created > 0) $message[] = "تم إنشاء: {$created}";
            if ($updated > 0) $message[] = "تم تحديث: {$updated}";
            if ($skipped > 0) $message[] = "تم تخطي: {$skipped}";

            Notification::make()
                ->title('تم رفع الرواتب بنجاح')
                ->body(implode(' | ', $message))
                ->success()
                ->send();

            if (!empty($errors)) {
                Notification::make()
                    ->title('بعض الصفوف فيها أخطاء')
                    ->body(implode("\n", array_slice($errors, 0, 10)))
                    ->warning()
                    ->persistent()
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في رفع الملف')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
