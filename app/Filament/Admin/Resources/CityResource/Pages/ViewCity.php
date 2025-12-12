<?php

namespace App\Filament\Admin\Resources\CityResource\Pages;

use App\Filament\Admin\Resources\CityResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCity extends ViewRecord
{
    protected static string $resource = CityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
