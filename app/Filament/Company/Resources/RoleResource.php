<?php

namespace App\Filament\Company\Resources;

use App\Enums\CompanyTypes;
use App\Filament\Company\Resources\RoleResource\Pages;
use BezhanSalleh\FilamentShield\Resources\RoleResource as ShieldRoleResource;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleResource extends ShieldRoleResource
{
    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        
        // Both PROVIDER and CLIENT companies can manage roles
        if ($user instanceof \App\Models\Company) {
            return in_array($user->type, [CompanyTypes::PROVIDER, CompanyTypes::CLIENT]);
        }
        
        // User model - check if their company is provider or client
        if ($user instanceof \App\Models\User) {
            $company = $user->company;
            if ($company && in_array($company->type, [CompanyTypes::PROVIDER, CompanyTypes::CLIENT])) {
                // Shield generates permissions as lowercase: 'view_any_role' not 'view_any_Role'
                $permissionNames = ['view_any_role', 'view_any_Role', 'view_any_RoleResource'];
                
                foreach ($permissionNames as $permName) {
                    try {
                        if ($user->can($permName)) {
                            return true;
                        }
                    } catch (\Exception $e) {
                        // Permission doesn't exist, try next
                        continue;
                    }
                }
            }
        }
        
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        $user = Filament::auth()->user();
        
        // Both provider and client companies can create roles
        if ($user instanceof \App\Models\Company) {
            return in_array($user->type, [CompanyTypes::PROVIDER, CompanyTypes::CLIENT]);
        }
        
        // User model needs permission
        if ($user instanceof \App\Models\User) {
            $company = $user->company;
            if ($company && in_array($company->type, [CompanyTypes::PROVIDER, CompanyTypes::CLIENT])) {
                return $user->can('create_Role') || $user->can('create_role');
            }
        }
        
        return false;
    }

    public static function canEdit($record): bool
    {
        $user = Filament::auth()->user();
        
        // Both provider and client companies can edit roles
        if ($user instanceof \App\Models\Company) {
            return in_array($user->type, [CompanyTypes::PROVIDER, CompanyTypes::CLIENT]);
        }
        
        // User model needs permission
        if ($user instanceof \App\Models\User) {
            $company = $user->company;
            if ($company && in_array($company->type, [CompanyTypes::PROVIDER, CompanyTypes::CLIENT])) {
                return $user->can('update_Role') || $user->can('update_role');
            }
        }
        
        return false;
    }

    public static function canDelete($record): bool
    {
        $user = Filament::auth()->user();
        
        // Both provider and client companies can delete roles
        if ($user instanceof \App\Models\Company) {
            return in_array($user->type, [CompanyTypes::PROVIDER, CompanyTypes::CLIENT]);
        }
        
        // User model needs permission
        if ($user instanceof \App\Models\User) {
            $company = $user->company;
            if ($company && in_array($company->type, [CompanyTypes::PROVIDER, CompanyTypes::CLIENT])) {
                return $user->can('delete_Role') || $user->can('delete_role');
            }
        }
        
        return false;
    }

    public static function canDeleteAny(): bool
    {
        $user = Filament::auth()->user();
        
        // Both provider and client companies can delete roles
        if ($user instanceof \App\Models\Company) {
            return in_array($user->type, [CompanyTypes::PROVIDER, CompanyTypes::CLIENT]);
        }
        
        // User model needs permission
        if ($user instanceof \App\Models\User) {
            $company = $user->company;
            if ($company && in_array($company->type, [CompanyTypes::PROVIDER, CompanyTypes::CLIENT])) {
                return $user->can('delete_any_Role') || $user->can('delete_any_role');
            }
        }
        
        return false;
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('filament-shield::filament-shield.field.name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('guard_name')
                                    ->label(__('filament-shield::filament-shield.field.guard_name'))
                                    ->default('company')
                                    ->disabled()
                                    ->dehydrated()
                                    ->maxLength(255),
                            ])
                            ->columns([
                                'sm' => 2,
                                'lg' => 3,
                            ]),
                    ]),
                static::getShieldFormComponents(),
            ]);
    }


    public static function getEloquentQuery(): Builder
    {
        // Filter by company guard and eager load permissions
        return parent::getEloquentQuery()
            ->where('guard_name', 'company')
            ->with('permissions');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->weight('font-medium')
                    ->label(__('filament-shield::filament-shield.column.name'))
                    ->formatStateUsing(fn ($state): string => Str::headline($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('guard_name')
                    ->badge()
                    ->color('warning')
                    ->label(__('filament-shield::filament-shield.column.guard_name')),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->badge()
                    ->label(__('filament-shield::filament-shield.column.permissions'))
                    ->counts('permissions')
                    ->colors(['success']),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament-shield::filament-shield.column.updated_at'))
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit'),
                Tables\Actions\DeleteAction::make()
                    ->label('Delete')
                    ->using(function ($record) {
                        // Delete the role directly without accessing problematic relationships
                        $record->permissions()->detach();
                        
                        // Use DB facade to delete directly to avoid relationship issues
                        DB::table('roles')->where('id', $record->id)->delete();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->using(function ($records) {
                        foreach ($records as $record) {
                            // Delete each role directly without accessing problematic relationships
                            $record->permissions()->detach();
                            
                            // Use DB facade to delete directly to avoid relationship issues
                            DB::table('roles')->where('id', $record->id)->delete();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => Pages\ViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}

