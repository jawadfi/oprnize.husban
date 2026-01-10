<?php

namespace App\Filament\Admin\Widgets;

use App\Models\City;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CityStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Cities', City::count())
                ->description('Available cities')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('primary'),
        ];
    }
}
