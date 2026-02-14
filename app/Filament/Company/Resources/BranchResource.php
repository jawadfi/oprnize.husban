<?php

namespace App\Filament\Company\Resources;

use App\Enums\CompanyTypes;
use App\Enums\EmployeeAssignedStatus;
use App\Filament\Company\Resources\BranchResource\Pages;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'الفروع / Branches';

    protected static ?string $modelLabel = 'فرع / Branch';

    protected static ?string $pluralModelLabel = 'الفروع / Branches';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        // Only CLIENT companies can manage branches
        if ($user instanceof Company) {
            return $user->type === CompanyTypes::CLIENT;
        }

        // User model - check if their company is CLIENT
        if ($user instanceof User) {
            return $user->company?->type === CompanyTypes::CLIENT;
        }

        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();

        if ($user instanceof Company) {
            return parent::getEloquentQuery()->where('company_id', $user->id);
        }

        if ($user instanceof User && $user->company_id) {
            // Branch managers only see their own branches
            if ($user->isBranchManager()) {
                return parent::getEloquentQuery()
                    ->where('company_id', $user->company_id)
                    ->where('manager_id', $user->id);
            }
            return parent::getEloquentQuery()->where('company_id', $user->company_id);
        }

        return parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    public static function form(Form $form): Form
    {
        $user = Filament::auth()->user();
        $companyId = $user instanceof Company ? $user->id : ($user instanceof User ? $user->company_id : null);

        return $form->schema([
            Forms\Components\Section::make('معلومات الفرع / Branch Info')
                ->schema([
                    Forms\Components\Hidden::make('company_id')
                        ->default($companyId),

                    Forms\Components\TextInput::make('name')
                        ->label('اسم الفرع / Branch Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('location')
                        ->label('الموقع / Location')
                        ->maxLength(255),

                    Forms\Components\Select::make('manager_id')
                        ->label('مدير الفرع / Branch Manager')
                        ->options(function () use ($companyId) {
                            return User::where('company_id', $companyId)
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('اختر مستخدم ليكون مدير هذا الفرع'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('نشط / Active')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الفرع / Branch')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('location')
                    ->label('الموقع / Location')
                    ->searchable(),

                Tables\Columns\TextColumn::make('manager.name')
                    ->label('المدير / Manager')
                    ->sortable()
                    ->placeholder('لم يتم التعيين'),

                Tables\Columns\TextColumn::make('active_employees_count')
                    ->label('الموظفين / Employees')
                    ->counts('activeEmployees')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط / Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة / Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('manageEmployees')
                    ->label('إدارة الموظفين')
                    ->icon('heroicon-o-user-group')
                    ->color('info')
                    ->url(fn (Branch $record) => static::getUrl('employees', ['record' => $record])),
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
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
            'employees' => Pages\ManageBranchEmployees::route('/{record}/employees'),
        ];
    }
}
