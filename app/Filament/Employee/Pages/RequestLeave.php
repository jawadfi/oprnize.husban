<?php

namespace App\Filament\Employee\Pages;

use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveType;
use App\Models\LeaveRequest as LeaveRequestModel;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class RequestLeave extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.employee.pages.leave-request';

    protected static ?string $navigationLabel = 'Request Leave';

    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $employee = Filament::auth()->user();
        $lastLeave = $employee->leaveRequests()->latest()->first();

        return $form
            ->schema([
                // Smart info panel
                Section::make('📊 بياناتك — نظرة سريعة قبل تقديم الطلب')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('vacation_balance_display')
                                    ->label('رصيد الإجازة السنوية / Annual Leave Balance')
                                    ->content(fn () => ($employee->vacation_balance ?? 21) . ' يوم / days'),
                                Placeholder::make('passport_expiry_display')
                                    ->label('انتهاء جواز السفر / Passport Expiry')
                                    ->content(fn () => $employee->passport_expiry
                                        ? $employee->passport_expiry->format('d/m/Y')
                                        : '⚠️ غير مُدخل / Not set'),
                                Placeholder::make('visa_expiry_display')
                                    ->label('انتهاء التأشيرة / Visa Expiry')
                                    ->content(fn () => $employee->visa_expiry
                                        ? $employee->visa_expiry->format('d/m/Y')
                                        : '— غير مُدخل / Not set'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('last_leave_display')
                                    ->label('آخر إجازة / Last Leave Request')
                                    ->content(fn () => $lastLeave
                                        ? $lastLeave->start_date->format('d/m/Y') . ' — ' . LeaveRequestStatus::getTranslatedKey($lastLeave->status->value)
                                        : 'لا يوجد / None'),
                                Placeholder::make('pending_requests_display')
                                    ->label('طلبات قيد المراجعة / Pending Requests')
                                    ->content(fn () => $employee->leaveRequests()
                                        ->whereNotIn('status', [LeaveRequestStatus::APPROVED, LeaveRequestStatus::REJECTED])
                                        ->count() ?: 'لا يوجد / None'),
                            ]),
                    ])
                    ->collapsible(),

                Wizard::make([
                    Step::make('leave_type')
                        ->label('Step 1/3')
                        ->schema([
                            Section::make('Leave Type')
                                ->schema([
                                    Radio::make('leave_type')
                                        ->label('Leave Type *')
                                        ->options(LeaveType::getTranslatedEnum())
                                        ->required()
                                        ->inline()
                                        ->descriptions([
                                            LeaveType::ANNUAL => 'Paid annual leave',
                                            LeaveType::UNPAID => 'Unpaid leave',
                                            LeaveType::SICK => 'Sick leave',
                                            LeaveType::DEATH => 'Bereavement leave',
                                            LeaveType::NEWBORN => 'Newborn leave',
                                        ])
                                        ->columns(2),
                                ]),
                        ]),
                    Step::make('dates')
                        ->label('Step 2/3')
                        ->schema([
                            Section::make('Leave Dates')
                                ->schema([
                                    DatePicker::make('start_date')
                                        ->label('Leave Start Date *')
                                        ->required()
                                        ->native(false)
                                        ->displayFormat('d/m/Y')
                                        ->minDate(now())
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            if ($state && $get('end_date')) {
                                                $this->calculateDays($state, $get('end_date'), $set);
                                            }
                                        }),
                                    DatePicker::make('end_date')
                                        ->label('Leave End Date *')
                                        ->required()
                                        ->native(false)
                                        ->displayFormat('d/m/Y')
                                        ->minDate(fn (callable $get) => $get('start_date') ?: now())
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            if ($state && $get('start_date')) {
                                                $this->calculateDays($get('start_date'), $state, $set);
                                            }
                                        }),
                                    Placeholder::make('days_count_display')
                                        ->label('Number of Days')
                                        ->content(function (callable $get) {
                                            $startDate = $get('start_date');
                                            $endDate = $get('end_date');

                                            if ($startDate && $endDate) {
                                                $days = $this->calculateDaysCount($startDate, $endDate);
                                                return $days . ' days';
                                            }

                                            return '0 days';
                                        })
                                        ->visible(fn (callable $get) => $get('start_date') && $get('end_date')),
                                    // Passport validation warnings
                                    Placeholder::make('passport_warning')
                                        ->label('')
                                        ->content(function (callable $get) {
                                            $employee = Filament::auth()->user();
                                            $leaveType = $get('leave_type');
                                            $endDate = $get('end_date');

                                            // Only check passport for annual leave
                                            if ($leaveType !== \App\Enums\LeaveType::ANNUAL) {
                                                return '';
                                            }

                                            if (!$employee->passport_expiry) {
                                                return new HtmlString('<div style="background:#fff5f5;border:1px solid #fc8181;border-radius:8px;padding:12px;color:#c53030;font-weight:600;">🚫 لا توجد بيانات جواز سفر — يرجى مراجعة HR الشركة المؤجرة / No passport data — contact your provider HR</div>');
                                            }

                                            if ($endDate && $employee->passport_expiry->lt(Carbon::parse($endDate)->addMonths(6))) {
                                                return new HtmlString('<div style="background:#fffbeb;border:1px solid #f6ad55;border-radius:8px;padding:12px;color:#b7791f;font-weight:600;">⚠️ صلاحية الجواز أقل من 6 أشهر عند العودة — يرجى التجديد أولاً / Passport expires within 6 months of return — renew first</div>');
                                            }

                                            return '';
                                        })
                                        ->visible(fn (callable $get) => $get('leave_type') === \App\Enums\LeaveType::ANNUAL),
                                    // Balance warning
                                    Placeholder::make('balance_warning')
                                        ->label('')
                                        ->content(function (callable $get) {
                                            $employee = Filament::auth()->user();
                                            $startDate = $get('start_date');
                                            $endDate = $get('end_date');
                                            $leaveType = $get('leave_type');

                                            if ($leaveType !== \App\Enums\LeaveType::ANNUAL || !$startDate || !$endDate) {
                                                return '';
                                            }

                                            $days = $this->calculateDaysCount($startDate, $endDate);
                                            $balance = $employee->vacation_balance ?? 21;

                                            if ($days > $balance) {
                                                return new HtmlString('<div style="background:#fffbeb;border:1px solid #f6ad55;border-radius:8px;padding:12px;color:#b7791f;font-weight:600;">⚠️ أيام الإجازة المطلوبة (' . $days . ') أكثر من رصيدك (' . $balance . ') — سيُحال القرار للمشرف / Requested days (' . $days . ') exceed your balance (' . $balance . ')</div>');
                                            }

                                            return '';
                                        })
                                        ->visible(fn (callable $get) => $get('leave_type') === \App\Enums\LeaveType::ANNUAL && $get('start_date') && $get('end_date')),
                                ]),
                        ]),
                    Step::make('summary')
                        ->label('Step 3/3')
                        ->schema([
                            Section::make('Request Summary')
                                ->schema([
                                    Placeholder::make('summary_leave_type')
                                        ->label('Leave Type')
                                        ->content(function (callable $get) {
                                            $type = $get('leave_type');
                                            return $type ? LeaveType::getTranslatedKey($type) : '-';
                                        }),
                                    Placeholder::make('summary_start_date')
                                        ->label(function (callable $get) {
                                            $startDate = $get('start_date');
                                            if ($startDate) {
                                                $date = Carbon::parse($startDate);
                                                $dayName = $this->getEnglishDayName($date->dayOfWeek);
                                                return "Start Date ({$dayName})";
                                            }
                                            return 'Start Date';
                                        })
                                        ->content(function (callable $get) {
                                            $startDate = $get('start_date');
                                            return $startDate ? Carbon::parse($startDate)->format('d/m/Y') : '-';
                                        }),
                                    Placeholder::make('summary_end_date')
                                        ->label(function (callable $get) {
                                            $endDate = $get('end_date');
                                            if ($endDate) {
                                                $date = Carbon::parse($endDate);
                                                $dayName = $this->getEnglishDayName($date->dayOfWeek);
                                                return "End Date ({$dayName})";
                                            }
                                            return 'End Date';
                                        })
                                        ->content(function (callable $get) {
                                            $endDate = $get('end_date');
                                            return $endDate ? Carbon::parse($endDate)->format('d/m/Y') : '-';
                                        }),
                                    Placeholder::make('summary_days_count')
                                        ->label('Number of Days')
                                        ->content(function (callable $get) {
                                            $startDate = $get('start_date');
                                            $endDate = $get('end_date');

                                            if ($startDate && $endDate) {
                                                $days = $this->calculateDaysCount($startDate, $endDate);
                                                return $days . ' days';
                                            }

                                            return '-';
                                        }),
                                    Textarea::make('notes')
                                        ->label('Notes (Optional)')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ])->submitAction(new HtmlString(Blade::render(<<<BLADE
    <x-filament::button
        type="submit"
        size="lg"
    >
        Save
    </x-filament::button>
BLADE
                )))
                    ->persistStepInQueryString()
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $employee = Filament::auth()->user();

        // Block annual leave if passport data missing
        if ($data['leave_type'] === \App\Enums\LeaveType::ANNUAL) {
            if (!$employee->passport_expiry) {
                Notification::make()
                    ->title('لا توجد بيانات جواز سفر / No passport data')
                    ->body('يرجى مراجعة HR الشركة المؤجرة لتحديث بياناتك / Contact your provider HR to update your data.')
                    ->danger()
                    ->send();
                return;
            }

            $endDate = Carbon::parse($data['end_date']);
            if ($employee->passport_expiry->lt($endDate->copy()->addMonths(6))) {
                Notification::make()
                    ->title('صلاحية الجواز غير كافية / Passport expiry insufficient')
                    ->body('جواز سفرك ينتهي قبل 6 أشهر من تاريخ العودة. يرجى التجديد أولاً / Passport expires within 6 months of return date. Renew first.')
                    ->danger()
                    ->send();
                return;
            }
        }

        // Get client company (where employee is assigned) or fall back to provider company
        $clientCompanyId = $employee->company_assigned_id ?? $employee->company_id;
        
        if (!$clientCompanyId) {
            Notification::make()
                ->title('Error')
                ->body('You are not assigned to any company. Please contact your administrator.')
                ->danger()
                ->send();
            return;
        }

        $daysCount = $this->calculateDaysCount($data['start_date'], $data['end_date']);

        // New flow: start with supervisor approval (branch manager)
        LeaveRequestModel::create([
            'employee_id' => $employee->id,
            'company_id' => $clientCompanyId,
            'current_approver_company_id' => $clientCompanyId,
            'leave_type' => $data['leave_type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days_count' => $daysCount,
            'status' => LeaveRequestStatus::PENDING_SUPERVISOR_APPROVAL,
            'notes' => $data['notes'] ?? null,
        ]);

        Notification::make()
            ->title('تم تقديم طلب الإجازة بنجاح / Leave request submitted successfully')
            ->body('سيتم مراجعته من المشرف ثم الشركة المستأجرة ثم المؤجرة / Will be reviewed by supervisor, then client HR, then provider HR.')
            ->success()
            ->send();

        $this->form->fill();
    }

    protected function calculateDays($startDate, $endDate, callable $set): void
    {
        $days = $this->calculateDaysCount($startDate, $endDate);
        // The days count is displayed via Placeholder, so we don't need to set it
    }

    protected function calculateDaysCount($startDate, $endDate): int
    {
        if (!$startDate || !$endDate) {
            return 0;
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Calculate the difference in days (inclusive of both start and end dates)
        return $start->diffInDays($end) + 1;
    }

    protected function getEnglishDayName(int $dayOfWeek): string
    {
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return $days[$dayOfWeek] ?? '';
    }

    public function getTitle(): string | Htmlable
    {
        return 'Request Leave';
    }

    public function getHeading(): string | Htmlable
    {
        return 'Request Leave';
    }
}

