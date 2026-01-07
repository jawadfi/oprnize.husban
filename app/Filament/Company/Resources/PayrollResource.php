<?php

namespace App\Filament\Company\Resources;

use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use App\Enums\CompanyTypes;
use App\Enums\EmployeeAssignedStatus;
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
            // Provider Service: Show payrolls for employees where employee.company_id = company_id
            return parent::getEloquentQuery()
                ->whereHas('employee', fn($q) => $q->where('company_id', $companyId));
        } else {
            // Receive Service: Show payrolls for employees assigned to CLIENT company
            return parent::getEloquentQuery()
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
                            return $rule->where('company_id', $companyId);
                        },
                        ignoreRecord: true
                    ),
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
                            ->content(function ($get, $record) {
                                if ($record) {
                                    return number_format($record->total_other_allow, 2) . ' SAR';
                                }
                                $total = (float)($get('housing_allowance') ?? 0) +
                                         (float)($get('transportation_allowance') ?? 0) +
                                         (float)($get('food_allowance') ?? 0) +
                                         (float)($get('other_allowance') ?? 0);
                                return number_format($total, 2) . ' SAR';
                            }),
                        Forms\Components\Placeholder::make('total_salary')
                            ->label('Total Salary')
                            ->content(function ($get, $record) {
                                if ($record) {
                                    return number_format($record->total_salary, 2) . ' SAR';
                                }
                                $basic = (float)($get('basic_salary') ?? 0);
                                $totalAllow = (float)($get('housing_allowance') ?? 0) +
                                             (float)($get('transportation_allowance') ?? 0) +
                                             (float)($get('food_allowance') ?? 0) +
                                             (float)($get('other_allowance') ?? 0);
                                return number_format($basic + $totalAllow, 2) . ' SAR';
                            }),
                        Forms\Components\Placeholder::make('monthly_cost')
                            ->label('Monthly Cost')
                            ->content(function ($get, $record) {
                                if ($record) {
                                    return number_format($record->monthly_cost, 2) . ' SAR';
                                }
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
                            ->content(function ($get, $record) {
                                if ($record) {
                                    return number_format($record->total_additions, 2) . ' SAR';
                                }
                                $total = (float)($get('overtime_amount') ?? 0) +
                                         (float)($get('added_days_amount') ?? 0) +
                                         (float)($get('other_additions') ?? 0);
                                return number_format($total, 2) . ' SAR';
                            }),
                        Forms\Components\Placeholder::make('total_earning')
                            ->label('Total Earning')
                            ->content(function ($get, $record) {
                                if ($record) {
                                    return number_format($record->total_earning, 2) . ' SAR';
                                }
                                $totalPackage = (float)($get('total_package') ?? 0);
                                $fees = (float)($get('fees') ?? 0);
                                $totalAdditions = (float)($get('overtime_amount') ?? 0) +
                                                  (float)($get('added_days_amount') ?? 0) +
                                                  (float)($get('other_additions') ?? 0);
                                return number_format($totalPackage + $fees + $totalAdditions, 2) . ' SAR';
                            }),
                        Forms\Components\Placeholder::make('total_deductions')
                            ->label('Total Deductions')
                            ->content(function ($get, $record) {
                                if ($record) {
                                    return number_format($record->total_deductions, 2) . ' SAR';
                                }
                                $total = (float)($get('absence_unpaid_leave_deduction') ?? 0) +
                                         (float)($get('food_subscription_deduction') ?? 0) +
                                         (float)($get('other_deduction') ?? 0);
                                return number_format($total, 2) . ' SAR';
                            }),
                        Forms\Components\Placeholder::make('net_payment')
                            ->label('Net Payment')
                            ->content(function ($get, $record) {
                                if ($record) {
                                    return number_format($record->net_payment, 2) . ' SAR';
                                }
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Emp. Name')
                    ->searchable()
                    ->sortable()
                    ->limit(20),
                Tables\Columns\TextColumn::make('basic_salary')
                    ->label('Basic salary')
                    ->money('SAR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('housing_allowance')
                    ->label('Housing Allowance')
                    ->money('SAR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('transportation_allowance')
                    ->label('Transportation Allow')
                    ->money('SAR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('food_allowance')
                    ->label('Food Allowance')
                    ->money('SAR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('other_allowance')
                    ->label('Other Allowance')
                    ->money('SAR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_other_allow')
                    ->label('Total Other Allowance')
                    ->money('SAR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_salary')
                    ->label('Total Salary')
                    ->money('SAR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fees')
                    ->label('Fees')
                    ->money('SAR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('monthly_cost')
                    ->label('Monthly cost')
                    ->money('SAR')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                FilamentExportHeaderAction::make('export'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc')
            ->searchable(false);
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
            'index' => Pages\ListPayrolls::route('/'),
            'create' => Pages\CreatePayroll::route('/create'),
            'view' => Pages\ViewPayroll::route('/{record}'),
            'edit' => Pages\EditPayroll::route('/{record}/edit'),
        ];
    }
}
