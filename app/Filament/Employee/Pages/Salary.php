<?php

namespace App\Filament\Employee\Pages;

use App\Models\Payroll;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Contracts\Support\Htmlable;

class Salary extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static string $view = 'filament.employee.pages.salary';

    protected static ?string $navigationLabel = 'My Salary';

    protected static ?int $navigationSort = 1;

    public function getTitle(): string | Htmlable
    {
        return 'My Salary';
    }

    public function getHeading(): string | Htmlable
    {
        return 'My Salary';
    }

    public function table(Table $table): Table
    {
        $employee = Filament::auth()->user();

        return $table
            ->query(
                Payroll::query()->where('employee_id', $employee->id)
            )
            ->columns([
                TextColumn::make('basic_salary')
                    ->label('Basic Salary')
                    ->money('SAR')
                    ->sortable(),
                TextColumn::make('housing_allowance')
                    ->label('Housing Allowance')
                    ->money('SAR')
                    ->sortable(),
                TextColumn::make('transportation_allowance')
                    ->label('Transportation Allowance')
                    ->money('SAR')
                    ->sortable(),
                TextColumn::make('food_allowance')
                    ->label('Food Allowance')
                    ->money('SAR')
                    ->sortable(),
                TextColumn::make('other_allowance')
                    ->label('Other Allowance')
                    ->money('SAR')
                    ->sortable(),
                TextColumn::make('total_other_allow')
                    ->label('Total Allowances')
                    ->money('SAR')
                    ->sortable(),
                TextColumn::make('total_salary')
                    ->label('Total Salary')
                    ->money('SAR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                TextColumn::make('fees')
                    ->label('Fees')
                    ->money('SAR')
                    ->sortable(),
                TextColumn::make('monthly_cost')
                    ->label('Monthly Cost')
                    ->money('SAR')
                    ->sortable()
                    ->color('warning'),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No salary records found')
            ->emptyStateDescription('Your salary information will appear here once it\'s been added.');
    }

    public function getCurrentPayroll()
    {
        $employee = Filament::auth()->user();
        return $employee->currentPayroll;
    }
}

