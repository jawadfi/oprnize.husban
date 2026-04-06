<?php declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasMappingEnum;
use BenSampo\Enum\Enum;

/**
 * سبب إنهاء الخدمة - End of Service Reason
 */
final class TerminationReason extends Enum
{
    use HasMappingEnum;

    /** استقالة */
    const RESIGNATION = 1;

    /** انتهاء عقد / إنهاء من جهة العمل */
    const CONTRACT_END = 2;

    /** فصل بموجب المادة 80 */
    const ARTICLE_80 = 3;

    public static function getTranslatedEnum(): array
    {
        return [
            self::RESIGNATION  => 'استقالة',
            self::CONTRACT_END => 'انتهاء عقد / إنهاء من جهة العمل',
            self::ARTICLE_80   => 'فصل بموجب المادة 80',
        ];
    }

    public static function getColors(): array
    {
        return [
            self::RESIGNATION  => 'warning',
            self::CONTRACT_END => 'success',
            self::ARTICLE_80   => 'danger',
        ];
    }
}
