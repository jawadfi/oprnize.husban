<?php

namespace App\Services;

use App\Enums\TerminationReason;

class EndOfServiceService
{
    private const DAYS_PER_YEAR = 365.25;

    /**
     * Calculate End of Service Bonus (مكافأة نهاية الخدمة).
     *
     * Based on Saudi Labor Law:
     *  - Article 80 termination → 0
     *  - Resignation < 2 years → 0
     *  - Resignation 2-5 years → 1/3 of entitlement
     *  - Resignation > 5 years → 2/3 of entitlement
     *  - Contract end / employer termination → 100% entitlement
     *
     * @param  int    $reason  TerminationReason constant (1 = resignation, 2 = contract end, 3 = article 80)
     * @param  int    $days    Total days of service
     * @param  float  $salary  Monthly salary (SAR)
     * @return float  Final bonus rounded to 2 decimal places
     */
    public function calculate(int $reason, int $days, float $salary): float
    {
        if ($days <= 0 || $salary <= 0) {
            return 0.00;
        }

        $serviceYears = $days / self::DAYS_PER_YEAR;

        return match ($reason) {
            TerminationReason::ARTICLE_80   => 0.00,
            TerminationReason::RESIGNATION  => $this->calculateResignation($serviceYears, $salary),
            TerminationReason::CONTRACT_END => $this->calculateContractEnd($serviceYears, $salary),
            default => 0.00,
        };
    }

    /**
     * Resignation: reduced entitlement based on years of service.
     */
    private function calculateResignation(float $serviceYears, float $salary): float
    {
        // Less than 2 years → no bonus
        if ($serviceYears < 2) {
            return 0.00;
        }

        $yearsP1 = min($serviceYears, 5);
        $yearsP2 = max(0, $serviceYears - 5);

        // First 5 years: half salary per year × 1/3
        $bonusP1 = $yearsP1 * ($salary / 2) * (1 / 3);

        // After 5 years: full salary per year × 2/3
        $bonusP2 = $yearsP2 * $salary * (2 / 3);

        return round($bonusP1 + $bonusP2, 2);
    }

    /**
     * Contract end / employer termination: full entitlement.
     */
    private function calculateContractEnd(float $serviceYears, float $salary): float
    {
        $yearsP1 = min($serviceYears, 5);
        $yearsP2 = max(0, $serviceYears - 5);

        // First 5 years: half salary per year
        $bonusP1 = $yearsP1 * ($salary / 2);

        // After 5 years: full salary per year
        $bonusP2 = $yearsP2 * $salary;

        return round($bonusP1 + $bonusP2, 2);
    }
}
