<?php

namespace App\Filament\Company\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Auth\EditProfile;

class Profile extends EditProfile
{
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }
}

