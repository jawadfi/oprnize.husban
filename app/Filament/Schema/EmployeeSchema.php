<?php

namespace App\Filament\Schema;

use App\Enums\EmployeeAssignedStatus;
use App\Enums\EmployeeStatusStatus;
use App\Helpers\Helpers;
use App\Models\EmployeeAssigned;
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
                    Forms\Components\TextInput::make('emp_id')->label('رقم الموظف / Emp ID'),
                    Forms\Components\TextInput::make('job_title')->required(),
                    Forms\Components\TextInput::make('department')->required(),
                    Forms\Components\DatePicker::make('hire_date')->required(),
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
    public static function getTableColumns($withCompanyAssigned=true,$path='',$clientCompanyId=null): array
    {
        // When $clientCompanyId is given (client-side view), show assignment start_date as "Hiring Date"
        $hiringDateColumn = $clientCompanyId
            ? Tables\Columns\TextColumn::make('assignment_start_date')
                ->label('Hiring Date')
                ->state(function ($record) use ($clientCompanyId) {
                    // Use eager-loaded relationship if available, otherwise query
                    if ($record->relationLoaded('assigned') && $record->assigned->count()) {
                        $first = $record->assigned->first();
                        return $first?->pivot?->start_date;
                    }
                    return \App\Models\EmployeeAssigned::where('employee_id', $record->id)
                        ->where('company_id', $clientCompanyId)
                        ->where('status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                        ->orderByDesc('start_date')
                        ->value('start_date');
                })
                ->date('Y-m-d')
                ->toggleable()
                ->sortable(false)
                ->searchable(false)
            : Tables\Columns\TextColumn::make($path.'hire_date')
                ->label('Hiring Date')
                ->date('Y-m-d')
                ->toggleable()
                ->sortable()
                ->searchable();

        return [
            Tables\Columns\TextColumn::make('row_index')
                ->label('#')
                ->rowIndex(),
            Tables\Columns\TextColumn::make($path.'id')->weight('bold')->label('Nova Emp ID.')->sortable()->searchable(),
            Tables\Columns\TextColumn::make($path.'emp_id')->label('Emp.ID')->sortable()->searchable()->placeholder('-'),
            Tables\Columns\TextColumn::make($path.'name')->label('Name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make($path.'nationality')->label('Nationality')->sortable()->searchable(),
            Tables\Columns\TextColumn::make($path.'iqama_no')->label('Iqama No')->toggleable()->sortable()->searchable(),
            Tables\Columns\TextColumn::make($path.'location')
                ->label('Location')
                ->state(function ($record) use ($path) {
                    $state = data_get($record, $path . 'location');

                    if (filled($state)) {
                        return $state;
                    }

                    $employeeId = null;

                    if ($path === 'employee.') {
                        $employeeId = data_get($record, 'employee_id') ?: data_get($record, 'employee.id');
                    } else {
                        $employeeId = data_get($record, 'id');
                    }

                    if (! $employeeId) {
                        return '-';
                    }

                    $assignment = EmployeeAssigned::query()
                        ->with('branch:id,name,location')
                        ->where('employee_id', $employeeId)
                        ->where('status', EmployeeAssignedStatus::APPROVED)
                        ->latest('start_date')
                        ->first();

                    return $assignment?->branch?->location
                        ?? $assignment?->branch?->name
                        ?? '-';
                })
                ->toggleable()
                ->sortable()
                ->searchable(),
            $hiringDateColumn,
            Tables\Columns\TextColumn::make($path.'job_title')->label('Title')->sortable()->searchable(),
            Tables\Columns\TextColumn::make($path.'department')->label('Department')->toggleable()->sortable()->searchable(),
            Tables\Columns\TextColumn::make($path.'currentCompanyAssigned.name')
                ->label('Company Assigned')
                ->badge()
                ->visible(fn($livewire) => $withCompanyAssigned && $livewire?->activeTab===EmployeeStatusStatus::IN_SERVICE)
                ->sortable()
                ->searchable()
        ];
    }
}
