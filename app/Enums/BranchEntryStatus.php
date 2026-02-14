<?php

namespace App\Enums;

enum BranchEntryStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'مسودة / Draft',
            self::SUBMITTED => 'تم الإرسال / Submitted',
            self::APPROVED => 'معتمد / Approved',
            self::REJECTED => 'مرفوض / Rejected',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::SUBMITTED => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }

    public static function getTranslatedEnum(): array
    {
        return collect(self::cases())->mapWithKeys(fn($case) => [
            $case->value => $case->label(),
        ])->toArray();
    }
}
