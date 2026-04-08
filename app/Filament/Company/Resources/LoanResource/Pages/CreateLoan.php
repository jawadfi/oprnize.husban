<?php

namespace App\Filament\Company\Resources\LoanResource\Pages;

use App\Enums\LoanStatus;
use App\Filament\Company\Resources\LoanResource;
use App\Models\Company;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateLoan extends CreateRecord
{
    protected static string $resource = LoanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user      = Filament::auth()->user();
        $companyId = $user instanceof Company ? $user->id : $user->company_id;

        $data['company_id']        = $companyId;
        $data['remaining_amount']  = $data['amount'];
        $data['status']            = LoanStatus::ACTIVE;

        // Recalculate to guard against JS-side rounding differences
        if ((int) $data['months'] > 0) {
            $data['monthly_deduction'] = round((float) $data['amount'] / (int) $data['months'], 2);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
