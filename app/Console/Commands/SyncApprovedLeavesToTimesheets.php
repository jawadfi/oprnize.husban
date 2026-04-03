<?php

namespace App\Console\Commands;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use Illuminate\Console\Command;

class SyncApprovedLeavesToTimesheets extends Command
{
    protected $signature = 'leaves:sync-timesheets
                            {--employee= : Only sync for this employee ID}
                            {--dry-run  : Show what would be synced without writing}';

    protected $description = 'Retroactively write approved leave days as L/X into the client timesheet and sync payroll';

    public function handle(): int
    {
        $query = LeaveRequest::with('employee')
            ->where('status', LeaveRequestStatus::APPROVED);

        if ($employeeId = $this->option('employee')) {
            $query->where('employee_id', $employeeId);
        }

        $leaves = $query->get();

        if ($leaves->isEmpty()) {
            $this->info('No approved leave requests found.');
            return self::SUCCESS;
        }

        $dryRun = $this->option('dry-run');
        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Syncing {$leaves->count()} approved leave request(s)...");

        $bar = $this->output->createProgressBar($leaves->count());
        $bar->start();

        $synced = 0;
        $skipped = 0;

        foreach ($leaves as $leave) {
            $employee = $leave->employee;

            if (! $employee || ! $employee->company_assigned_id) {
                $this->newLine();
                $this->warn("  Skipped leave #{$leave->id} — employee has no assigned client company.");
                $skipped++;
                $bar->advance();
                continue;
            }

            if (! $leave->start_date || ! $leave->end_date) {
                $this->newLine();
                $this->warn("  Skipped leave #{$leave->id} — missing start/end date.");
                $skipped++;
                $bar->advance();
                continue;
            }

            if (! $dryRun) {
                $leave->applyApprovedLeaveToClientTimesheet();
            } else {
                $this->newLine();
                $this->line("  Would sync: leave #{$leave->id} [{$leave->leave_type->value}] employee #{$employee->id} {$leave->start_date} → {$leave->end_date} ({$leave->days_count} days)");
            }

            $synced++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Synced: {$synced}  |  Skipped: {$skipped}");

        return self::SUCCESS;
    }
}
