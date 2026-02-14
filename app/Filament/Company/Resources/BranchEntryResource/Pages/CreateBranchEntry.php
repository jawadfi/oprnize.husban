<?php

namespace App\Filament\Company\Resources\BranchEntryResource\Pages;

use App\Enums\BranchEntryStatus;
use App\Filament\Company\Resources\BranchEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBranchEntry extends CreateRecord
{
    protected static string $resource = BranchEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = BranchEntryStatus::DRAFT->value;
        return $data;
    }
}
