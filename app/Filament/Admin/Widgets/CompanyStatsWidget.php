<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Company;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CompanyStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Companies', Company::count())
                ->description('Registered companies')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('info'),
        ];
    }
}
