<?php

namespace App\Filament\Company\Resources\RoleResource\Pages;

use App\Filament\Company\Resources\RoleResource;
use BezhanSalleh\FilamentShield\Resources\RoleResource\Pages\CreateRole as ShieldCreateRole;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Support\Arr;

class CreateRole extends ShieldCreateRole
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
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
            ->unique();

        if (Arr::has($data, Utils::getTenantModelForeignKey())) {
            return Arr::only($data, ['name', 'guard_name', Utils::getTenantModelForeignKey()]);
        }

        return Arr::only($data, ['name', 'guard_name']);
    }

    protected function afterCreate(): void
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

