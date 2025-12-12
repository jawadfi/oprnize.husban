<?php

namespace App\Helpers\Interfaces;

use Illuminate\Database\Eloquent\Relations\MorphOne;

interface ISetting
{
    public function setting():MorphOne;
}
