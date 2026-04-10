<?php

namespace App\Enums;

enum CompanyConnectionStatus: string
{
    case PENDING  = 'pending';
    case APPROVED = 'approved';
    case DECLINED = 'declined';
}
