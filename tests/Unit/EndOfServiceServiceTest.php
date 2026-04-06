<?php

use App\Enums\TerminationReason;
use App\Services\EndOfServiceService;

beforeEach(function () {
    $this->service = new EndOfServiceService();
});

// ── Article 80 ──────────────────────────────────────────────

it('returns 0 for article 80 termination', function () {
    expect($this->service->calculate(TerminationReason::ARTICLE_80, 3650, 10000))
        ->toBe(0.00);
});

// ── Resignation ─────────────────────────────────────────────

it('returns 0 for resignation under 2 years', function () {
    // 1 year = 365 days
    expect($this->service->calculate(TerminationReason::RESIGNATION, 365, 10000))
        ->toBe(0.00);
});

it('returns 0 for resignation at 729 days', function () {
    expect($this->service->calculate(TerminationReason::RESIGNATION, 729, 10000))
        ->toBe(0.00);
});

it('calculates resignation at exactly 2 years', function () {
    $days = 731; // 731 / 365.25 = 2.0013... years — just over 2
    $salary = 10000;
    $serviceYears = $days / 365.25;
    $expected = round(min($serviceYears, 5) * ($salary / 2) * (1 / 3), 2);

    expect($this->service->calculate(TerminationReason::RESIGNATION, $days, $salary))
        ->toBe($expected);
});

it('calculates resignation at 5 years', function () {
    $days = (int) (5 * 365.25); // 1826
    $salary = 10000;
    $serviceYears = $days / 365.25;
    $yearsP1 = min($serviceYears, 5);
    $expected = round($yearsP1 * ($salary / 2) * (1 / 3), 2);

    expect($this->service->calculate(TerminationReason::RESIGNATION, $days, $salary))
        ->toBe($expected);
});

it('calculates resignation over 5 years with two-part split', function () {
    $days = (int) (8 * 365.25); // ~2922 days = 8 years
    $salary = 10000;
    $serviceYears = $days / 365.25;

    $yearsP1 = 5;
    $yearsP2 = $serviceYears - 5;
    $bonusP1 = $yearsP1 * ($salary / 2) * (1 / 3);
    $bonusP2 = $yearsP2 * $salary * (2 / 3);
    $expected = round($bonusP1 + $bonusP2, 2);

    expect($this->service->calculate(TerminationReason::RESIGNATION, $days, $salary))
        ->toBe($expected);
});

// ── Contract End ────────────────────────────────────────────

it('calculates contract end under 5 years (full entitlement)', function () {
    $days = (int) (3 * 365.25); // ~3 years
    $salary = 10000;
    $serviceYears = $days / 365.25;
    $expected = round(min($serviceYears, 5) * ($salary / 2), 2);

    expect($this->service->calculate(TerminationReason::CONTRACT_END, $days, $salary))
        ->toBe($expected);
});

it('calculates contract end over 5 years (full entitlement)', function () {
    $days = (int) (10 * 365.25); // ~10 years
    $salary = 10000;
    $serviceYears = $days / 365.25;

    $bonusP1 = 5 * ($salary / 2);
    $bonusP2 = ($serviceYears - 5) * $salary;
    $expected = round($bonusP1 + $bonusP2, 2);

    expect($this->service->calculate(TerminationReason::CONTRACT_END, $days, $salary))
        ->toBe($expected);
});

// ── Edge Cases ──────────────────────────────────────────────

it('returns 0 for zero days', function () {
    expect($this->service->calculate(TerminationReason::CONTRACT_END, 0, 10000))
        ->toBe(0.00);
});

it('returns 0 for negative days', function () {
    expect($this->service->calculate(TerminationReason::CONTRACT_END, -100, 10000))
        ->toBe(0.00);
});

it('returns 0 for zero salary', function () {
    expect($this->service->calculate(TerminationReason::CONTRACT_END, 1000, 0))
        ->toBe(0.00);
});

it('returns 0 for negative salary', function () {
    expect($this->service->calculate(TerminationReason::CONTRACT_END, 1000, -5000))
        ->toBe(0.00);
});

it('returns 0 for unknown reason', function () {
    expect($this->service->calculate(99, 1000, 10000))
        ->toBe(0.00);
});
