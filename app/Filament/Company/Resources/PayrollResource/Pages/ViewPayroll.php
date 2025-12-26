<?php

namespace App\Filament\Company\Resources\PayrollResource\Pages;

use App\Filament\Company\Resources\PayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewPayroll extends ViewRecord
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Employee Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('employee.emp_id')
                            ->label('Employee ID'),
                        Infolists\Components\TextEntry::make('employee.name')
                            ->label('Employee Name'),
                    ])->columns(2),

                Infolists\Components\Section::make('Salary Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('basic_salary')
                            ->label('Basic Salary')
                            ->money('SAR'),
                        Infolists\Components\TextEntry::make('housing_allowance')
                            ->label('Housing Allowance')
                            ->money('SAR'),
                        Infolists\Components\TextEntry::make('transportation_allowance')
                            ->label('Transportation Allowance')
                            ->money('SAR'),
                        Infolists\Components\TextEntry::make('food_allowance')
                            ->label('Food Allowance')
                            ->money('SAR'),
                        Infolists\Components\TextEntry::make('other_allowance')
                            ->label('Other Allowance')
                            ->money('SAR'),
                        Infolists\Components\TextEntry::make('total_other_allow')
                            ->label('Total Other Allowance')
                            ->money('SAR'),
                        Infolists\Components\TextEntry::make('total_salary')
                            ->label('Total Salary')
                            ->money('SAR')
                            ->color('success')
                            ->weight('bold'),
                    ])->columns(2),

                Infolists\Components\Section::make('Earnings')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_package')
                            ->label('Total Package')
                            ->money('SAR'),
                        Infolists\Components\TextEntry::make('fees')
                            ->label('Monthly Fees')
                            ->money('SAR'),
                        Infolists\Components\TextEntry::make('work_days')
                            ->label('Work Days'),
                        Infolists\Components\TextEntry::make('added_days')
                            ->label('Added Days'),
                        Infolists\Components\TextEntry::make('overtime_hours')
                            ->label('OT HRS.'),
                        Infolists\Components\TextEntry::make('overtime_amount')
                            ->label('Overtime Amount')
                            ->money('SAR'),
                        Infolists\Components\TextEntry::make('added_days_amount')
                            ->label('Added Days Amount')
                            ->money('SAR'),
                        Infolists\Components\TextEntry::make('other_additions')
                            ->label('Other Additions')
                            ->money('SAR'),
                        Infolists\Components\TextEntry::make('total_additions')
                            ->label('Total Additions')
                            ->money('SAR')
                            ->color('success')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('total_earning')
                            ->label('Total Earning')
                            ->money('SAR')
                            ->color('success')
                            ->weight('bold')
                            ->size('lg'),
                    ])->columns(2)
                    ->icon('heroicon-o-arrow-trending-up')
                    ->color('success'),

                Infolists\Components\Section::make('Deductions')
                    ->schema([
                        Infolists\Components\TextEntry::make('absence_days')
                            ->label('Absence Days'),
                        Infolists\Components\TextEntry::make('absence_unpaid_leave_deduction')
                            ->label('AB & UL')
                            ->money('SAR'),
                        Infolists\Components\TextEntry::make('food_subscription_deduction')
                            ->label('Food Subscription Deduction')
                            ->money('SAR'),
                        Infolists\Components\TextEntry::make('other_deduction')
                            ->label('Other Deduction')
                            ->money('SAR'),
                        Infolists\Components\TextEntry::make('total_deductions')
                            ->label('Total Deductions')
                            ->money('SAR')
                            ->color('danger')
                            ->weight('bold')
                            ->size('lg'),
                    ])->columns(2)
                    ->icon('heroicon-o-arrow-trending-down')
                    ->color('danger'),

                Infolists\Components\Section::make('Net Payment')
                    ->schema([
                        Infolists\Components\TextEntry::make('net_payment')
                            ->label('Net Payment')
                            ->money('SAR')
                            ->color('primary')
                            ->weight('bold')
                            ->size('xl'),
                    ])
                    ->icon('heroicon-o-banknotes')
                    ->color('primary'),
            ]);
    }
}

