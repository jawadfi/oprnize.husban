<?php

namespace App\Filament\Company\Resources;

use App\Enums\CompanyTypes;
use App\Enums\LoanStatus;
use App\Filament\Company\Resources\LoanResource\Pages;
use App\Filament\Company\Resources\LoanResource\RelationManagers;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'السلف والقروض';

    protected static ?string $modelLabel = 'قرض';

    protected static ?string $pluralModelLabel = 'السلف والقروض';

    protected static ?int $navigationSort = 5;

    // ─── Access control: Provider companies only ─────────────────────────────

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        if ($user instanceof Company) {
            return $user->type === CompanyTypes::PROVIDER;
        }

        if ($user instanceof User) {
            if ($user->isBranchManager()) {
                return false;
            }
            return $user->company?->type === CompanyTypes::PROVIDER
                && $user->can('view_any_loan');
        }

        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', self::getCompanyId());
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

    // ─── Form ─────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        $companyId = self::getCompanyId();

        return $form->schema([
            Forms\Components\Section::make('بيانات القرض')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('employee_id')
                        ->label('الموظف')
                        ->options(
                            Employee::where('company_id', $companyId)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->required()
                        ->disabledOn('edit'),

                    Forms\Components\TextInput::make('start_month')
                        ->label('شهر بداية الاقتطاع')
                        ->type('month')
                        ->required()
                        ->helperText('سيبدأ اقتطاع القسط الشهري من هذا الشهر'),

                    Forms\Components\TextInput::make('amount')
                        ->label('إجمالي القرض')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->suffix('ريال')
                        ->live(debounce: 500)
                        ->afterStateUpdated(function (string|null $state, Set $set, Get $get) {
                            $months = (int) $get('months');
                            if ($months > 0 && (float) $state > 0) {
                                $set('monthly_deduction', round((float) $state / $months, 2));
                            }
                        }),

                    Forms\Components\TextInput::make('months')
                        ->label('عدد الأشهر')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->required()
                        ->live(debounce: 300)
                        ->afterStateUpdated(function (string|null $state, Set $set, Get $get) {
                            $amount = (float) $get('amount');
                            if ((int) $state > 0 && $amount > 0) {
                                $set('monthly_deduction', round($amount / (int) $state, 2));
                            }
                        }),

                    Forms\Components\TextInput::make('monthly_deduction')
                        ->label('القسط الشهري (ريال)')
                        ->numeric()
                        ->readOnly()
                        ->helperText('يُحسب تلقائياً = الإجمالي ÷ عدد الأشهر'),

                    Forms\Components\Textarea::make('notes')
                        ->label('ملاحظات')
                        ->columnSpanFull()
                        ->rows(2),
                ]),
        ]);
    }

    // ─── Table ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('الموظف')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('إجمالي القرض')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2) . ' ريال')
                    ->sortable(),

                Tables\Columns\TextColumn::make('monthly_deduction')
                    ->label('القسط الشهري')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2) . ' ريال'),

                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('المتبقي')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2) . ' ريال')
                    ->color(fn ($record) => (float) $record->remaining_amount <= 0 ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('start_month')
                    ->label('شهر البداية')
                    ->sortable(),

                Tables\Columns\TextColumn::make('months')
                    ->label('الأشهر'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state) => LoanStatus::getTranslatedEnum()[$state] ?? $state)
                    ->color(fn ($state) => LoanStatus::getColors()[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(LoanStatus::getTranslatedEnum()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (Loan $record) => $record->status === LoanStatus::ACTIVE),

                Tables\Actions\Action::make('process')
                    ->label('اقتطاع الشهر الحالي')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (Loan $record) => $record->status === LoanStatus::ACTIVE)
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد الاقتطاع')
                    ->modalDescription(
                        fn (Loan $record) => sprintf(
                            'سيتم إنشاء خصم بقيمة %s ريال للموظف %s لشهر %s',
                            number_format((float) $record->monthly_deduction, 2),
                            $record->employee?->name,
                            now()->format('Y-m')
                        )
                    )
                    ->action(function (Loan $record) {
                        $result = $record->processMonthlyDeduction(now()->format('Y-m'));

                        if ($result) {
                            Notification::make()
                                ->title('تم إنشاء القسط بنجاح')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('لم يُنشأ خصم')
                                ->body('الخصم موجود مسبقاً لهذا الشهر أو القرض غير نشط.')
                                ->warning()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('pause')
                    ->label('إيقاف مؤقت')
                    ->icon('heroicon-o-pause-circle')
                    ->color('gray')
                    ->visible(fn (Loan $record) => $record->status === LoanStatus::ACTIVE)
                    ->requiresConfirmation()
                    ->action(fn (Loan $record) => $record->update(['status' => LoanStatus::PAUSED])),

                Tables\Actions\Action::make('resume')
                    ->label('استئناف')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->visible(fn (Loan $record) => $record->status === LoanStatus::PAUSED)
                    ->requiresConfirmation()
                    ->action(fn (Loan $record) => $record->update(['status' => LoanStatus::ACTIVE])),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Loan $record) => $record->status !== LoanStatus::ACTIVE),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // ─── Relation managers ────────────────────────────────────────────────────

    public static function getRelationManagers(): array
    {
        return [
            RelationManagers\LoanDeductionsRelationManager::class,
        ];
    }

    // ─── Pages ────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoan::route('/create'),
            'edit'   => Pages\EditLoan::route('/{record}/edit'),
        ];
    }
}
