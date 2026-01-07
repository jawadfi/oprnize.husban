<?php

namespace App\Filament\Company\Resources;

use App\Filament\Company\Resources\UserResource\Pages;
use App\Filament\Company\Resources\UserResource\RelationManagers;
use App\Models\User;
use BezhanSalleh\FilamentShield\FilamentShield;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        
        // Company model has all access
        if ($user instanceof \App\Models\Company) {
            return true;
        }
        
        // User model needs permission to view users
        if ($user instanceof User) {
            return $user->can('view_any_UserResource');
        }
        
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getEloquentQuery(): Builder
    {
        $company = Filament::auth()->user();
        
        // If authenticated user is a Company, show its users
        if ($company instanceof \App\Models\Company) {
            return parent::getEloquentQuery()->where('company_id', $company->id);
        }
        
        // If authenticated user is a User, show users from same company
        if ($company instanceof User && $company->company_id) {
            return parent::getEloquentQuery()->where('company_id', $company->company_id);
        }
        
        return parent::getEloquentQuery();
    }

    public static function form(Form $form): Form
    {
        $company = Filament::auth()->user();
        $companyId = $company instanceof \App\Models\Company ? $company->id : ($company instanceof User ? $company->company_id : null);

        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(191)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->maxLength(191)
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create'),
                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name', fn (Builder $query) => $query->where('guard_name', 'company'))
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->required(),
                Forms\Components\Hidden::make('company_id')
                    ->default($companyId)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->separator(',')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
