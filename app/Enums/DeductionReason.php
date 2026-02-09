<?php declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasMappingEnum;
use BenSampo\Enum\Enum;

/**
 * سبب الخصم - Deduction reason
 */
final class DeductionReason extends Enum
{
    use HasMappingEnum;

    /** غياب */
    const ABSENCE = 'absence';

    /** تأخير */
    const LATE = 'late';

    /** جزاء / عقوبة */
    const PENALTY = 'penalty';

    /** اشتراك طعام */
    const FOOD_SUBSCRIPTION = 'food_subscription';

    /** سلفة */
    const ADVANCE = 'advance';

    /** أخرى */
    const OTHER = 'other';

    public static function getTranslatedEnum(): array
    {
        return [
            self::ABSENCE => 'غياب',
            self::LATE => 'تأخير',
            self::PENALTY => 'جزاء / عقوبة',
            self::FOOD_SUBSCRIPTION => 'اشتراك طعام',
            self::ADVANCE => 'سلفة',
            self::OTHER => 'أخرى',
        ];
    }

    public static function getColors(): array
    {
        return [
            self::ABSENCE => 'danger',
            self::LATE => 'warning',
            self::PENALTY => 'danger',
            self::FOOD_SUBSCRIPTION => 'info',
            self::ADVANCE => 'primary',
            self::OTHER => 'gray',
        ];
    }
}
