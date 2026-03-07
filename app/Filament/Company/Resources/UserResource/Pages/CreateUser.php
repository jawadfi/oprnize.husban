<?php

namespace App\Filament\Company\Resources\UserResource\Pages;

use App\Filament\Company\Resources\UserResource;
use Filament\Facades\Filament;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected array $roles = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $company = Filament::auth()->user();
        $companyId = $company instanceof \App\Models\Company ? $company->id : ($company instanceof \App\Models\User ? $company->company_id : null);
        
        $data['company_id'] = $companyId;
        $data['email_verified_at'] = now();

        // Extract roles before create (not a DB column)
        $this->roles = $data['roles'] ?? [];
        unset($data['roles']);
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Explicitly sync roles after record is created
        if (!empty($this->roles)) {
            $this->record->roles()->sync($this->roles);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
