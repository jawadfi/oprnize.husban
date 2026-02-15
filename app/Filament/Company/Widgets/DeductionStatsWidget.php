<?php

namespace App\Filament\Company\Widgets;

use App\Enums\CompanyTypes;
use App\Enums\DeductionStatus;
use App\Filament\Company\Resources\DeductionResource;
use App\Models\Deduction;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DeductionStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Filament::auth()->user();
        $currentMonth = now()->format('Y-m');
        
        // Get deductions for current month
        $query = Deduction::where('company_id', $user->id)
            ->where('payroll_month', $currentMonth);
        
        $totalAmount = (clone $query)->where('status', DeductionStatus::APPROVED)->sum('amount');
        $pendingCount = (clone $query)->where('status', DeductionStatus::PENDING)->count();
        $approvedCount = (clone $query)->where('status', DeductionStatus::APPROVED)->count();
        
        $url = DeductionResource::getUrl('index');
        return [
            Stat::make('إجمالي الخصومات', number_format($totalAmount, 2) . ' ريال')
                ->description('الخصومات المعتمدة لشهر ' . now()->format('F'))
                ->descriptionIcon('heroicon-o-minus-circle')
                ->color('danger')
                ->url($url),
                
            Stat::make('خصومات معلقة', $pendingCount)
                ->description('بانتظار الموافقة')
                ->descriptionIcon('heroicon-o-clock')
                ->color($pendingCount > 0 ? 'warning' : 'success')
                ->url($url),
                
            Stat::make('خصومات معتمدة', $approvedCount)
                ->description('تم اعتمادها')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('info')
                ->url($url),
        ];
    }
}
