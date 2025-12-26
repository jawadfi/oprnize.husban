<?php

namespace App\Filament\Employee\Pages;

use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveType;
use App\Models\LeaveRequest as LeaveRequestModel;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
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

class RequestLeave extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.employee.pages.leave-request';

    protected static ?string $navigationLabel = 'طلب اجازة';

    protected static ?int $navigationSort = 2;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('leave_type')
                        ->label('الخطوة 1/3')
                        ->schema([
                            Section::make('نوع الاجازة')
                                ->schema([
                                    Radio::make('leave_type')
                                        ->label('نوع الاجازة *')
                                        ->options(LeaveType::getTranslatedEnum())
                                        ->required()
                                        ->inline()
                                        ->descriptions([
                                            LeaveType::ANNUAL => 'إجازة سنوية مدفوعة',
                                            LeaveType::UNPAID => 'إجازة بدون راتب',
                                            LeaveType::SICK => 'إجازة مرضية',
                                            LeaveType::DEATH => 'إجازة وفاة',
                                            LeaveType::NEWBORN => 'إجازة مولود',
                                        ])
                                        ->columns(2),
                                ]),
                        ]),
                    Step::make('dates')
                        ->label('الخطوة 2/3')
                        ->schema([
                            Section::make('تواريخ الاجازة')
                                ->schema([
                                    DatePicker::make('start_date')
                                        ->label('تاريخ بداية الاجازة *')
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
                                        ->label('تاريخ نهاية الاجازة *')
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
                                    \Filament\Forms\Components\Placeholder::make('days_count_display')
                                        ->label('عدد الايام')
                                        ->content(function (callable $get) {
                                            $startDate = $get('start_date');
                                            $endDate = $get('end_date');
                                            
                                            if ($startDate && $endDate) {
                                                $days = $this->calculateDaysCount($startDate, $endDate);
                                                return $days . ' ايام';
                                            }
                                            
                                            return '0 ايام';
                                        })
                                        ->visible(fn (callable $get) => $get('start_date') && $get('end_date')),
                                ]),
                        ]),
                    Step::make('summary')
                        ->label('الخطوة 3/3')
                        ->schema([
                            Section::make('ملخص الطلب')
                                ->schema([
                                    \Filament\Forms\Components\Placeholder::make('summary_leave_type')
                                        ->label('نوع الاجازة')
                                        ->content(function (callable $get) {
                                            $type = $get('leave_type');
                                            return $type ? LeaveType::getTranslatedKey($type) : '-';
                                        }),
                                    \Filament\Forms\Components\Placeholder::make('summary_start_date')
                                        ->label(function (callable $get) {
                                            $startDate = $get('start_date');
                                            if ($startDate) {
                                                $date = Carbon::parse($startDate);
                                                $dayName = $this->getArabicDayName($date->dayOfWeek);
                                                return "تاريخ البداية ({$dayName})";
                                            }
                                            return 'تاريخ البداية';
                                        })
                                        ->content(function (callable $get) {
                                            $startDate = $get('start_date');
                                            return $startDate ? Carbon::parse($startDate)->format('d/m/Y') : '-';
                                        }),
                                    \Filament\Forms\Components\Placeholder::make('summary_end_date')
                                        ->label(function (callable $get) {
                                            $endDate = $get('end_date');
                                            if ($endDate) {
                                                $date = Carbon::parse($endDate);
                                                $dayName = $this->getArabicDayName($date->dayOfWeek);
                                                return "تاريخ النهاية ({$dayName})";
                                            }
                                            return 'تاريخ النهاية';
                                        })
                                        ->content(function (callable $get) {
                                            $endDate = $get('end_date');
                                            return $endDate ? Carbon::parse($endDate)->format('d/m/Y') : '-';
                                        }),
                                    \Filament\Forms\Components\Placeholder::make('summary_days_count')
                                        ->label('عدد الايام')
                                        ->content(function (callable $get) {
                                            $startDate = $get('start_date');
                                            $endDate = $get('end_date');
                                            
                                            if ($startDate && $endDate) {
                                                $days = $this->calculateDaysCount($startDate, $endDate);
                                                return $days . ' يوم';
                                            }
                                            
                                            return '-';
                                        }),
                                    Textarea::make('notes')
                                        ->label('ملاحظات (اختياري)')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ])
                    ->submitAction(\Filament\Forms\Components\Actions\Action::make('submit')
                        ->label('ارسال')
                        ->submit('submit'))
                    ->cancelAction(\Filament\Forms\Components\Actions\Action::make('cancel')
                        ->label('إلغاء')
                        ->color('gray')
                        ->action(fn () => $this->form->fill()))
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        
        $employee = Filament::auth()->user();
        
        $daysCount = $this->calculateDaysCount($data['start_date'], $data['end_date']);
        
        LeaveRequestModel::create([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'leave_type' => $data['leave_type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days_count' => $daysCount,
            'status' => LeaveRequestStatus::PENDING,
            'notes' => $data['notes'] ?? null,
        ]);

        Notification::make()
            ->title('تم إرسال طلب الاجازة بنجاح')
            ->body('سيتم مراجعة طلبك والرد عليه قريباً')
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

    protected function getArabicDayName(int $dayOfWeek): string
    {
        $days = [
            0 => 'يوم الاحد',
            1 => 'يوم الاثنين',
            2 => 'يوم الثلاثاء',
            3 => 'يوم الاربعاء',
            4 => 'يوم الخميس',
            5 => 'يوم الجمعة',
            6 => 'يوم السبت',
        ];

        return $days[$dayOfWeek] ?? '';
    }

    public function getTitle(): string | Htmlable
    {
        return 'طلب اجازة';
    }

    public function getHeading(): string | Htmlable
    {
        return 'طلب اجازة';
    }
}

