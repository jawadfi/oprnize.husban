<?php declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasMappingEnum;
use BenSampo\Enum\Enum;

final class EmployeeAssignedStatus extends Enum
{
    use HasMappingEnum;
    const PENDING = 'pending';
    const APPROVED = 'approved';
    const DECLINED = 'declined';
    const ENDED = 'ended';

    public static function getTranslatedEnum(): array
    {
        return [
          self::PENDING => 'بانتظار الموافقة',
          self::APPROVED => 'موافق عليه',
          self::DECLINED => 'مرفوض',
          self::ENDED => 'منتهي الخدمة',
        ];
    }
    public static function getColors(): array
    {
        return [
            self::PENDING => 'primary',
            self::APPROVED => 'success',
            self::DECLINED => 'danger',
            self::ENDED => 'gray',
        ];
    }
}
