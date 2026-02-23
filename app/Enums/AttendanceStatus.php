<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'P';
    case ABSENT = 'A';
    case LEAVE = 'L';       // إجازة - Leave (deduct salary + fees)
    case OFF_DAY = 'O';     // يوم راحة / إجازة أسبوعية - Off/Holiday (deduct salary + fees)
    case EXCLUDED = 'X';    // مستبعد - Excluded (deduct salary + fees, same as leave)

    public function label(): string
    {
        return match($this) {
            self::PRESENT => 'حاضر / Present',
            self::ABSENT => 'غائب / Absent',
            self::LEAVE => 'إجازة / Leave',
            self::OFF_DAY => 'يوم راحة / Off Day',
            self::EXCLUDED => 'مستبعد / Excluded',
        };
    }

    public function shortLabel(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match($this) {
            self::PRESENT => '#28a745',      // green
            self::ABSENT => '#dc3545',       // red
            self::LEAVE => '#fd7e14',        // orange
            self::OFF_DAY => '#6f42c1',      // purple
            self::EXCLUDED => '#6c757d',     // gray
        };
    }

    /**
     * Whether this status deducts salary only (no fees)
     * A (Absent) = salary only
     */
    public function deductsSalaryOnly(): bool
    {
        return match($this) {
            self::ABSENT => true,
            default => false,
        };
    }

    /**
     * Whether this status deducts salary + monthly fees
     * L (Leave), O (Off Day), X (Excluded) = salary + fees
     */
    public function deductsSalaryAndFees(): bool
    {
        return match($this) {
            self::LEAVE, self::OFF_DAY, self::EXCLUDED => true,
            default => false,
        };
    }

    /**
     * Whether this status counts as a non-working deductible day
     */
    public function isDeductible(): bool
    {
        return $this !== self::PRESENT;
    }

    public static function getTranslatedEnum(): array
    {
        return collect(self::cases())->mapWithKeys(fn($case) => [
            $case->value => $case->label(),
        ])->toArray();
    }

    public static function getDropdownOptions(): array
    {
        return collect(self::cases())->mapWithKeys(fn($case) => [
            $case->value => $case->value . ' - ' . $case->label(),
        ])->toArray();
    }
}
