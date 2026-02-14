<?php

namespace App\Enums;

enum BranchEntryType: string
{
    case ATTENDANCE = 'attendance';
    case DEDUCTION = 'deduction';
    case ABSENCE = 'absence';
    case OVERTIME = 'overtime';
    case ADDITION = 'addition';

    public function label(): string
    {
        return match($this) {
            self::ATTENDANCE => 'حضور وانصراف / Attendance',
            self::DEDUCTION => 'حسومات / Deductions',
            self::ABSENCE => 'غيابات / Absences',
            self::OVERTIME => 'عمل إضافي / Overtime',
            self::ADDITION => 'مبالغ إضافية / Additions',
        };
    }

    public static function getTranslatedEnum(): array
    {
        return collect(self::cases())->mapWithKeys(fn($case) => [
            $case->value => $case->label(),
        ])->toArray();
    }
}
