<?php

namespace App\Filament\Schema;

use App\Enums\EmployeeStatusStatus;
use App\Helpers\Helpers;
use Filament\Forms;
use Filament\Tables;
class EmployeeSchema
{
    public static function getFormComponents(): array
    {
        return [
            Forms\Components\Tabs::make()->tabs([
                Forms\Components\Tabs\Tab::make('Personal Information')
                    ->icon('heroicon-o-user')->columns(3)->schema([
                    Forms\Components\TextInput::make('name')->label('Employee Name')->required(),
                    Forms\Components\TextInput::make('job_title')->required(),
                    Forms\Components\TextInput::make('department')->required(),
                    Forms\Components\DatePicker::make('hire_date')->required(),
                    Forms\Components\TextInput::make('location'),
                    Forms\Components\TextInput::make('iqama_no'),
                    Forms\Components\TextInput::make('identity_number')
                        ->unique(ignoreRecord:true)
                        ->label('ID Number')->required(),
                    Forms\Components\Select::make('nationality')
                        ->options(Helpers::same_key_value(config('helpers.nationalities')))
                        ->preload()
                        ->searchable()
                        ->required(),
                ]),
                Forms\Components\Tabs\Tab::make('Credential')->icon('heroicon-o-key')->schema([
                    Forms\Components\TextInput::make('email')->email()->unique(ignoreRecord:true),
                    Forms\Components\TextInput::make('password')->password(),
                ])->columns(2)
            ])->columnSpanFull(),

        ];
    }
    public static function getTableColumns($withCompanyAssigned=true,$path=''): array
    {
        return [
            Tables\Columns\TextColumn::make($path.'id')->weight('bold')->prefix('#')->label('Employee ID')->sortable()->searchable(),
            Tables\Columns\TextColumn::make($path.'name')->label('Employee Name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make($path.'nationality')->sortable()->searchable(),
            Tables\Columns\TextColumn::make($path.'iqama_no')->toggleable()->sortable()->searchable(),
            Tables\Columns\TextColumn::make($path.'location')->toggleable()->sortable()->searchable(),
            Tables\Columns\TextColumn::make($path.'hire_date')->date('Y-m-d')->toggleable()->toggledHiddenByDefault()->sortable()->searchable(),
            Tables\Columns\TextColumn::make($path.'job_title')->sortable()->searchable(),
            Tables\Columns\TextColumn::make($path.'department')->toggleable()->sortable()->searchable(),
            Tables\Columns\TextColumn::make($path.'identity_number')->toggleable()->label('ID Number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make($path.'currentCompanyAssigned.name')
                ->label('Company Assigned')
                ->badge()
                ->visible(fn($livewire) => $withCompanyAssigned && $livewire?->activeTab===EmployeeStatusStatus::IN_SERVICE)
                ->sortable()
                ->searchable()
        ];
    }
}
