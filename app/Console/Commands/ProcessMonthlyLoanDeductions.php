<?php

namespace App\Console\Commands;

use App\Enums\LoanStatus;
use App\Models\Loan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessMonthlyLoanDeductions extends Command
{
    protected $signature = 'loans:process-monthly
                            {--month= : Month in YYYY-MM format (default: current month)}';

    protected $description = 'Generate monthly installment deductions for all active loans';

    public function handle(): int
    {
        $month = $this->option('month') ?? now()->format('Y-m');

        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $this->error("Invalid month format: {$month}. Expected YYYY-MM (e.g. 2026-05).");
            return self::FAILURE;
        }

        $this->info("Processing loan deductions for: {$month}");

        $processed = 0;
        $skipped   = 0;
        $errors    = 0;

        Loan::where('status', LoanStatus::ACTIVE)
            ->with('employee')
            ->chunk(100, function ($loans) use ($month, &$processed, &$skipped, &$errors) {
                foreach ($loans as $loan) {
                    try {
                        DB::transaction(function () use ($loan, $month, &$processed, &$skipped) {
                            $result = $loan->processMonthlyDeduction($month);
                            $result ? $processed++ : $skipped++;
                        });
                    } catch (\Throwable $e) {
                        $errors++;
                        Log::error('loans:process-monthly failed', [
                            'loan_id'  => $loan->id,
                            'month'    => $month,
                            'error'    => $e->getMessage(),
                        ]);
                        $this->warn("  Loan #{$loan->id} (employee: {$loan->employee?->name}) — {$e->getMessage()}");
                    }
                }
            });

        $this->info("Done — Processed: {$processed}, Skipped: {$skipped}, Errors: {$errors}");

        return self::SUCCESS;
    }
}
