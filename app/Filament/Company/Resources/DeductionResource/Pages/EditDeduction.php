<?php

namespace App\Filament\Company\Resources\DeductionResource\Pages;

use App\Enums\DeductionType;
use App\Filament\Company\Resources\DeductionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeduction extends EditRecord
{
    protected static string $resource = DeductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Auto-calculate amount for days type
        if ($data['type'] === DeductionType::DAYS && !empty($data['days']) && !empty($data['daily_rate'])) {
            $data['amount'] = $data['days'] * $data['daily_rate'];
        }

        return $data;
    }
}
