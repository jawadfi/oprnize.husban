<?php

namespace App\Helpers\Classes\Interfaces;

use Illuminate\Database\Eloquent\Relations\MorphOne;

interface ISetting
{
    public function setting():MorphOne;
}
