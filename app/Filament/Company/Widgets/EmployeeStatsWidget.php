<?php

namespace App\Filament\Company\Widgets;

use App\Enums\CompanyTypes;
use App\Enums\EmployeeAssignedStatus;
use App\Models\Employee;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmployeeStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Filament::auth()->user();
        
        if ($user->type === CompanyTypes::PROVIDER) {
            // Provider: count their own employees
            $totalEmployees = Employee::where('company_id', $user->id)->count();
            $assignedEmployees = Employee::where('company_id', $user->id)
                ->whereHas('assigned', fn($q) => $q->where('status', EmployeeAssignedStatus::APPROVED))
                ->count();
            
            return [
                Stat::make('إجمالي الموظفين', $totalEmployees)
                    ->description('عدد الموظفين المسجلين')
                    ->descriptionIcon('heroicon-o-users')
                    ->color('primary'),
                    
                Stat::make('الموظفين المعينين', $assignedEmployees)
                    ->description('الموظفين المخصصين للعملاء')
                    ->descriptionIcon('heroicon-o-user-group')
                    ->color('success'),
                    
                Stat::make('الموظفين المتاحين', $totalEmployees - $assignedEmployees)
                    ->description('موظفين غير معينين')
                    ->descriptionIcon('heroicon-o-user-plus')
                    ->color('warning'),
            ];
        } else {
            // Client: count assigned employees only
            $assignedEmployees = Employee::whereHas('assigned', fn($q) => 
                $q->where('employee_assigned.company_id', $user->id)
                  ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
            )->count();
            
            return [
                Stat::make('الموظفين المعينين', $assignedEmployees)
                    ->description('عدد الموظفين المخصصين لك')
                    ->descriptionIcon('heroicon-o-users')
                    ->color('primary'),
            ];
        }
    }
}
