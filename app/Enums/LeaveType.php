<?php declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasMappingEnum;
use BenSampo\Enum\Enum;

final class LeaveType extends Enum
{
    use HasMappingEnum;
    
    const ANNUAL = 'annual';
    const UNPAID = 'unpaid';
    const SICK = 'sick';
    const DEATH = 'death';
    const NEWBORN = 'newborn';

    public static function getTranslatedEnum(): array
    {
        return [
            self::ANNUAL => 'Annual',
            self::UNPAID => 'Unpaid',
            self::SICK => 'Sick',
            self::DEATH => 'Death',
            self::NEWBORN => 'Newborn',
        ];
    }

    public static function getColors(): array
    {
        return [
            self::ANNUAL => 'success',
            self::UNPAID => 'warning',
            self::SICK => 'danger',
            self::DEATH => 'gray',
            self::NEWBORN => 'info',
        ];
    }
}

