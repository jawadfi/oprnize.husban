<?php

namespace App\Filament\Company\Resources\UserResource\Pages;

use App\Filament\Company\Resources\UserResource;
use Filament\Facades\Filament;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $company = Filament::auth()->user();
        $companyId = $company instanceof \App\Models\Company ? $company->id : ($company instanceof \App\Models\User ? $company->company_id : null);
        
        $data['company_id'] = $companyId;
        $data['email_verified_at'] = now();
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
