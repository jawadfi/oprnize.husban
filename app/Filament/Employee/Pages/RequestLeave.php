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

    protected static ?string $navigationLabel = 'Request Leave';

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
                                    \Filament\Forms\Components\Placeholder::make('days_count_display')
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
                                ]),
                        ]),
                    Step::make('summary')
                        ->label('Step 3/3')
                        ->schema([
                            Section::make('Request Summary')
                                ->schema([
                                    \Filament\Forms\Components\Placeholder::make('summary_leave_type')
                                        ->label('Leave Type')
                                        ->content(function (callable $get) {
                                            $type = $get('leave_type');
                                            return $type ? LeaveType::getTranslatedKey($type) : '-';
                                        }),
                                    \Filament\Forms\Components\Placeholder::make('summary_start_date')
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
                                    \Filament\Forms\Components\Placeholder::make('summary_end_date')
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
                                    \Filament\Forms\Components\Placeholder::make('summary_days_count')
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
                ])
                    ->persistStepInQueryString()
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
            ->title('Leave request submitted successfully')
            ->body('Your request will be reviewed and responded to soon')
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

