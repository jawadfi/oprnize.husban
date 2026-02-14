<?php

namespace App\Filament\Company\Resources\BranchResource\Pages;

use App\Filament\Company\Resources\BranchResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateBranch extends CreateRecord
{
    protected static string $resource = BranchResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();
        $companyId = $user instanceof \App\Models\Company
            ? $user->id
            : ($user instanceof \App\Models\User ? $user->company_id : null);

        $data['company_id'] = $companyId;

        return $data;
    }
}
