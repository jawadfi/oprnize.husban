<?php

namespace App\Filament\Company\Resources;

use App\Enums\BranchEntryStatus;
use App\Enums\BranchEntryType;
use App\Enums\CompanyTypes;
use App\Filament\Company\Resources\BranchEntryResource\Pages;
use App\Models\Branch;
use App\Models\BranchEntry;
use App\Models\Company;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\EmployeeAddition;
use App\Models\EmployeeOvertime;
use App\Models\EmployeeTimesheet;
use App\Models\Payroll;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BranchEntryResource extends Resource
{
    protected static ?string $model = BranchEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'إدخالات الفرع / Branch Entries';

    protected static ?string $modelLabel = 'إدخال / Entry';

    protected static ?string $pluralModelLabel = 'إدخالات الفرع / Branch Entries';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        if ($user instanceof Company) {
            return $user->type === CompanyTypes::CLIENT;
        }

        if ($user instanceof User) {
            return $user->company?->type === CompanyTypes::CLIENT;
        }

        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * Get branches the current user can operate on
     */
    protected static function getUserBranches(): Builder
    {
        $user = Filament::auth()->user();

        if ($user instanceof Company) {
            return Branch::query()->where('company_id', $user->id)->where('is_active', true);
        }

        if ($user instanceof User) {
            $query = Branch::query()->where('company_id', $user->company_id)->where('is_active', true);

            // Branch managers only see their branches
            if ($user->isBranchManager()) {
                $query->where('manager_id', $user->id);
            }

            return $query;
        }

        return Branch::query()->whereRaw('1 = 0');
    }

    public static function getEloquentQuery(): Builder
    {
        $branchIds = static::getUserBranches()->pluck('id');

        return parent::getEloquentQuery()->whereIn('branch_id', $branchIds);
    }

    /**
     * Check if current user can only edit drafts (branch manager)
     */
    protected static function isBranchManager(): bool
    {
        $user = Filament::auth()->user();
        return $user instanceof User && $user->isBranchManager();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('معلومات أساسية / Basic Info')
                ->schema([
                    Forms\Components\Select::make('branch_id')
                        ->label('الفرع / Branch')
                        ->options(function () {
                            return static::getUserBranches()->pluck('name', 'id');
                        })
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('employee_id', null)),

                    Forms\Components\Select::make('employee_id')
                        ->label('الموظف / Employee')
                        ->options(function (Get $get) {
                            $branchId = $get('branch_id');
                            if (!$branchId) {
                                return [];
                            }
                            $branch = Branch::find($branchId);
                            if (!$branch) {
                                return [];
                            }
                            return $branch->activeEmployees()
                                ->get()
                                ->mapWithKeys(fn ($e) => [$e->id => $e->name . ' - ' . ($e->identity_number ?? '')])
                                ->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\TextInput::make('payroll_month')
                        ->label('شهر الرواتب / Payroll Month')
                        ->placeholder('YYYY-MM')
                        ->required()
                        ->default(now()->format('Y-m'))
                        ->maxLength(7),

                    Forms\Components\Select::make('entry_type')
                        ->label('نوع الإدخال / Entry Type')
                        ->options(BranchEntryType::getTranslatedEnum())
                        ->required()
                        ->reactive(),
                ])
                ->columns(2),

            // Attendance Fields
            Forms\Components\Section::make('بيانات الحضور / Attendance Data')
                ->schema([
                    Forms\Components\DatePicker::make('attendance_date')
                        ->label('التاريخ / Date')
                        ->required(),

                    Forms\Components\TimePicker::make('check_in')
                        ->label('وقت الحضور / Check In')
                        ->seconds(false),

                    Forms\Components\TimePicker::make('check_out')
                        ->label('وقت الانصراف / Check Out')
                        ->seconds(false),
                ])
                ->columns(3)
                ->visible(fn (Get $get) => $get('entry_type') === BranchEntryType::ATTENDANCE->value),

            // Deduction Fields
            Forms\Components\Section::make('بيانات الحسومات / Deduction Data')
                ->schema([
                    Forms\Components\TextInput::make('deduction_reason')
                        ->label('سبب الحسم / Reason')
                        ->required(),

                    Forms\Components\Textarea::make('deduction_description')
                        ->label('الوصف / Description')
                        ->rows(2),

                    Forms\Components\TextInput::make('deduction_days')
                        ->label('أيام الحسم / Days')
                        ->numeric()
                        ->minValue(0),

                    Forms\Components\TextInput::make('deduction_daily_rate')
                        ->label('المعدل اليومي / Daily Rate')
                        ->numeric()
                        ->prefix('SAR'),

                    Forms\Components\TextInput::make('deduction_amount')
                        ->label('مبلغ الحسم / Amount')
                        ->numeric()
                        ->prefix('SAR'),
                ])
                ->columns(2)
                ->visible(fn (Get $get) => $get('entry_type') === BranchEntryType::DEDUCTION->value),

            // Absence Fields
            Forms\Components\Section::make('بيانات الغياب / Absence Data')
                ->schema([
                    Forms\Components\TextInput::make('absence_days')
                        ->label('أيام الغياب / Days')
                        ->numeric()
                        ->required()
                        ->minValue(1),

                    Forms\Components\Select::make('absence_type')
                        ->label('نوع الغياب / Type')
                        ->options([
                            'paid' => 'مدفوع / Paid',
                            'unpaid' => 'غير مدفوع / Unpaid',
                        ])
                        ->required(),

                    Forms\Components\DatePicker::make('absence_from')
                        ->label('من تاريخ / From')
                        ->required(),

                    Forms\Components\DatePicker::make('absence_to')
                        ->label('إلى تاريخ / To')
                        ->required()
                        ->afterOrEqual('absence_from'),
                ])
                ->columns(2)
                ->visible(fn (Get $get) => $get('entry_type') === BranchEntryType::ABSENCE->value),

            // Overtime Fields
            Forms\Components\Section::make('بيانات العمل الإضافي / Overtime Data')
                ->schema([
                    Forms\Components\TextInput::make('overtime_hours')
                        ->label('ساعات العمل الإضافي / Hours')
                        ->numeric()
                        ->required()
                        ->minValue(0.5)
                        ->step(0.5),

                    Forms\Components\TextInput::make('overtime_amount')
                        ->label('مبلغ العمل الإضافي / Amount')
                        ->numeric()
                        ->prefix('SAR'),
                ])
                ->columns(2)
                ->visible(fn (Get $get) => $get('entry_type') === BranchEntryType::OVERTIME->value),

            // Addition Fields
            Forms\Components\Section::make('بيانات الإضافات / Addition Data')
                ->schema([
                    Forms\Components\TextInput::make('addition_amount')
                        ->label('المبلغ الإضافي / Amount')
                        ->numeric()
                        ->required()
                        ->prefix('SAR'),

                    Forms\Components\TextInput::make('addition_reason')
                        ->label('السبب / Reason')
                        ->required(),
                ])
                ->columns(2)
                ->visible(fn (Get $get) => $get('entry_type') === BranchEntryType::ADDITION->value),

            // Notes
            Forms\Components\Section::make('ملاحظات / Notes')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('ملاحظات / Notes')
                        ->rows(2),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('الفرع / Branch')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('employee.name')
                    ->label('الموظف / Employee')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('employee.company.name')
                    ->label('الشركة المزودة / Provider Company')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payroll_month')
                    ->label('الشهر / Month')
                    ->sortable(),

                Tables\Columns\TextColumn::make('entry_type')
                    ->label('النوع / Type')
                    ->formatStateUsing(fn ($state) => $state?->label())
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        BranchEntryType::ATTENDANCE => 'info',
                        BranchEntryType::DEDUCTION => 'danger',
                        BranchEntryType::ABSENCE => 'warning',
                        BranchEntryType::OVERTIME => 'success',
                        BranchEntryType::ADDITION => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة / Status')
                    ->formatStateUsing(fn ($state) => $state?->label())
                    ->badge()
                    ->color(fn ($state) => $state?->color() ?? 'gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('provider_company_id')
                    ->label('الشركة المزودة / Provider Company')
                    ->options(function () {
                        return Company::query()
                            ->where('type', CompanyTypes::PROVIDER)
                            ->whereIn('id', Employee::query()->select('company_id')->distinct())
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        $providerCompanyId = $data['value'] ?? null;

                        if (blank($providerCompanyId)) {
                            return $query;
                        }

                        return $query->whereHas('employee', function (Builder $employeeQuery) use ($providerCompanyId) {
                            $employeeQuery->where('company_id', $providerCompanyId);
                        });
                    }),

                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('الفرع / Branch')
                    ->options(function () {
                        return static::getUserBranches()->pluck('name', 'id');
                    }),

                Tables\Filters\SelectFilter::make('entry_type')
                    ->label('النوع / Type')
                    ->options(BranchEntryType::getTranslatedEnum()),

                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة / Status')
                    ->options(BranchEntryStatus::getTranslatedEnum()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (BranchEntry $record) => in_array($record->status, [BranchEntryStatus::DRAFT, BranchEntryStatus::REJECTED])),

                Tables\Actions\Action::make('submit')
                    ->label('اعتماد / Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('اعتماد الإدخال')
                    ->modalDescription('سيتم اعتماد الإدخال مباشرة وتطبيقه على الرواتب.')
                    ->visible(fn (BranchEntry $record) => $record->status === BranchEntryStatus::SUBMITTED)
                    ->action(function (BranchEntry $record) {
                        $user = Filament::auth()->user();

                        static::applyApprovedEntry($record);

                        $record->update([
                            'status' => BranchEntryStatus::APPROVED,
                            'submitted_by' => $user instanceof User ? $user->id : null,
                            'submitted_at' => now(),
                            'review_notes' => null,
                            'reviewed_by' => $user instanceof User ? $user->id : null,
                            'reviewed_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('تم إرسال واعتماد الإدخال بنجاح')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('رفض / Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('review_notes')
                            ->label('سبب الرفض / Rejection Reason')
                            ->required(),
                    ])
                    ->visible(function (BranchEntry $record) {
                        $user = Filament::auth()->user();
                        return $record->status === BranchEntryStatus::SUBMITTED
                            && ($user instanceof Company || ($user instanceof User && !$user->isBranchManager()));
                    })
                    ->action(function (BranchEntry $record, array $data) {
                        $user = Filament::auth()->user();
                        $record->update([
                            'status' => BranchEntryStatus::REJECTED,
                            'reviewed_by' => $user instanceof User ? $user->id : null,
                            'reviewed_at' => now(),
                            'review_notes' => $data['review_notes'],
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('تم رفض الإدخال')
                            ->danger()
                            ->send();
                    }),

                // Revert rejected entry back to draft for correction (flowchart: Rejected → Branch Employee)
                Tables\Actions\Action::make('revert_to_draft')
                    ->label('إعادة للتعديل / Revert to Draft')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('إعادة الإدخال المرفوض للتعديل')
                    ->modalDescription('سيتم إعادة هذا الإدخال لحالة المسودة حتى تتمكن من تعديله وإعادة إرساله.')
                    ->visible(fn (BranchEntry $record) => $record->status === BranchEntryStatus::REJECTED)
                    ->action(function (BranchEntry $record) {
                        $record->update([
                            'status' => BranchEntryStatus::DRAFT,
                            'reviewed_by' => null,
                            'reviewed_at' => null,
                            'review_notes' => null,
                            'submitted_by' => null,
                            'submitted_at' => null,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('تم إعادة الإدخال للمسودة')
                            ->body('يمكنك الآن تعديل الإدخال وإعادة إرساله')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (BranchEntry $record) => in_array($record->status, [BranchEntryStatus::DRAFT, BranchEntryStatus::REJECTED])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('submitAll')
                        ->label('اعتماد الكل / Approve All')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $user = Filament::auth()->user();
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === BranchEntryStatus::SUBMITTED) {
                                    static::applyApprovedEntry($record);

                                    $record->update([
                                        'status' => BranchEntryStatus::APPROVED,
                                        'submitted_by' => $user instanceof User ? $user->id : null,
                                        'submitted_at' => now(),
                                        'reviewed_by' => $user instanceof User ? $user->id : null,
                                        'reviewed_at' => now(),
                                        'review_notes' => null,
                                    ]);
                                    $count++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title("تم إرسال واعتماد {$count} إدخال")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('rejectAll')
                        ->label('رفض الكل / Reject All')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->visible(function () {
                            $user = Filament::auth()->user();
                            return $user instanceof Company || ($user instanceof User && !$user->isBranchManager());
                        })
                        ->form([
                            Forms\Components\Textarea::make('review_notes')
                                ->label('سبب الرفض / Rejection Reason')
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $user = Filament::auth()->user();
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === BranchEntryStatus::SUBMITTED) {
                                    $record->update([
                                        'status' => BranchEntryStatus::REJECTED,
                                        'reviewed_by' => $user instanceof User ? $user->id : null,
                                        'reviewed_at' => now(),
                                        'review_notes' => $data['review_notes'],
                                    ]);
                                    $count++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title("تم رفض {$count} إدخال")
                                ->danger()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function applyApprovedEntry(BranchEntry $record): void
    {
        $month = (string) $record->payroll_month;

        switch ($record->entry_type) {
            case BranchEntryType::OVERTIME:
                EmployeeOvertime::create([
                    'employee_id' => $record->employee_id,
                    'company_id' => $record->branch->company_id,
                    'payroll_month' => $month,
                    'hours' => (float) ($record->overtime_hours ?? 0),
                    'rate_per_hour' => 0,
                    'amount' => (float) ($record->overtime_amount ?? 0),
                    'notes' => $record->notes,
                    'is_recurring' => false,
                    'status' => 'approved',
                    'created_by_company_id' => $record->branch->company_id,
                ]);
                Payroll::syncFromEntries($record->employee_id, $record->branch->company_id, $month);
                break;

            case BranchEntryType::ADDITION:
                EmployeeAddition::create([
                    'employee_id' => $record->employee_id,
                    'company_id' => $record->branch->company_id,
                    'payroll_month' => $month,
                    'amount' => (float) ($record->addition_amount ?? 0),
                    'reason' => $record->addition_reason,
                    'description' => $record->notes,
                    'is_recurring' => false,
                    'status' => 'approved',
                    'created_by_company_id' => $record->branch->company_id,
                ]);
                Payroll::syncFromEntries($record->employee_id, $record->branch->company_id, $month);
                break;

            case BranchEntryType::DEDUCTION:
                Deduction::create([
                    'employee_id' => $record->employee_id,
                    'company_id' => $record->branch->company_id,
                    'payroll_month' => $month,
                    'type' => ($record->deduction_days ?? 0) > 0 ? 'days' : 'fixed',
                    'reason' => $record->deduction_reason ?: 'other',
                    'description' => $record->deduction_description ?: $record->notes,
                    'days' => $record->deduction_days,
                    'daily_rate' => $record->deduction_daily_rate,
                    'amount' => (float) ($record->deduction_amount ?? 0),
                    'status' => 'approved',
                    'is_recurring' => false,
                    'created_by_company_id' => $record->branch->company_id,
                ]);
                Payroll::syncFromEntries($record->employee_id, $record->branch->company_id, $month);
                break;

            case BranchEntryType::ATTENDANCE:
                static::applyAttendanceOrAbsenceToTimesheet($record, 'P');
                Payroll::syncFromEntries($record->employee_id, $record->branch->company_id, $month);
                break;

            case BranchEntryType::ABSENCE:
                $statusCode = $record->absence_type === 'paid' ? 'L' : 'X';
                static::applyAttendanceOrAbsenceToTimesheet($record, $statusCode);
                Payroll::syncFromEntries($record->employee_id, $record->branch->company_id, $month);
                break;
        }
    }

    protected static function applyAttendanceOrAbsenceToTimesheet(BranchEntry $record, string $statusCode): void
    {
        $parts = explode('-', (string) $record->payroll_month);
        $year = (int) ($parts[0] ?? now()->year);
        $month = (int) ($parts[1] ?? now()->month);
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        $timesheet = EmployeeTimesheet::firstOrCreate(
            [
                'employee_id' => $record->employee_id,
                'company_id' => $record->branch->company_id,
                'year' => $year,
                'month' => $month,
            ],
            [
                'attendance_data' => array_fill_keys(range(1, $daysInMonth), 'P'),
            ]
        );

        $attendanceData = $timesheet->attendance_data ?? [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            if (!array_key_exists($d, $attendanceData) && !array_key_exists((string) $d, $attendanceData)) {
                $attendanceData[$d] = 'P';
            }
        }

        if ($record->entry_type === BranchEntryType::ATTENDANCE && $record->attendance_date) {
            $attendanceDate = Carbon::parse($record->attendance_date);
            if ($attendanceDate->year === $year && $attendanceDate->month === $month) {
                $attendanceData[(string) $attendanceDate->day] = $statusCode;
            }
        }

        if ($record->entry_type === BranchEntryType::ABSENCE) {
            if ($record->absence_from && $record->absence_to) {
                $from = Carbon::parse($record->absence_from)->startOfDay();
                $to = Carbon::parse($record->absence_to)->endOfDay();

                if ($to->lt($from)) {
                    [$from, $to] = [$to, $from];
                }

                $cursor = $from->copy();
                while ($cursor->lte($to)) {
                    if ($cursor->year === $year && $cursor->month === $month) {
                        $attendanceData[(string) $cursor->day] = $statusCode;
                    }
                    $cursor->addDay();
                }
            }
        }

        $timesheet->attendance_data = $attendanceData;
        $timesheet->recalculateTotals();
        $timesheet->save();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranchEntries::route('/'),
            'create' => Pages\CreateBranchEntry::route('/create'),
            'edit' => Pages\EditBranchEntry::route('/{record}/edit'),
        ];
    }
}
