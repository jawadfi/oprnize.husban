<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairEmployeeData extends Command
{
    protected $signature = 'employees:repair {--fix : Actually apply fixes (dry-run by default)} {--company= : Target a specific company ID}';

    protected $description = 'Audit and repair employee data: find orphaned employees, duplicate records, and company_id mismatches';

    public function handle(): int
    {
        $dryRun = !$this->option('fix');
        $targetCompanyId = $this->option('company');

        if ($dryRun) {
            $this->warn('DRY RUN MODE — no changes will be made. Use --fix to apply repairs.');
        } else {
            if (!$this->confirm('This will modify employee data. Continue?')) {
                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info('=== Employee Data Audit Report ===');
        $this->newLine();

        // 1. Summary per company
        $this->companySummary($targetCompanyId);

        // 2. Orphaned employees (null or invalid company_id)
        $this->repairOrphanedEmployees($dryRun);

        // 3. Employees with company_id pointing to non-existent company
        $this->repairInvalidCompanyIds($dryRun);

        // 4. Duplicate employees within same company (same identity_number)
        $this->reportDuplicates($targetCompanyId);

        // 5. Employee assignments pointing to non-existent employees or companies
        $this->repairOrphanedAssignments($dryRun);

        // 6. Employees that exist in multiple companies (possible import leak)
        $this->detectCrossCompanyDuplicates();

        $this->newLine();
        $this->info('=== Audit Complete ===');

        return self::SUCCESS;
    }

    private function companySummary(?string $targetCompanyId): void
    {
        $this->info('--- Company Employee Summary ---');

        $query = Company::withCount('employees')->orderBy('name');
        if ($targetCompanyId) {
            $query->where('id', $targetCompanyId);
        }

        $companies = $query->get();
        $rows = $companies->map(fn($c) => [
            $c->id,
            $c->name,
            $c->type?->value ?? $c->type,
            $c->employees_count,
        ])->toArray();

        $this->table(['ID', 'Company', 'Type', 'Employees'], $rows);
    }

    private function repairOrphanedEmployees(bool $dryRun): void
    {
        $this->info('--- Orphaned Employees (NULL company_id) ---');

        $orphans = Employee::withTrashed()->whereNull('company_id')->get();

        if ($orphans->isEmpty()) {
            $this->line('None found.');
            return;
        }

        $this->warn("Found {$orphans->count()} employees with NULL company_id:");
        /** @var Employee $emp */
        foreach ($orphans as $emp) {
            // Try to infer company from employee_assigned pivot
            $assignment = DB::table('employee_assigned')
                ->where('employee_id', $emp->id)
                ->first();

            $inferredCompanyId = null;
            if ($assignment) {
                // The pivot company_id is the CLIENT. Check if there's a pattern from other employees.
                $inferredCompanyId = $this->inferProviderCompanyId($emp);
            }

            $this->line("  ID:{$emp->id} Name:{$emp->name} iqama:{$emp->iqama_no} => Inferred company: " . ($inferredCompanyId ?? 'UNKNOWN'));

            if (!$dryRun && $inferredCompanyId) {
                $emp->company_id = $inferredCompanyId;
                $emp->save();
                $this->info("    FIXED: Set company_id = {$inferredCompanyId}");
            }
        }
    }

    private function repairInvalidCompanyIds(bool $dryRun): void
    {
        $this->info('--- Employees with Invalid company_id ---');

        $validCompanyIds = Company::pluck('id')->toArray();
        $invalid = Employee::withTrashed()
            ->whereNotNull('company_id')
            ->whereNotIn('company_id', $validCompanyIds)
            ->get();

        if ($invalid->isEmpty()) {
            $this->line('None found.');
            return;
        }

        $this->warn("Found {$invalid->count()} employees pointing to non-existent companies:");
        /** @var Employee $emp */
        foreach ($invalid as $emp) {
            $this->line("  ID:{$emp->id} Name:{$emp->name} company_id:{$emp->company_id} (deleted/missing)");

            $inferredCompanyId = $this->inferProviderCompanyId($emp);
            if (!$dryRun && $inferredCompanyId) {
                $emp->company_id = $inferredCompanyId;
                $emp->save();
                $this->info("    FIXED: Set company_id = {$inferredCompanyId}");
            }
        }
    }

    private function reportDuplicates(?string $targetCompanyId): void
    {
        $this->info('--- Duplicate Employees (same identity_number in same company) ---');

        $query = DB::table('employees')
            ->select('company_id', 'identity_number', DB::raw('COUNT(*) as cnt'), DB::raw('GROUP_CONCAT(id) as ids'))
            ->whereNotNull('identity_number')
            ->where('identity_number', '!=', '')
            ->groupBy('company_id', 'identity_number')
            ->having('cnt', '>', 1);

        if ($targetCompanyId) {
            $query->where('company_id', $targetCompanyId);
        }

        $dupes = $query->get();

        if ($dupes->isEmpty()) {
            $this->line('None found.');
            return;
        }

        $this->warn("Found {$dupes->count()} duplicate groups:");
        foreach ($dupes as $dupe) {
            $companyName = Company::find($dupe->company_id)?->name ?? 'Unknown';
            $this->line("  Company:{$companyName}(#{$dupe->company_id}) identity:{$dupe->identity_number} count:{$dupe->cnt} IDs:[{$dupe->ids}]");
        }
    }

    private function repairOrphanedAssignments(bool $dryRun): void
    {
        $this->info('--- Orphaned Assignments (employee or company deleted) ---');

        $orphanedByEmployee = DB::table('employee_assigned')
            ->leftJoin('employees', 'employee_assigned.employee_id', '=', 'employees.id')
            ->whereNull('employees.id')
            ->count();

        $orphanedByCompany = DB::table('employee_assigned')
            ->leftJoin('companies', 'employee_assigned.company_id', '=', 'companies.id')
            ->whereNull('companies.id')
            ->count();

        $total = $orphanedByEmployee + $orphanedByCompany;

        if ($total === 0) {
            $this->line('None found.');
            return;
        }

        $this->warn("Found {$orphanedByEmployee} assignments to deleted employees, {$orphanedByCompany} to deleted companies.");

        if (!$dryRun) {
            $deleted = DB::table('employee_assigned')
                ->leftJoin('employees', 'employee_assigned.employee_id', '=', 'employees.id')
                ->whereNull('employees.id')
                ->delete();

            $deleted += DB::table('employee_assigned')
                ->leftJoin('companies', 'employee_assigned.company_id', '=', 'companies.id')
                ->whereNull('companies.id')
                ->delete();

            $this->info("  FIXED: Removed {$deleted} orphaned assignment records.");
        }
    }

    private function detectCrossCompanyDuplicates(): void
    {
        $this->info('--- Cross-Company Duplicates (same identity in multiple companies) ---');

        $crossDupes = DB::table('employees')
            ->select('identity_number', DB::raw('COUNT(DISTINCT company_id) as company_count'), DB::raw('GROUP_CONCAT(DISTINCT company_id) as company_ids'))
            ->whereNotNull('identity_number')
            ->where('identity_number', '!=', '')
            ->whereNull('deleted_at')
            ->groupBy('identity_number')
            ->having('company_count', '>', 1)
            ->get();

        if ($crossDupes->isEmpty()) {
            $this->line('None found.');
            return;
        }

        $this->warn("Found {$crossDupes->count()} employees existing in multiple companies (possible import leak):");
        foreach ($crossDupes as $dupe) {
            $companyNames = Company::whereIn('id', explode(',', $dupe->company_ids))
                ->pluck('name', 'id')
                ->map(fn($name, $id) => "{$name}(#{$id})")
                ->implode(', ');

            $this->line("  identity:{$dupe->identity_number} in {$dupe->company_count} companies: {$companyNames}");
        }
        $this->line('  NOTE: These may need manual review. Use --company=ID to focus on a specific company.');
    }

    private function inferProviderCompanyId(Employee $emp): ?int
    {
        // Try to find the provider company from employees imported in the same batch (similar created_at)
        if ($emp->iqama_no || $emp->identity_number) {
            $identifier = $emp->iqama_no ?: $emp->identity_number;
            $similar = Employee::withTrashed()
                ->where(function ($q) use ($identifier) {
                    $q->where('iqama_no', $identifier)
                      ->orWhere('identity_number', $identifier);
                })
                ->whereNotNull('company_id')
                ->where('id', '!=', $emp->id)
                ->first();

            if ($similar) {
                return $similar->company_id;
            }
        }

        return null;
    }
}
