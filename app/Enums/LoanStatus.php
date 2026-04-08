<?php declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasMappingEnum;
use BenSampo\Enum\Enum;

/**
 * حالة القرض - Loan status
 */
final class LoanStatus extends Enum
{
    use HasMappingEnum;

    /** نشط – يتم الاقتطاع شهرياً */
    const ACTIVE = 'active';

    /** مكتمل – تم سداد كامل المبلغ */
    const COMPLETED = 'completed';

    /** موقوف مؤقتاً */
    const PAUSED = 'paused';

    public static function getTranslatedEnum(): array
    {
        return [
            self::ACTIVE    => 'نشط',
            self::COMPLETED => 'مكتمل',
            self::PAUSED    => 'موقوف مؤقتاً',
        ];
    }

    public static function getColors(): array
    {
        return [
            self::ACTIVE    => 'success',
            self::COMPLETED => 'gray',
            self::PAUSED    => 'warning',
        ];
    }
}
