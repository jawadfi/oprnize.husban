<?php declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasMappingEnum;
use BenSampo\Enum\Enum;

final class LeaveRequestStatus extends Enum
{
    use HasMappingEnum;
    
    const PENDING = 'pending';
    const PENDING_CLIENT_APPROVAL = 'pending_client_approval';
    const PENDING_PROVIDER_APPROVAL = 'pending_provider_approval';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';

    public static function getTranslatedEnum(): array
    {
        return [
            self::PENDING => 'Pending',
            self::PENDING_CLIENT_APPROVAL => 'Pending Client Approval',
            self::PENDING_PROVIDER_APPROVAL => 'Pending Provider Approval',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        ];
    }

    public static function getColors(): array
    {
        return [
            self::PENDING => 'warning',
            self::PENDING_CLIENT_APPROVAL => 'warning',
            self::PENDING_PROVIDER_APPROVAL => 'info',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        ];
    }
}

