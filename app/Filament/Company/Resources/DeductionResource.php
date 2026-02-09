<?php

namespace App\Filament\Company\Resources;

use App\Enums\CompanyTypes;
use App\Enums\DeductionReason;
use App\Enums\DeductionStatus;
use App\Enums\DeductionType;
use App\Enums\EmployeeAssignedStatus;
use App\Filament\Company\Resources\DeductionResource\Pages;
use App\Models\Company;
use App\Models\Deduction;
use App\Models\Employee;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DeductionResource extends Resource
{
    protected static ?string $model = Deduction::class;

    protected static ?string $navigationIcon = 'heroicon-o-minus-circle';

    protected static ?string $navigationLabel = 'الخصومات';

    protected static ?string $modelLabel = 'خصم';

    protected static ?string $pluralModelLabel = 'الخصومات';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        if ($user instanceof Company) {
            return in_array($user->type, [CompanyTypes::PROVIDER, CompanyTypes::CLIENT]);
        }

        if ($user instanceof User) {
            return $user->can('view_any_deduction');
        }

        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();

        if ($user instanceof Company) {
            $companyId = $user->id;
            $companyType = $user->type;
        } elseif ($user instanceof User) {
            $user->load('company');
            $companyId = $user->company_id;
            $companyType = $user->company ? $user->company->type : null;
        } else {
            $companyId = null;
            $companyType = null;
        }

        if ($companyType === CompanyTypes::PROVIDER) {
            // Provider: see deductions for their own employees
            return parent::getEloquentQuery()
                ->whereHas('employee', fn($q) => $q->where('company_id', $companyId));
        } else {
            // Client: see deductions they created or for employees assigned to them
            return parent::getEloquentQuery()
                ->where(function ($q) use ($companyId) {
                    $q->where('created_by_company_id', $companyId)
                      ->orWhereHas('employee.assigned', fn($sq) =>
                          $sq->where('employee_assigned.company_id', $companyId)
                             ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
                      );
                });
        }
    }

    private static function getCompanyType(): ?string
    {
        $user = Filament::auth()->user();
        if ($user instanceof Company) {
            return $user->type;
        }
        if ($user instanceof User && $user->company) {
            return $user->company->type;
        }
        return null;
    }

    private static function getCompanyId(): ?int
    {
        $user = Filament::auth()->user();
        if ($user instanceof Company) {
            return $user->id;
        }
        if ($user instanceof User) {
            return $user->company_id;
        }
        return null;
    }

    public static function form(Form $form): Form
    {
        $companyType = self::getCompanyType();
        $companyId = self::getCompanyId();

        // Get employees based on company type
        if ($companyType === CompanyTypes::PROVIDER) {
            $employees = Employee::where('company_id', $companyId)->get();
        } else {
            $employees = Employee::whereHas('assigned', fn($q) =>
                $q->where('employee_assigned.company_id', $companyId)
                  ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
            )->get();
        }

        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الخصم')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('الموظف')
                            ->options($employees->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('payroll_month')
                            ->label('شهر الراتب')
                            ->type('month')
                            ->required()
                            ->default(now()->format('Y-m')),

                        Forms\Components\Select::make('type')
                            ->label('نوع الخصم')
                            ->options(DeductionType::getTranslatedEnum())
                            ->required()
                            ->live()
                            ->default(DeductionType::FIXED),

                        Forms\Components\Select::make('reason')
                            ->label('سبب الخصم')
                            ->options(DeductionReason::getTranslatedEnum())
                            ->required()
                            ->default(DeductionReason::OTHER),
                    ])->columns(2),

                Forms\Components\Section::make('تفاصيل الخصم')
                    ->schema([
                        Forms\Components\TextInput::make('days')
                            ->label('عدد الأيام')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->required(fn(Forms\Get $get) => $get('type') === DeductionType::DAYS)
                            ->visible(fn(Forms\Get $get) => $get('type') === DeductionType::DAYS)
                            ->live(onBlur: true),

                        Forms\Components\TextInput::make('daily_rate')
                            ->label('سعر اليوم')
                            ->numeric()
                            ->prefix('SAR')
                            ->step(0.01)
                            ->visible(fn(Forms\Get $get) => $get('type') === DeductionType::DAYS)
                            ->live(onBlur: true)
                            ->helperText('الراتب الأساسي / 30 يوم'),

                        Forms\Components\TextInput::make('amount')
                            ->label('المبلغ')
                            ->numeric()
                            ->prefix('SAR')
                            ->step(0.01)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state) {})
                            ->helperText(fn(Forms\Get $get) =>
                                $get('type') === DeductionType::DAYS
                                    ? 'سيتم حسابه تلقائياً: عدد الأيام × سعر اليوم'
                                    : 'أدخل المبلغ الثابت'
                            ),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف / ملاحظات')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('الحالة')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('حالة الخصم')
                            ->options(DeductionStatus::getTranslatedEnum())
                            ->default(DeductionStatus::PENDING)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('الموظف')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('employee.emp_id')
                    ->label('رقم الموظف')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('payroll_month')
                    ->label('الشهر')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->formatStateUsing(fn($state) => DeductionType::getTranslatedEnum()[$state] ?? $state)
                    ->badge()
                    ->color(fn($state) => DeductionType::getColors()[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('reason')
                    ->label('السبب')
                    ->formatStateUsing(fn($state) => DeductionReason::getTranslatedEnum()[$state] ?? $state)
                    ->badge()
                    ->color(fn($state) => DeductionReason::getColors()[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('days')
                    ->label('الأيام')
                    ->alignCenter()
                    ->default('-'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('SAR')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn($state) => DeductionStatus::getTranslatedEnum()[$state] ?? $state)
                    ->badge()
                    ->color(fn($state) => DeductionStatus::getColors()[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('createdByCompany.name')
                    ->label('أنشئ بواسطة')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(DeductionStatus::getTranslatedEnum()),

                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options(DeductionType::getTranslatedEnum()),

                Tables\Filters\SelectFilter::make('reason')
                    ->label('السبب')
                    ->options(DeductionReason::getTranslatedEnum()),

                Tables\Filters\Filter::make('payroll_month')
                    ->form([
                        Forms\Components\TextInput::make('payroll_month')
                            ->label('شهر الراتب')
                            ->type('month'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['payroll_month'],
                            fn(Builder $query, $month) => $query->where('payroll_month', $month)
                        );
                    }),
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
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeductions::route('/'),
            'create' => Pages\CreateDeduction::route('/create'),
            'edit' => Pages\EditDeduction::route('/{record}/edit'),
        ];
    }
}
