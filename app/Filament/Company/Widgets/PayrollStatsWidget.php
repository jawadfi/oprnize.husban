<?php

namespace App\Filament\Company\Widgets;

use App\Enums\CompanyTypes;
use App\Enums\EmployeeAssignedStatus;
use App\Models\Employee;
use App\Models\Payroll;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PayrollStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Filament::auth()->user();
        $currentMonth = now()->format('Y-m');
        
        if ($user->type === CompanyTypes::PROVIDER) {
            // Provider: payroll for their employees
            $monthlyPayroll = Payroll::where('company_id', $user->id)
                ->where('payroll_month', $currentMonth)
                ->sum('monthly_cost');
            
            $employeesWithPayroll = Payroll::where('company_id', $user->id)
                ->where('payroll_month', $currentMonth)
                ->where('basic_salary', '>', 0)
                ->count();
            
            $totalEmployees = Employee::where('company_id', $user->id)->count();
            
            return [
                Stat::make('إجمالي رواتب الشهر', number_format($monthlyPayroll, 2) . ' ريال')
                    ->description('تكلفة رواتب ' . now()->format('F Y'))
                    ->descriptionIcon('heroicon-o-currency-dollar')
                    ->color('success'),
                    
                Stat::make('عدد كشوف الرواتب', $employeesWithPayroll . ' / ' . $totalEmployees)
                    ->description('الموظفين الذين لديهم كشف راتب')
                    ->descriptionIcon('heroicon-o-document-text')
                    ->color($employeesWithPayroll === $totalEmployees ? 'success' : 'warning'),
            ];
        } else {
            // Client: payroll for assigned employees
            $monthlyPayroll = Payroll::where('company_id', $user->id)
                ->where('payroll_month', $currentMonth)
                ->sum('monthly_cost');
            
            $employeesWithPayroll = Payroll::where('company_id', $user->id)
                ->where('payroll_month', $currentMonth)
                ->where('basic_salary', '>', 0)
                ->count();
            
            return [
                Stat::make('إجمالي رواتب الشهر', number_format($monthlyPayroll, 2) . ' ريال')
                    ->description('تكلفة رواتب ' . now()->format('F Y'))
                    ->descriptionIcon('heroicon-o-currency-dollar')
                    ->color('success'),
                    
                Stat::make('عدد كشوف الرواتب', $employeesWithPayroll)
                    ->description('الموظفين الذين لديهم كشف راتب')
                    ->descriptionIcon('heroicon-o-document-text')
                    ->color('info'),
            ];
        }
    }
}
