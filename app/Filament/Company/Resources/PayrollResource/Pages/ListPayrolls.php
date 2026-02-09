<?php

namespace App\Filament\Company\Resources\PayrollResource\Pages;

use App\Filament\Company\Resources\PayrollResource;
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

    public function mount(): void
    {
        parent::mount();
        
        if (!$this->selectedMonth) {
            $this->selectedMonth = now()->format('Y-m');
        }
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
        
        // Filter by selected month (if payrolls have created_at or a month field)
        // For now, we'll filter by created_at month
        if ($this->selectedMonth) {
            $date = Carbon::parse($this->selectedMonth . '-01');
            $query->whereYear('created_at', $date->year)
                  ->whereMonth('created_at', $date->month);
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
        // Calculate total_salary using actual database columns
        // total_salary = basic_salary + housing_allowance + transportation_allowance + food_allowance + other_allowance
        return (float) $this->getTableQuery()
            ->selectRaw('SUM(basic_salary + housing_allowance + transportation_allowance + food_allowance + other_allowance) as total')
            ->value('total') ?? 0.00;
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
        
        return $query->whereYear('created_at', $previousMonth->year)
                     ->whereMonth('created_at', $previousMonth->month)
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
        
        return (float) $query->whereYear('created_at', $previousMonth->year)
                             ->whereMonth('created_at', $previousMonth->month)
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
        
        return (float) $query->whereYear('created_at', $previousMonth->year)
                             ->whereMonth('created_at', $previousMonth->month)
                             ->selectRaw('SUM(basic_salary + housing_allowance + transportation_allowance + food_allowance + other_allowance) as total')
                             ->value('total') ?? 0.00;
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
        
        $payrolls = $query->whereYear('created_at', $previousMonth->year)
                          ->whereMonth('created_at', $previousMonth->month)
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
        
        // Get employees based on company type
        if ($user->type === \App\Enums\CompanyTypes::PROVIDER) {
            $employees = \App\Models\Employee::where('company_id', $user->id)->get();
        } else {
            $employees = \App\Models\Employee::whereHas('assigned', fn($q) => 
                $q->where('employee_assigned.company_id', $user->id)
                  ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
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
        
        foreach ($employees as $employee) {
            // Check if payroll already exists for this employee and month
            $payrollExists = Payroll::where('employee_id', $employee->id)
                ->where('company_id', $user->id)
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->exists();
            
            if ($payrollExists) {
                $existing++;
                continue;
            }
            
            // Create new payroll with default values (can be customized)
            Payroll::create([
                'employee_id' => $employee->id,
                'company_id' => $user->id,
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
        }
        
        $this->resetTable();
        
        Notification::make()
            ->title('Payroll calculation completed')
            ->body("Created: {$created} records" . ($existing > 0 ? " | Already exists: {$existing}" : ""))
            ->success()
            ->send();
    }
}
