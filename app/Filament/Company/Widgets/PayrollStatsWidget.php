<?php

namespace App\Filament\Company\Widgets;

use App\Enums\CompanyTypes;
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

        $payrolls = Payroll::where('company_id', $user->id)
            ->where('payroll_month', $currentMonth)
            ->where('basic_salary', '>', 0)
            ->get();

        // Total Overtime = sum of overtime_amount (NOT other_additions)
        $totalOvertime = $payrolls->sum('overtime_amount');

        // Net Payment = sum of (total_earning - total_deductions) per payroll
        $netPayment = $payrolls->sum(fn($p) => $p->net_payment);

        // Total without OT = Net Payment - Total Overtime
        $totalWithoutOT = $netPayment - $totalOvertime;

        return [
            Stat::make('Total Overtime', number_format($totalOvertime, 2) . ' ريال')
                ->description('إجمالي مبلغ العمل الإضافي')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Net Payment', number_format($netPayment, 2) . ' ريال')
                ->description('الإجمالي (الراتب + الإضافات + الرسوم - الخصومات)')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Total without OT', number_format($totalWithoutOT, 2) . ' ريال')
                ->description('الإجمالي بدون العمل الإضافي')
                ->descriptionIcon('heroicon-o-calculator')
                ->color('primary'),
        ];
    }
}
