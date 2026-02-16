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

    public ?string $companyType = null;

    public ?string $payrollCategory = null;

    public ?string $selectedCompanyId = null;

    public function mount(): void
    {
        $user = Filament::auth()->user();
        $this->companyType = $user->type;
    }

    public function getTitle(): string
    {
        if ($this->selectedCompanyId) {
            $companyName = $this->getSelectedCompanyName();
            return $companyName . ' - Select Category / اختر نوع كشف الرواتب';
        }

        if ($this->companyType === CompanyTypes::CLIENT) {
            return 'Payroll - Select Provider / اختر شركة المزود';
        }

        return 'Payroll - Select Company / اختر الشركة';
    }

    public function selectCategory(string $category): void
    {
        $this->payrollCategory = $category;

        if (!$this->selectedCompanyId) {
            return;
        }

        $user = Filament::auth()->user();

        // For 'no_payroll', auto-create empty DRAFT payrolls
        if ($this->selectedCompanyId === 'no_payroll' && $user->type === CompanyTypes::PROVIDER) {
            $this->createEmptyPayrollsForMissing();
        }

        $params = ['payrollCategory' => $this->payrollCategory];

        if ($user->type === CompanyTypes::CLIENT) {
            $params['providerCompany'] = $this->selectedCompanyId;
        } else {
            $params['clientCompany'] = $this->selectedCompanyId;
        }

        $this->redirect(PayrollResource::getUrl('list', $params));
    }

    public function resetCompany(): void
    {
        $this->selectedCompanyId = null;
        $this->payrollCategory = null;
    }

    protected function getSelectedCompanyName(): string
    {
        if ($this->selectedCompanyId === 'all') {
            return $this->companyType === CompanyTypes::CLIENT ? 'All Providers / جميع المزودين' : 'All Companies / جميع الشركات';
        }
        if ($this->selectedCompanyId === 'in_house') {
            return 'In-House / موظفين داخليين';
        }
        if ($this->selectedCompanyId === 'no_payroll') {
            return 'No Payroll Data / بدون بيانات رواتب';
        }
        $company = Company::find($this->selectedCompanyId);
        return $company ? $company->name : '';
    }

    /**
     * Get companies to display as cards.
     * For PROVIDER: shows client companies they lent employees to.
     * For CLIENT: shows provider companies they borrowed employees from.
     */
    public function getClientCompanies(): array
    {
        $user = Filament::auth()->user();

        if (!$user) {
            return [];
        }

        if ($user->type === CompanyTypes::PROVIDER) {
            return $this->getProviderCards($user);
        }

        return $this->getClientCards($user);
    }

    /**
     * Cards for PROVIDER view: shows client companies they assigned employees to.
     */
    protected function getProviderCards($user): array
    {
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

        // Count employees without payroll data for current month
        // (either no payroll record at all, or payroll with basic_salary = 0)
        $currentMonth = now()->format('Y-m');
        $noPayrollCount = Employee::where('company_id', $user->id)
            ->where(function ($q) use ($currentMonth) {
                $q->whereDoesntHave('payrolls', fn($pq) =>
                    $pq->where('payroll_month', $currentMonth)
                )
                ->orWhereHas('payrolls', fn($pq) =>
                    $pq->where('payroll_month', $currentMonth)
                       ->where('basic_salary', 0)
                );
            })
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

    /**
     * Cards for CLIENT view: shows provider companies they borrowed employees from.
     */
    protected function getClientCards($user): array
    {
        // Get all provider company IDs that have approved employees assigned to this client
        $providerCompanyIds = DB::table('employee_assigned')
            ->join('employees', 'employees.id', '=', 'employee_assigned.employee_id')
            ->where('employee_assigned.company_id', $user->id)
            ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
            ->distinct()
            ->pluck('employees.company_id');

        $companies = Company::whereIn('id', $providerCompanyIds)->get();

        $result = [];
        foreach ($companies as $company) {
            $employeeCount = Employee::where('company_id', $company->id)
                ->whereHas('assigned', fn($q) =>
                    $q->where('employee_assigned.company_id', $user->id)
                      ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
                )
                ->count();

            $result[] = [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'city' => $company->city?->name ?? '',
                'employee_count' => $employeeCount,
                'type' => 'provider',
            ];
        }

        // "All Providers" option at the top
        $totalEmployees = Employee::whereHas('assigned', fn($q) =>
            $q->where('employee_assigned.company_id', $user->id)
              ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
        )->count();

        array_unshift($result, [
            'id' => 'all',
            'name' => 'All Providers',
            'name_ar' => 'جميع المزودين',
            'email' => '',
            'city' => '',
            'employee_count' => $totalEmployees,
            'type' => 'all',
        ]);

        return $result;
    }

    public function selectCompany(string $companyId): void
    {
        $this->selectedCompanyId = $companyId;
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
