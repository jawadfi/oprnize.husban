# Payroll Deduction Fix - Verification Guide

## Problem

Absence deductions were calculating using hardcoded `/30` regardless of the actual days in the month.

**Example:** 1 absence day with 1,900 SAR salary showed 63.33 (1900/30) instead of correct amount based on actual days in month.

## Solution

Fixed all deduction calculations to use actual `daysInMonth`:

- **31-day months** (Jan, Mar, May, Jul, Aug, Oct, Dec): divide by 31
- **30-day months** (Apr, Jun, Sep, Nov): divide by 30
- **February**: divide by 28 or 29 (leap year)

## Files Changed

1. **app/Models/Payroll.php** - `syncFromEntries()` method now calculates `$daysInMonth` from the payroll month and uses it in absence deduction formulas
2. **app/Filament/Company/Resources/PayrollResource/Pages/ListPayrolls.php** - Initial `work_days` now set to actual days in month
3. **app/Filament/Company/Resources/DeductionResource/Pages/ListDeductions.php** - Daily rate calculation uses actual days
4. **app/Filament/Company/Imports/DeductionImporter.php** - CSV importer uses actual days
5. **app/Console/Commands/RecalculatePayrollDeductions.php** - New command to fix existing payroll records

## How to Apply the Fix

### Option 1: Recalculate Single Payroll Record (UI)

1. Navigate to the payroll record
2. Click the **Calculate** button in the row actions
3. This triggers `syncFromEntries()` which will recalculate using the new formula

### Option 2: Bulk Recalculate ALL Records (CLI)

```bash
php artisan payroll:recalculate-deductions --all
```

### Option 3: Recalculate Specific Month

```bash
php artisan payroll:recalculate-deductions --payroll_month=2026-03
```

### Option 4: Recalculate for Specific Company

```bash
php artisan payroll:recalculate-deductions --company_id=1
```

## Example Calculation

**Before Fix (March/31 days):**

- Salary: 1,900 SAR
- Absence: 1 day
- Deduction: 1,900 ÷ 30 = **63.33 SAR** ❌

**After Fix (March/31 days):**

- Salary: 1,900 SAR
- Absence: 1 day
- Deduction: 1,900 ÷ 31 = **61.29 SAR** ✓

**February (28 days, non-leap year):**

- Salary: 1,900 SAR
- Absence: 1 day
- Deduction: 1,900 ÷ 28 = **67.86 SAR** ✓

## Code Implementation

The key fix in `app/Models/Payroll.php` (line 401):

```php
$parts = explode('-', $payrollMonth);
$year = (int) $parts[0];
$month = (int) $parts[1];
$daysInMonth = (int) Carbon::create($year, $month)->daysInMonth;  // ← Gets actual days!
```

Then used in deduction calculation (lines 456-462):

```php
// A (Absent): deduct from salary ONLY
if ($absentDays > 0 && $totalSalary > 0) {
    $salaryOnlyDeduction = $absentDays * ($totalSalary / $daysInMonth);  // ← Uses actual days!
}

// L + O + X: deduct from salary + fees
$feeDeductDays = $leaveDays + $offDays + $excludedDays;
if ($feeDeductDays > 0 && ($totalSalary + $fees) > 0) {
    $salaryAndFeesDeduction = $feeDeductDays * (($totalSalary + $fees) / $daysInMonth);  // ← Uses actual days!
}
```

## Git Commits

- `269e240` - Use actual days in month for all payroll/deduction calculations
- `ba26da9` - Add recalculation command and update docstring
