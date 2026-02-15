<?php

namespace App\Filament\Company\Widgets;

use App\Enums\CompanyTypes;
use App\Enums\LeaveRequestStatus;
use App\Filament\Company\Pages\LeaveRequests;
use App\Models\LeaveRequest;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LeaveRequestStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Filament::auth()->user();
        
        $stats = [];
        
        // Pending leave requests
        $pending = LeaveRequest::where('company_id', $user->id)
            ->where('status', LeaveRequestStatus::PENDING)
            ->count();
        
        $leaveUrl = LeaveRequests::getUrl();
        $stats[] = Stat::make('طلبات الإجازات المعلقة', $pending)
            ->description('بانتظار الموافقة')
            ->descriptionIcon('heroicon-o-clock')
            ->color($pending > 0 ? 'warning' : 'success')
            ->url($leaveUrl);
        
        if ($user->type === CompanyTypes::PROVIDER) {
            // Show monthly leave requests statistics
            $currentMonth = now()->startOfMonth();
            $approved = LeaveRequest::where('company_id', $user->id)
                ->where('status', LeaveRequestStatus::APPROVED)
                ->whereMonth('created_at', $currentMonth->month)
                ->whereYear('created_at', $currentMonth->year)
                ->count();
            
            $stats[] = Stat::make('الإجازات المعتمدة هذا الشهر', $approved)
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->url($leaveUrl);
        }
        
        return $stats;
    }
}
