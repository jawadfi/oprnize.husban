<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class CompanyTypes extends Enum
{
    const PROVIDER = 'provider';
    const CLIENT = 'client';
}
