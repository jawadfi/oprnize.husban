<?php

namespace App\Filament\Company\Resources\PayrollResource\Pages;

use App\Enums\CompanyTypes;
use App\Enums\EmployeeAssignedStatus;
use App\Filament\Company\Resources\PayrollResource;
use App\Models\Company;
use App\Models\Employee;
use Filament\Facades\Filament;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class SelectCompanyPayroll extends Page
{
    protected static string $resource = PayrollResource::class;

    protected static string $view = 'filament.company.resources.payroll-resource.pages.select-company-payroll';

    public function mount(): void
    {
        $user = Filament::auth()->user();

        // CLIENT companies skip company selection, go directly to payroll list
        if ($user->type !== CompanyTypes::PROVIDER) {
            $this->redirect(PayrollResource::getUrl('list'));
        }
    }

    public function getTitle(): string
    {
        return 'Payroll - Select Company';
    }

    /**
     * Get the client companies that the provider has employees assigned to.
     */
    public function getClientCompanies(): array
    {
        $user = Filament::auth()->user();

        if (!$user || $user->type !== CompanyTypes::PROVIDER) {
            return [];
        }

        // Get all client company IDs that have approved employee assignments from this provider
        $clientCompanyIds = DB::table('employee_assigned')
            ->join('employees', 'employees.id', '=', 'employee_assigned.employee_id')
            ->where('employees.company_id', $user->id)
            ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
            ->distinct()
            ->pluck('employee_assigned.company_id');

        $companies = Company::whereIn('id', $clientCompanyIds)->get();

        $result = [];
        foreach ($companies as $company) {
            $employeeCount = Employee::where('company_id', $user->id)
                ->whereHas('assigned', fn($q) =>
                    $q->where('employee_assigned.company_id', $company->id)
                      ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
                )
                ->count();

            $result[] = [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'city' => $company->city?->name ?? '',
                'employee_count' => $employeeCount,
                'type' => 'client',
            ];
        }

        // Count In-House employees (owned by provider but NOT assigned to any client company)
        $inHouseCount = Employee::where('company_id', $user->id)
            ->whereDoesntHave('assigned', fn($q) =>
                $q->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
            )
            ->count();

        // Count employees without payroll for current month
        $currentMonth = now()->format('Y-m');
        $noPayrollCount = Employee::where('company_id', $user->id)
            ->whereDoesntHave('payrolls', fn($q) =>
                $q->where('payroll_month', $currentMonth)
            )
            ->count();

        // Also add "All Companies" option at the top
        $totalEmployees = Employee::where('company_id', $user->id)->count();
        array_unshift($result, [
            'id' => 'all',
            'name' => 'All Companies',
            'name_ar' => 'جميع الشركات',
            'email' => '',
            'city' => '',
            'employee_count' => $totalEmployees,
            'type' => 'all',
        ]);

        // Add In-House card
        $result[] = [
            'id' => 'in_house',
            'name' => 'In-House Employees',
            'name_ar' => 'موظفين داخليين',
            'email' => 'Not assigned to any client',
            'city' => '',
            'employee_count' => $inHouseCount,
            'type' => 'in_house',
        ];

        // Add No-Payroll card
        $result[] = [
            'id' => 'no_payroll',
            'name' => 'No Payroll Data',
            'name_ar' => 'بدون بيانات رواتب',
            'email' => 'Employees without payroll this month',
            'city' => '',
            'employee_count' => $noPayrollCount,
            'type' => 'no_payroll',
        ];

        return $result;
    }

    public function selectCompany(string $companyId): void
    {
        // For 'no_payroll', auto-create empty DRAFT payrolls for employees missing payroll
        if ($companyId === 'no_payroll') {
            $this->createEmptyPayrollsForMissing();
        }

        $this->redirect(PayrollResource::getUrl('list', ['clientCompany' => $companyId]));
    }

    /**
     * Create empty DRAFT payroll records for employees that don't have payroll this month.
     */
    protected function createEmptyPayrollsForMissing(): void
    {
        $user = Filament::auth()->user();
        $currentMonth = now()->format('Y-m');

        $employees = Employee::where('company_id', $user->id)
            ->whereDoesntHave('payrolls', fn($q) =>
                $q->where('payroll_month', $currentMonth)
            )
            ->get();

        foreach ($employees as $employee) {
            \App\Models\Payroll::create([
                'employee_id' => $employee->id,
                'company_id' => $user->id,
                'payroll_month' => $currentMonth,
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
            ]);
        }
    }
}
