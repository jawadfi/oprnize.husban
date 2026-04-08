<?php

namespace App\Filament\Company\Widgets;

use App\Enums\CompanyTypes;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PayrollStatsWidget extends BaseWidget
{
    /**
     * This widget is displayed on the Payroll ListPayrolls page only.
     * Uses PHP computation since monthly_cost is a computed accessor (not a DB column).
     */
    protected function getStats(): array
    {
        $user = Filament::auth()->user();
        $companyId = $user instanceof Company ? $user->id : ($user instanceof User ? $user->company_id : null);
        $companyType = $user instanceof Company ? $user->type : ($user instanceof User ? $user->company?->type : null);

        if (!$companyId) {
            return [];
        }

        $currentMonth = now()->format('Y-m');

        // Use PHP-level computation since monthly_cost is a computed accessor (not a DB column)
        $payrolls = Payroll::where('company_id', $companyId)
            ->where('payroll_month', $currentMonth)
            ->get();

        $monthlyPayroll = $payrolls->sum(fn ($p) => $p->monthly_cost);
        $employeesWithPayroll = $payrolls->where('basic_salary', '>', 0)->count();

        if ($companyType === CompanyTypes::PROVIDER) {
            $totalEmployees = Employee::where('company_id', $companyId)->count();

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
        }

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
