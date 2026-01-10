<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Admin;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Admins', Admin::count())
                ->description('Administrative users')
                ->descriptionIcon('heroicon-o-shield-check')
                ->color('success'),
        ];
    }
}
