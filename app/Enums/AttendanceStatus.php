<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'P';
    case ABSENT = 'A';

    public function label(): string
    {
        return match($this) {
            self::PRESENT => 'حاضر / Present',
            self::ABSENT => 'غائب / Absent',
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
        };
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
