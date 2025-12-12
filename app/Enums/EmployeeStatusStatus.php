<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class EmployeeStatusStatus extends Enum
{
    const AVAILABLE = 'available';
    const ENDED_SERVICE = 'ended_service';
    const IN_SERVICE = 'in_service';
}
