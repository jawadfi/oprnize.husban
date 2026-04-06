<?php

namespace App\Filament\Company\Pages;

use App\Enums\CompanyTypes;
use App\Enums\EmployeeAssignedStatus;
use App\Enums\TerminationReason;
use App\Models\Company;
use App\Models\EmployeeAssigned;
use App\Models\Employee;
use App\Services\EndOfServiceService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class EndOfServiceCalculator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'حاسبة نهاية الخدمة';
    protected static ?string $title = 'حاسبة مكافأة نهاية الخدمة';
    protected static ?string $navigationGroup = 'الأدوات';
    protected static ?int $navigationSort = 90;
    protected static string $view = 'filament.company.pages.end-of-service-calculator';

    public ?array $data = [];
    public ?float $result = null;
    public ?float $serviceYears = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $company = $this->getCurrentCompany();
        $companyId = $company?->id;
        $companyType = $company?->type;

        return $form
            ->schema([
                Section::make('بيانات الموظف')
                    ->description('اختر موظفاً أو أدخل البيانات يدوياً')
                    ->schema([
                        Select::make('employee_id')
                            ->label('الموظف')
                            ->options(function () use ($companyId, $companyType) {
                                if (! $companyId) {
                                    return [];
                                }

                                $query = Employee::query();

                                if ($this->isClientCompany($companyType)) {
                                    // Client company: show own employees + approved assigned employees to this client.
                                    $query->where('company_id', $companyId)
                                        ->orWhereHas('assigned', fn ($q) => $q
                                            ->where('employee_assigned.company_id', $companyId)
                                            ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED));
                                } else {
                                    // Provider company: show only its original employees.
                                    $query->where('company_id', $companyId);
                                }

                                return $query->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) use ($companyId, $companyType) {
                                if (! $state) {
                                    return;
                                }

                                $employee = Employee::find($state);
                                if (! $employee) {
                                    return;
                                }

                                if ($this->isClientCompany($companyType) && $companyId) {
                                    // Client side hiring date should be assignment start date in this client.
                                    $assignmentStartDate = EmployeeAssigned::query()
                                        ->where('employee_id', $employee->id)
                                        ->where('company_id', $companyId)
                                        ->where('status', EmployeeAssignedStatus::APPROVED)
                                        ->orderByDesc('start_date')
                                        ->value('start_date');

                                    if ($assignmentStartDate) {
                                        $set('hire_date', Carbon::parse($assignmentStartDate)->format('Y-m-d'));
                                    } elseif ($employee->hire_date) {
                                        $set('hire_date', Carbon::parse($employee->hire_date)->format('Y-m-d'));
                                    }
                                } elseif ($employee->hire_date) {
                                    // Provider side keeps original employee hire date.
                                    $set('hire_date', Carbon::parse($employee->hire_date)->format('Y-m-d'));
                                }

                                $payroll = null;
                                if ($companyId) {
                                    $payroll = $employee->payrolls()
                                        ->where('company_id', $companyId)
                                        ->latest('id')
                                        ->first();
                                }

                                // Fallback to latest payroll when company-specific payroll is missing.
                                if (! $payroll) {
                                    $payroll = $employee->currentPayroll;
                                }

                                if ($payroll) {
                                    $totalSalary = (float) $payroll->basic_salary
                                        + (float) $payroll->housing_allowance
                                        + (float) $payroll->transportation_allowance
                                        + (float) $payroll->food_allowance
                                        + (float) $payroll->other_allowance;
                                    $set('salary', $totalSalary);
                                }
                            }),

                        DatePicker::make('hire_date')
                            ->label('تاريخ التعيين')
                            ->required()
                            ->live()
                            ->maxDate(now()),

                        DatePicker::make('end_date')
                            ->label('تاريخ انتهاء الخدمة')
                            ->required()
                            ->live()
                            ->default(now()->format('Y-m-d'))
                            ->afterOrEqual('hire_date'),

                        TextInput::make('salary')
                            ->label('الراتب الشهري (ريال)')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->suffix('SAR'),

                        Select::make('reason')
                            ->label('سبب إنهاء الخدمة')
                            ->options(TerminationReason::getTranslatedEnum())
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function calculate(): void
    {
        $data = $this->form->getState();

        $hireDate = Carbon::parse($data['hire_date']);
        $endDate = Carbon::parse($data['end_date']);
        $days = (int) $hireDate->diffInDays($endDate);
        $salary = (float) $data['salary'];
        $reason = (int) $data['reason'];

        $service = new EndOfServiceService();
        $this->result = $service->calculate($reason, $days, $salary);
        $this->serviceYears = round($days / 365.25, 2);
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        if ($user instanceof \App\Models\Company) {
            return true;
        }

        if ($user instanceof \App\Models\User) {
            return $user->can('page_EndOfServiceCalculator');
        }

        return false;
    }

    private function getCurrentCompany(): ?Company
    {
        $user = Filament::auth()->user();

        if ($user instanceof Company) {
            return $user;
        }

        if ($user instanceof \App\Models\User && $user->company_id) {
            return Company::find($user->company_id);
        }

        return null;
    }

    private function isClientCompany(?string $companyType): bool
    {
        return $companyType === CompanyTypes::CLIENT;
    }
}
