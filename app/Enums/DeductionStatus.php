<?php declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasMappingEnum;
use BenSampo\Enum\Enum;

/**
 * حالة الخصم
 */
final class DeductionStatus extends Enum
{
    use HasMappingEnum;

    const PENDING = 'pending';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';

    public static function getTranslatedEnum(): array
    {
        return [
            self::PENDING => 'بانتظار الموافقة',
            self::APPROVED => 'معتمد',
            self::REJECTED => 'مرفوض',
        ];
    }

    public static function getColors(): array
    {
        return [
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        ];
    }
}
