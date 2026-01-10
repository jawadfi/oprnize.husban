<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmployeeStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Employees', Employee::count())
                ->description('Registered employees')
                ->descriptionIcon('heroicon-o-users')
                ->color('warning'),
        ];
    }
}
