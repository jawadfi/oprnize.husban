<?php declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasMappingEnum;
use BenSampo\Enum\Enum;

/**
 * حالات الرواتب - Payroll workflow statuses
 */
final class PayrollStatus extends Enum
{
    use HasMappingEnum;

    /** مسودة - Draft: Initial state when payroll is created */
    const DRAFT = 'draft';

    /** مقدم للشركة الأم - CLIENT submitted movements/deductions to PROVIDER */
    const SUBMITTED_TO_PROVIDER = 'submitted_to_provider';

    /** تم الاحتساب - PROVIDER calculated the payroll */
    const CALCULATED = 'calculated';

    /** مقدم للعميل - PROVIDER submitted final payroll to CLIENT for review */
    const SUBMITTED_TO_CLIENT = 'submitted_to_client';

    /** مرتجع - CLIENT sent back for modifications */
    const REBACK = 'reback';

    /** نهائي - Finalized and approved */
    const FINALIZED = 'finalized';

    public static function getTranslatedEnum(): array
    {
        return [
            self::DRAFT => 'مسودة',
            self::SUBMITTED_TO_PROVIDER => 'مقدم للشركة الأم',
            self::CALCULATED => 'تم الاحتساب',
            self::SUBMITTED_TO_CLIENT => 'مقدم للعميل',
            self::REBACK => 'مرتجع للتعديل',
            self::FINALIZED => 'نهائي',
        ];
    }

    public static function getColors(): array
    {
        return [
            self::DRAFT => 'gray',
            self::SUBMITTED_TO_PROVIDER => 'info',
            self::CALCULATED => 'warning',
            self::SUBMITTED_TO_CLIENT => 'primary',
            self::REBACK => 'danger',
            self::FINALIZED => 'success',
        ];
    }
}
