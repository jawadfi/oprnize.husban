<?php

namespace App\Filament\Company\Resources;

use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use App\Enums\CompanyTypes;
use App\Enums\EmployeeAssignedStatus;
use App\Enums\PayrollStatus;
use App\Filament\Company\Resources\PayrollResource\Pages;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PayrollResource extends Resource
{
    protected static ?string $model = Payroll::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Payroll';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        
        // Company model - check type
        if ($user instanceof Company) {
            return in_array($user->type, [CompanyTypes::PROVIDER, CompanyTypes::CLIENT]);
        }
        
        // User model - check permission
        // Filament Shield generates permissions based on model name, not Resource name
        if ($user instanceof User) {
            return $user->can('view_any_payroll');
        }
        
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();
        
        if ($user instanceof Company) {
            $companyId = $user->id;
            $companyType = $user->type;
        } elseif ($user instanceof \App\Models\User) {
            // Ensure company relationship is loaded
            $user->load('company');
            $companyId = $user->company_id;
            $companyType = $user->company ? $user->company->type : null;
        } else {
            $companyId = null;
            $companyType = null;
        }
        
        if ($companyType === CompanyTypes::PROVIDER) {
            // Provider Service: Show payrolls created by this provider for its employees
            return parent::getEloquentQuery()
                ->where('company_id', $companyId)
                ->whereHas('employee', fn($q) => $q->where('company_id', $companyId));
        } else {
            // Receive Service: Show only payrolls created by this CLIENT (company_id = client)
            return parent::getEloquentQuery()
                ->where('company_id', $companyId)
                ->whereHas('employee.assigned', fn($q) => 
                    $q->where('employee_assigned.company_id', $companyId)
                      ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
                );
        }
    }

    public static function form(Form $form): Form
    {
        $user = Filament::auth()->user();
        
        // Get employees based on company type
        if ($user->type === CompanyTypes::PROVIDER) {
            $employees = Employee::where('company_id', $user->id)->get();
        } else {
            $employees = Employee::whereHas('assigned', fn($q) => 
                $q->where('employee_assigned.company_id', $user->id)
                  ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
            )->get();
        }

        return $form
            ->schema([
                Forms\Components\Select::make('employee_id')
                    ->label('Employee')
                    ->options($employees->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn($context) => $context === 'edit')
                    ->dehydrated(fn($context) => $context !== 'edit')
                    ->unique(
                        table: 'payrolls',
                        column: 'employee_id',
                        modifyRuleUsing: function ($rule, $get) {
                            $user = Filament::auth()->user();
                            $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);
                            return $rule->where('company_id', $companyId)->where('payroll_month', $get('payroll_month'));
                        },
                        ignoreRecord: true
                    ),
                Forms\Components\TextInput::make('payroll_month')
                    ->label('Payroll Month')
                    ->type('month')
                    ->required()
                    ->default(now()->format('Y-m'))
                    ->disabled(fn($context) => $context === 'edit')
                    ->dehydrated(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(PayrollStatus::getTranslatedEnum())
                    ->default(PayrollStatus::DRAFT)
                    ->disabled()
                    ->dehydrated()
                    ->visible(fn($context) => $context === 'edit'),
                Forms\Components\Section::make('Salary Information')
                    ->schema([
                        Forms\Components\TextInput::make('basic_salary')
                            ->label('Basic Salary')
                            ->numeric()
                            ->required()
                            ->prefix('SAR')
                            ->default(0)
                            ->step(0.01)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('housing_allowance')
                            ->label('Housing Allowance')
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->step(0.01)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('transportation_allowance')
                            ->label('Transportation Allowance')
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->step(0.01)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('food_allowance')
                            ->label('Food Allowance')
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->step(0.01)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('other_allowance')
                            ->label('Other Allowance')
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->step(0.01)
                            ->live(onBlur: true),
                    ])->columns(2),
                Forms\Components\Section::make('Additional Costs')
                    ->schema([
                        Forms\Components\TextInput::make('fees')
                            ->label('Monthly Fees')
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->step(0.01)
                            ->live(onBlur: true),
                    ]),
                Forms\Components\Section::make('Earnings')
                    ->schema([
                        Forms\Components\TextInput::make('total_package')
                            ->label('Total Package')
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->step(0.01)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('work_days')
                            ->label('Work Days')
                            ->numeric()
                            ->default(0)
                            ->integer()
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('added_days')
                            ->label('Added Days')
                            ->numeric()
                            ->default(0)
                            ->integer()
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('overtime_hours')
                            ->label('OT HRS.')
                            ->numeric()
                            ->default(0)
                            ->step(0.01)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('overtime_amount')
                            ->label('Overtime Amount')
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->step(0.01)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('added_days_amount')
                            ->label('Added Days Amount')
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->step(0.01)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('other_additions')
                            ->label('Other Additions')
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->step(0.01)
                            ->live(onBlur: true),
                    ])->columns(2),
                Forms\Components\Section::make('Deductions')
                    ->schema([
                        Forms\Components\TextInput::make('absence_days')
                            ->label('Absence Days')
                            ->numeric()
                            ->default(0)
                            ->integer()
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('absence_unpaid_leave_deduction')
                            ->label('AB & UL')
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->step(0.01)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('food_subscription_deduction')
                            ->label('Food Subscription Deduction')
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->step(0.01)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('other_deduction')
                            ->label('Other Deduction')
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->step(0.01)
                            ->live(onBlur: true),
                    ])->columns(2),
                Forms\Components\Section::make('Calculated Totals')
                    ->schema([
                        Forms\Components\Placeholder::make('total_other_allow')
                            ->label('Total Other Allow')
                            ->content(function ($get) {
                                $total = (float)($get('housing_allowance') ?? 0) +
                                         (float)($get('transportation_allowance') ?? 0) +
                                         (float)($get('food_allowance') ?? 0) +
                                         (float)($get('other_allowance') ?? 0);
                                return number_format($total, 2) . ' SAR';
                            }),
                        Forms\Components\Placeholder::make('total_salary')
                            ->label('Total Salary')
                            ->content(function ($get) {
                                $basic = (float)($get('basic_salary') ?? 0);
                                $totalAllow = (float)($get('housing_allowance') ?? 0) +
                                             (float)($get('transportation_allowance') ?? 0) +
                                             (float)($get('food_allowance') ?? 0) +
                                             (float)($get('other_allowance') ?? 0);
                                return number_format($basic + $totalAllow, 2) . ' SAR';
                            }),
                        Forms\Components\Placeholder::make('monthly_cost')
                            ->label('Monthly Cost')
                            ->content(function ($get) {
                                $basic = (float)($get('basic_salary') ?? 0);
                                $totalAllow = (float)($get('housing_allowance') ?? 0) +
                                             (float)($get('transportation_allowance') ?? 0) +
                                             (float)($get('food_allowance') ?? 0) +
                                             (float)($get('other_allowance') ?? 0);
                                $fees = (float)($get('fees') ?? 0);
                                return number_format($basic + $totalAllow + $fees, 2) . ' SAR';
                            }),
                        Forms\Components\Placeholder::make('total_additions')
                            ->label('Total Additions')
                            ->content(function ($get) {
                                $total = (float)($get('overtime_amount') ?? 0) +
                                         (float)($get('added_days_amount') ?? 0) +
                                         (float)($get('other_additions') ?? 0);
                                return number_format($total, 2) . ' SAR';
                            }),
                        Forms\Components\Placeholder::make('total_earning')
                            ->label('Total Earning')
                            ->content(function ($get) {
                                $totalPackage = (float)($get('total_package') ?? 0);
                                $fees = (float)($get('fees') ?? 0);
                                $totalAdditions = (float)($get('overtime_amount') ?? 0) +
                                                  (float)($get('added_days_amount') ?? 0) +
                                                  (float)($get('other_additions') ?? 0);
                                return number_format($totalPackage + $fees + $totalAdditions, 2) . ' SAR';
                            }),
                        Forms\Components\Placeholder::make('total_deductions')
                            ->label('Total Deductions')
                            ->content(function ($get) {
                                $total = (float)($get('absence_unpaid_leave_deduction') ?? 0) +
                                         (float)($get('food_subscription_deduction') ?? 0) +
                                         (float)($get('other_deduction') ?? 0);
                                return number_format($total, 2) . ' SAR';
                            }),
                        Forms\Components\Placeholder::make('net_payment')
                            ->label('Net Payment')
                            ->content(function ($get) {
                                $totalPackage = (float)($get('total_package') ?? 0);
                                $fees = (float)($get('fees') ?? 0);
                                $totalAdditions = (float)($get('overtime_amount') ?? 0) +
                                                  (float)($get('added_days_amount') ?? 0) +
                                                  (float)($get('other_additions') ?? 0);
                                $totalEarning = $totalPackage + $fees + $totalAdditions;
                                $totalDeductions = (float)($get('absence_unpaid_leave_deduction') ?? 0) +
                                                   (float)($get('food_subscription_deduction') ?? 0) +
                                                   (float)($get('other_deduction') ?? 0);
                                return number_format($totalEarning - $totalDeductions, 2) . ' SAR';
                            }),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Filament::auth()->user();
        $companyType = null;
        if ($user instanceof Company) {
            $companyType = $user->type;
        } elseif ($user instanceof User && $user->company) {
            $companyType = $user->company->type;
        }

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.emp_id')
                    ->label('Emp. ID')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('employee', function ($q) use ($search) {
                            $q->where('emp_id', 'like', "%{$search}%")
                              ->orWhere('name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable()
                    ->alignCenter()
                    ->width('80px'),
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Emp. Name')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->alignCenter()
                    ->width('150px'),
                Tables\Columns\TextColumn::make('payroll_month')
                    ->label('Month')
                    ->sortable()
                    ->alignCenter()
                    ->width('90px'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn($state) => PayrollStatus::getTranslatedEnum()[$state] ?? $state)
                    ->badge()
                    ->color(fn($state) => PayrollStatus::getColors()[$state] ?? 'gray')
                    ->alignCenter()
                    ->width('110px'),
                Tables\Columns\IconColumn::make('is_modified')
                    ->label('معدل')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('warning')
                    ->falseIcon('heroicon-o-check-circle')
                    ->falseColor('success')
                    ->alignCenter()
                    ->width('70px'),
                Tables\Columns\TextColumn::make('basic_salary')
                    ->label('Basic')
                    ->money('SAR')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable()
                    ->width('100px'),
                Tables\Columns\TextColumn::make('housing_allowance')
                    ->label('Housing')
                    ->money('SAR')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->width('100px'),
                Tables\Columns\TextColumn::make('transportation_allowance')
                    ->label('Transport')
                    ->money('SAR')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->width('100px'),
                Tables\Columns\TextColumn::make('food_allowance')
                    ->label('Food')
                    ->money('SAR')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->width('100px'),
                Tables\Columns\TextColumn::make('other_allowance')
                    ->label('Other')
                    ->money('SAR')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->width('100px'),
                Tables\Columns\TextColumn::make('total_other_allow')
                    ->label('Total Allow')
                    ->money('SAR')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable()
                    ->width('110px'),
                Tables\Columns\TextColumn::make('total_salary')
                    ->label('Total Salary')
                    ->money('SAR')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable()
                    ->width('120px'),
                Tables\Columns\TextColumn::make('fees')
                    ->label('Fees')
                    ->money('SAR')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable()
                    ->width('100px'),
                Tables\Columns\TextColumn::make('monthly_cost')
                    ->label('Monthly Cost')
                    ->money('SAR')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable()
                    ->width('120px'),
                Tables\Columns\TextColumn::make('overtime_hours')
                    ->label('OT Hours')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable()
                    ->width('90px'),
                Tables\Columns\TextColumn::make('overtime_amount')
                    ->label('Total Overtime')
                    ->money('SAR')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable()
                    ->width('120px'),
                Tables\Columns\TextColumn::make('other_additions')
                    ->label('Other Additions')
                    ->money('SAR')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->width('120px'),
                Tables\Columns\TextColumn::make('net_payment')
                    ->label('Net Payment')
                    ->money('SAR')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable()
                    ->weight('bold')
                    ->width('130px'),
                Tables\Columns\TextColumn::make('total_without_ot')
                    ->label('Without OT')
                    ->money('SAR')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable()
                    ->width('120px'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(PayrollStatus::getTranslatedEnum()),
            ])
            ->headerActions([
                FilamentExportHeaderAction::make('export'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                // CLIENT: Submit to Provider (send movements/deductions)
                Tables\Actions\Action::make('submit_to_provider')
                    ->label('إرسال للشركة الأم')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('إرسال الحركات للشركة الأم')
                    ->modalDescription('سيتم إرسال جميع الحركات والخصومات والإضافات للشركة الأم لاحتساب الراتب')
                    ->visible(fn(Payroll $record) =>
                        $companyType === CompanyTypes::CLIENT &&
                        in_array($record->status, [PayrollStatus::DRAFT, PayrollStatus::REBACK])
                    )
                    ->action(function (Payroll $record) {
                        $record->update([
                            'status' => PayrollStatus::SUBMITTED_TO_PROVIDER,
                            'submitted_at' => now(),
                            'is_modified' => false,
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('تم الإرسال')
                            ->body('تم إرسال الحركات للشركة الأم بنجاح')
                            ->success()
                            ->send();
                    }),

                // PROVIDER: Calculate payroll
                Tables\Actions\Action::make('calculate')
                    ->label('احتساب الراتب')
                    ->icon('heroicon-o-calculator')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('احتساب الراتب')
                    ->modalDescription('سيتم احتساب راتب هذا الموظف')
                    ->visible(fn(Payroll $record) =>
                        $companyType === CompanyTypes::PROVIDER &&
                        in_array($record->status, [PayrollStatus::SUBMITTED_TO_PROVIDER, PayrollStatus::REBACK])
                    )
                    ->action(function (Payroll $record) {
                        // Sync from employee entries before calculating
                        Payroll::syncFromEntries($record->employee_id, $record->company_id, $record->payroll_month);
                        $record->refresh();

                        $record->update([
                            'status' => PayrollStatus::CALCULATED,
                            'calculated_at' => now(),
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('تم الاحتساب')
                            ->body('تم احتساب الراتب بنجاح')
                            ->success()
                            ->send();
                    }),

                // PROVIDER: Submit to Client (final payroll)
                Tables\Actions\Action::make('submit_to_client')
                    ->label('تقديم للعميل')
                    ->icon('heroicon-o-document-check')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('تقديم الراتب النهائي')
                    ->modalDescription('سيتم تقديم كشف الراتب النهائي للعميل للمراجعة')
                    ->visible(fn(Payroll $record) =>
                        $companyType === CompanyTypes::PROVIDER &&
                        $record->status === PayrollStatus::CALCULATED
                    )
                    ->action(function (Payroll $record) {
                        $record->update([
                            'status' => PayrollStatus::SUBMITTED_TO_CLIENT,
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('تم التقديم')
                            ->body('تم تقديم كشف الراتب النهائي للعميل')
                            ->success()
                            ->send();
                    }),

                // CLIENT: Reback (send back for modifications)
                Tables\Actions\Action::make('reback')
                    ->label('إرجاع للتعديل')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('إرجاع للتعديل - Reback')
                    ->form([
                        Forms\Components\Textarea::make('reback_reason')
                            ->label('سبب الإرجاع')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn(Payroll $record) =>
                        $companyType === CompanyTypes::CLIENT &&
                        $record->status === PayrollStatus::SUBMITTED_TO_CLIENT
                    )
                    ->action(function (Payroll $record, array $data) {
                        $record->update([
                            'status' => PayrollStatus::REBACK,
                            'reback_reason' => $data['reback_reason'],
                            'is_modified' => true,
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('تم الإرجاع')
                            ->body('تم إرجاع كشف الراتب للشركة الأم للتعديل')
                            ->warning()
                            ->send();
                    }),

                // CLIENT: Finalize (approve final payroll)
                Tables\Actions\Action::make('finalize')
                    ->label('اعتماد نهائي')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('اعتماد الراتب')
                    ->modalDescription('سيتم اعتماد كشف الراتب بشكل نهائي ولا يمكن التعديل عليه بعد ذلك')
                    ->visible(fn(Payroll $record) =>
                        $companyType === CompanyTypes::CLIENT &&
                        $record->status === PayrollStatus::SUBMITTED_TO_CLIENT
                    )
                    ->action(function (Payroll $record) {
                        $record->update([
                            'status' => PayrollStatus::FINALIZED,
                            'finalized_at' => now(),
                            'is_modified' => false,
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('تم الاعتماد')
                            ->body('تم اعتماد كشف الراتب بشكل نهائي')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->checkIfRecordIsSelectableUsing(fn (): bool => true)
            ->defaultSort('id', 'desc')
            ->searchable(false)
            ->striped(false)
            ->paginated([10, 25, 50]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\SelectCompanyPayroll::route('/'),
            'list' => Pages\ListPayrolls::route('/list'),
            'create' => Pages\CreatePayroll::route('/create'),
            'view' => Pages\ViewPayroll::route('/{record}'),
            'edit' => Pages\EditPayroll::route('/{record}/edit'),
        ];
    }
}
