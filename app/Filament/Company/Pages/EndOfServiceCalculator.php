<?php

namespace App\Filament\Company\Pages;

use App\Enums\TerminationReason;
use App\Models\Employee;
use App\Services\EndOfServiceService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
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
        $user = Filament::auth()->user();
        $companyId = $user instanceof \App\Models\Company ? $user->id : ($user->company_id ?? null);

        return $form
            ->schema([
                Section::make('بيانات الموظف')
                    ->description('اختر موظفاً أو أدخل البيانات يدوياً')
                    ->schema([
                        Select::make('employee_id')
                            ->label('الموظف')
                            ->options(function () use ($companyId) {
                                if (! $companyId) {
                                    return [];
                                }

                                return Employee::where('company_id', $companyId)
                                    ->orWhereHas('assigned', fn ($q) => $q->where('employee_assigned.company_id', $companyId))
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (! $state) {
                                    return;
                                }

                                $employee = Employee::with('currentPayroll')->find($state);
                                if (! $employee) {
                                    return;
                                }

                                if ($employee->hire_date) {
                                    $set('hire_date', Carbon::parse($employee->hire_date)->format('Y-m-d'));
                                }

                                $payroll = $employee->currentPayroll;
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
}
