<?php

namespace App\Filament\Employee\Pages;

use App\Enums\EndOfServiceRequestStatus;
use App\Enums\TerminationReason;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeAssigned;
use App\Models\EndOfServiceRequest;
use App\Services\EndOfServiceService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Blade;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class RequestEndOfService extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-minus';
    protected static string $view = 'filament.employee.pages.request-end-of-service';
    protected static ?string $navigationLabel = 'Request End of Service';
    protected static ?int $navigationSort = 4;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'last_working_date' => now()->format('Y-m-d'),
        ]);
    }

    public function form(Form $form): Form
    {
        /** @var Employee $employee */
        $employee = Filament::auth()->user();

        return $form
            ->schema([
                Section::make('End of Service Request')
                    ->description('Submit your request to end service. It will be reviewed by branch supervisor, client company, then provider company.')
                    ->schema([
                        Placeholder::make('context_company')
                            ->label('Current Work Company')
                            ->content(fn () => $this->resolveContextData($employee)['context_company_name'] ?? '-'),
                        Placeholder::make('service_start_date_preview')
                            ->label('Service Start Date')
                            ->content(fn () => $this->formatDate($this->resolveContextData($employee)['service_start_date'] ?? null)),
                        Placeholder::make('salary_amount_preview')
                            ->label('Actual Wage')
                            ->content(fn () => number_format($this->resolveContextData($employee)['salary_amount'] ?? 0, 2) . ' SAR'),
                        Placeholder::make('estimated_amount_preview')
                            ->label('Estimated End of Service Benefit')
                            ->content(function (callable $get) use ($employee) {
                                $terminationReason = $get('termination_reason');
                                $lastWorkingDate = $get('last_working_date');

                                if (! $terminationReason || ! $lastWorkingDate) {
                                    return '0.00 SAR';
                                }

                                $context = $this->resolveContextData($employee);
                                if (empty($context['service_start_date'])) {
                                    return '0.00 SAR';
                                }

                                $serviceDays = Carbon::parse($context['service_start_date'])->diffInDays(Carbon::parse($lastWorkingDate));
                                $amount = app(EndOfServiceService::class)->calculate((int) $terminationReason, $serviceDays, (float) ($context['salary_amount'] ?? 0));

                                return number_format($amount, 2) . ' SAR';
                            }),
                        Select::make('termination_reason')
                            ->label('Reason for Ending Employment')
                            ->options(TerminationReason::getTranslatedEnum())
                            ->required()
                            ->live(),
                        DatePicker::make('last_working_date')
                            ->label('Last Working Date')
                            ->required()
                            ->live(),
                        Textarea::make('notes')
                            ->label('Employee Request Details')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->submitAction(new HtmlString(Blade::render(<<<BLADE
    <x-filament::button
        type="submit"
        size="lg"
    >
        Send Request
    </x-filament::button>
BLADE
            )))
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();

        /** @var Employee $employee */
        $employee = Filament::auth()->user();
        $context = $this->resolveContextData($employee);

        if (empty($context['context_company_id']) || empty($context['service_start_date'])) {
            Notification::make()
                ->title('Unable to submit request')
                ->body('Missing company assignment or service start date. Please contact HR.')
                ->danger()
                ->send();
            return;
        }

        $serviceStartDate = Carbon::parse($context['service_start_date']);
        $lastWorkingDate = Carbon::parse($data['last_working_date']);
        $serviceDays = $serviceStartDate->diffInDays($lastWorkingDate);
        $salaryAmount = (float) ($context['salary_amount'] ?? 0);
        $estimatedAmount = app(EndOfServiceService::class)->calculate((int) $data['termination_reason'], $serviceDays, $salaryAmount);

        EndOfServiceRequest::create([
            'employee_id' => $employee->id,
            'company_id' => $context['context_company_id'],
            'provider_company_id' => $employee->company_id,
            'client_company_id' => $context['context_company_id'],
            'current_approver_company_id' => $context['context_company_id'],
            'termination_reason' => (int) $data['termination_reason'],
            'last_working_date' => $data['last_working_date'],
            'service_start_date' => $serviceStartDate->format('Y-m-d'),
            'service_days' => $serviceDays,
            'salary_amount' => $salaryAmount,
            'estimated_amount' => $estimatedAmount,
            'status' => EndOfServiceRequestStatus::PENDING_SUPERVISOR_APPROVAL,
            'notes' => $data['notes'],
        ]);

        Notification::make()
            ->title('End of service request submitted successfully')
            ->body('It will be reviewed by supervisor, then client HR, then provider HR.')
            ->success()
            ->send();

        $this->form->fill([
            'last_working_date' => now()->format('Y-m-d'),
        ]);
    }

    protected function resolveContextData(Employee $employee): array
    {
        $contextCompanyId = $employee->company_assigned_id ?: $employee->company_id;
        $contextCompanyName = Company::query()->where('id', $contextCompanyId)->value('name') ?? '-';

        $serviceStartDate = $employee->hire_date;
        if ($employee->company_assigned_id) {
            $serviceStartDate = EmployeeAssigned::query()
                ->where('employee_id', $employee->id)
                ->where('company_id', $employee->company_assigned_id)
                ->orderByDesc('start_date')
                ->value('start_date') ?: $employee->hire_date;
        }

        $payroll = $employee->payrolls()
            ->where('company_id', $contextCompanyId)
            ->latest('id')
            ->first();

        if (! $payroll) {
            $payroll = $employee->currentPayroll;
        }

        $salaryAmount = $payroll
            ? (float) $payroll->basic_salary
                + (float) $payroll->housing_allowance
                + (float) $payroll->transportation_allowance
                + (float) $payroll->food_allowance
                + (float) $payroll->other_allowance
            : 0.0;

        return [
            'context_company_id' => $contextCompanyId,
            'context_company_name' => $contextCompanyName,
            'service_start_date' => $serviceStartDate,
            'salary_amount' => $salaryAmount,
        ];
    }

    protected function formatDate($date): string
    {
        if (! $date) {
            return '-';
        }

        return Carbon::parse($date)->format('Y-m-d');
    }

    public function getTitle(): string | Htmlable
    {
        return 'Request End of Service';
    }

    public function getHeading(): string | Htmlable
    {
        return 'Request End of Service';
    }
}
