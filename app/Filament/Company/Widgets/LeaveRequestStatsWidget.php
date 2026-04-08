<?php

namespace App\Filament\Company\Widgets;

use App\Enums\CompanyTypes;
use App\Enums\LeaveRequestStatus;
use App\Filament\Company\Pages\LeaveRequests;
use App\Models\Company;
use App\Models\LeaveRequest;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LeaveRequestStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Filament::auth()->user();
        $companyId = $user instanceof Company ? $user->id : ($user instanceof User ? $user->company_id : null);
        $companyType = $user instanceof Company ? $user->type : ($user instanceof User ? $user->company?->type : null);

        if (!$companyId) {
            return [];
        }

        $isProvider = $companyType === CompanyTypes::PROVIDER;
        $companyColumn = $isProvider ? 'provider_company_id' : 'client_company_id';
        
        $stats = [];
        
        // Pending leave requests (all pending statuses)
        $pending = LeaveRequest::where(function ($query) use ($companyId, $companyColumn) {
                $query->where('current_approver_company_id', $companyId)
                    ->orWhere(function ($pendingQuery) use ($companyId, $companyColumn) {
                        $pendingQuery->where('status', LeaveRequestStatus::PENDING)
                            ->where($companyColumn, $companyId);
                    });
            })
            ->whereNotIn('status', [
                LeaveRequestStatus::APPROVED,
                LeaveRequestStatus::REJECTED,
            ])
            ->count();
        
        $leaveUrl = LeaveRequests::getUrl();
        $stats[] = Stat::make('طلبات الإجازات المعلقة', $pending)
            ->description('بانتظار الموافقة')
            ->descriptionIcon('heroicon-o-clock')
            ->color($pending > 0 ? 'warning' : 'success')
            ->url($leaveUrl);
        
        if ($isProvider) {
            // Show monthly leave requests statistics
            $currentMonth = now()->startOfMonth();
            $approved = LeaveRequest::where('provider_company_id', $companyId)
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
