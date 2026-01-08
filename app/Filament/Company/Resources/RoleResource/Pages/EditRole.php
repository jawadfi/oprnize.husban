<?php

namespace App\Filament\Company\Resources\RoleResource\Pages;

use App\Filament\Company\Resources\RoleResource;
use BezhanSalleh\FilamentShield\Resources\RoleResource\Pages\EditRole as ShieldEditRole;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Support\Arr;

class EditRole extends ShieldEditRole
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make()
                ->label('Delete'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure guard_name is set to 'company'
        if (!isset($data['guard_name']) || empty($data['guard_name'])) {
            $data['guard_name'] = 'company';
        }

        $this->permissions = collect($data)
            ->filter(function ($permission, $key) {
                return ! in_array($key, ['name', 'guard_name', 'select_all', Utils::getTenantModelForeignKey()]);
            })
            ->values()
            ->flatten()
            ->unique()
            ->filter(function ($permission) {
                // Exclude AssignedEmployeeResource permissions
                if (stripos($permission, 'AssignedEmployeeResource') !== false || 
                    stripos($permission, 'assigned_employee') !== false) {
                    return false;
                }
                
                // Exclude ProviderCompaniesListing page permissions
                if (stripos($permission, 'ProviderCompaniesListing') !== false ||
                    stripos($permission, 'page_Companies') !== false) {
                    return false;
                }
                
                // Exclude ProviderCompanyEmployees page permissions
                if (stripos($permission, 'ProviderCompanyEmployees') !== false ||
                    stripos($permission, 'page_Employees of') !== false) {
                    return false;
                }
                
                return true;
            });

        if (Arr::has($data, Utils::getTenantModelForeignKey())) {
            return Arr::only($data, ['name', 'guard_name', Utils::getTenantModelForeignKey()]);
        }

        return Arr::only($data, ['name', 'guard_name']);
    }

    protected function afterSave(): void
    {
        // Ensure guard_name is 'company' for permissions
        $guardName = $this->data['guard_name'] ?? 'company';
        
        $permissionModels = collect();
        $this->permissions->each(function ($permission) use ($permissionModels, $guardName) {
            $permissionModels->push(Utils::getPermissionModel()::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guardName,
            ]));
        });

        $this->record->syncPermissions($permissionModels);
    }
}

