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
    public static function shouldRegisterNavigation(): bool
    {
        $user = Filament::auth()->user();
        
        // Only show roles for provider companies, not client companies
        if ($user instanceof \App\Models\Company) {
            return $user->type === CompanyTypes::PROVIDER;
        }
        
        // User model - check if their company is a provider and has permission
        if ($user instanceof \App\Models\User) {
            $company = $user->company;
            if ($company && $company->type === CompanyTypes::PROVIDER) {
            return $user->can('view_any_Role');
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
        // Don't eager load relationships that might cause issues
        return parent::getEloquentQuery()->with('permissions');
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
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

