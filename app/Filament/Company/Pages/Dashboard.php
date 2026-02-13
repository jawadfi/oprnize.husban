<?php

namespace App\Filament\Company\Pages;


use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        
        // Company model has all access
        if ($user instanceof \App\Models\Company) {
            return true;
        }
        
        // User model - hide dashboard if they only have LeaveRequests permission
        // This ensures HR users with only LeaveRequests permission don't see Dashboard
        if ($user instanceof \App\Models\User) {
            // Check if user has permissions beyond just LeaveRequests
            $hasOtherPermissions = $user->can('page_PendingHiring') || 
                                   $user->can('view_any_UserResource') || 
                                   $user->can('view_any_Role') ||
                                   $user->can('view_any_EmployeeResource') ||
                                   $user->can('view_any_PayrollResource') ||
                                   $user->can('page_Dashboard');
            
            // Only show dashboard if they have other permissions (not just LeaveRequests)
            return $hasOtherPermissions;
        }
        
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
    
    public function getWidgets(): array
    {
        return [
            EmployeeStatsWidget::class,
            DeductionStatsWidget::class,
            LeaveRequestStatsWidget::class,
        ];
    }
}

