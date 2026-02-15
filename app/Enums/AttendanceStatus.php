<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'P';
    case ABSENT = 'A';
    case DAY_OFF = 'DO';
    case LEAVE = 'L';
    case ANNUAL_LEAVE = 'AL';
    case UNPAID_LEAVE = 'UL';
    case SICK_LEAVE = 'SL';
    case FAILED_TO_REPORT = 'FR';

    public function label(): string
    {
        return match($this) {
            self::PRESENT => 'حاضر / Present',
            self::ABSENT => 'غائب / Absent',
            self::DAY_OFF => 'يوم إجازة / Day Off',
            self::LEAVE => 'إجازة / Leave',
            self::ANNUAL_LEAVE => 'إجازة سنوية / Annual Leave',
            self::UNPAID_LEAVE => 'إجازة بدون راتب / Unpaid Leave',
            self::SICK_LEAVE => 'إجازة مرضية / Sick Leave',
            self::FAILED_TO_REPORT => 'لم يباشر / Failed to Report',
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
            self::DAY_OFF => '#007bff',      // blue
            self::LEAVE => '#6c757d',        // gray
            self::ANNUAL_LEAVE => '#17a2b8', // teal
            self::UNPAID_LEAVE => '#ffc107', // yellow
            self::SICK_LEAVE => '#fd7e14',   // orange
            self::FAILED_TO_REPORT => '#6f42c1', // purple
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
