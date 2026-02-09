<?php declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasMappingEnum;
use BenSampo\Enum\Enum;

/**
 * نوع الخصم - Deduction type
 */
final class DeductionType extends Enum
{
    use HasMappingEnum;

    /** خصم بالأيام */
    const DAYS = 'days';

    /** خصم مبلغ ثابت */
    const FIXED = 'fixed';

    public static function getTranslatedEnum(): array
    {
        return [
            self::DAYS => 'خصم بالأيام',
            self::FIXED => 'مبلغ ثابت',
        ];
    }

    public static function getColors(): array
    {
        return [
            self::DAYS => 'warning',
            self::FIXED => 'info',
        ];
    }
}
