<?php

namespace App\Console\Commands;

use App\Models\Payroll;
use Illuminate\Console\Command;

class RecalculatePayrollDeductions extends Command
{
    protected $signature = 'payroll:recalculate-deductions {--company_id=} {--payroll_month=} {--all}';
    protected $description = 'Recalculate all payroll absence deductions to use actual days in month instead of 30';

    public function handle()
    {
        $all = $this->option('all');
        $companyId = $this->option('company_id');
        $payrollMonth = $this->option('payroll_month');

        $query = Payroll::query();

        if (!$all) {
            if ($companyId) {
                $query->where('company_id', $companyId);
                $this->info("Filtering by company_id: {$companyId}");
            }
            if ($payrollMonth) {
                $query->where('payroll_month', $payrollMonth);
                $this->info("Filtering by payroll_month: {$payrollMonth}");
            }
        } else {
            $this->info("Recalculating ALL payroll records");
        }

        $total = $query->count();
        $this->info("Found {$total} payroll records to recalculate");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk(100, function ($payrolls) use ($bar) {
            foreach ($payrolls as $payroll) {
                // Sync from entries will recalculate absence_unpaid_leave_deduction using actual daysInMonth
                Payroll::syncFromEntries(
                    $payroll->employee_id,
                    $payroll->company_id,
                    $payroll->payroll_month
                );
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('✓ Payroll deductions recalculation completed successfully!');
    }
}
