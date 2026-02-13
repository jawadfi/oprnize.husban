<?php

namespace App\Filament\Company\Widgets;

use App\Enums\CompanyTypes;
use App\Models\Employee;
use App\Models\Payroll;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PayrollStatsWidget extends BaseWidget
{
    /**
     * This widget is NOT displayed on the main Dashboard.
     * It exists for the Payroll pages only.
     * Returning empty stats prevents errors if Livewire tries to lazy-load it.
     */
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $user = Filament::auth()->user();
        if (!$user) {
            return [];
        }

        $currentMonth = now()->format('Y-m');

        // Use PHP-level computation since monthly_cost is a computed accessor (not a DB column)
        $payrolls = Payroll::where('company_id', $user->id)
            ->where('payroll_month', $currentMonth)
            ->get();

        $monthlyPayroll = $payrolls->sum(fn ($p) => $p->monthly_cost);
        $employeesWithPayroll = $payrolls->where('basic_salary', '>', 0)->count();

        if ($user->type === CompanyTypes::PROVIDER) {
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
