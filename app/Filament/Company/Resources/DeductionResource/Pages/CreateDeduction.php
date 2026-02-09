<?php

namespace App\Filament\Company\Resources\DeductionResource\Pages;

use App\Enums\DeductionType;
use App\Filament\Company\Resources\DeductionResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateDeduction extends CreateRecord
{
    protected static string $resource = DeductionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();
        $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);

        $data['company_id'] = $companyId;
        $data['created_by_company_id'] = $companyId;

        // Auto-calculate amount for days type
        if ($data['type'] === DeductionType::DAYS && !empty($data['days']) && !empty($data['daily_rate'])) {
            $data['amount'] = $data['days'] * $data['daily_rate'];
        }

        return $data;
    }
}
