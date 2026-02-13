<?php

namespace App\Filament\Company\Resources\EmployeeResource\Pages;
use App\Filament\Company\Imports\EmployeeImporter;
use Filament\Facades\Filament;
use Filament\Resources\Components\Tab;

use App\Filament\Company\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected static ?string $title ='Employee and provider services';
    public function getTabs(): array
    {
        $user = Filament::auth()->user();
        $company = $user instanceof \App\Models\Company ? $user : ($user instanceof \App\Models\User ? $user->company : null);

        if (!$company) {
            return [];
        }

        return [
            'available' => Tab::make()
                ->badge(fn()=> $company->original_employees()->byStatus(\App\Enums\EmployeeStatusStatus::AVAILABLE)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->byStatus(\App\Enums\EmployeeStatusStatus::AVAILABLE)),

            'in_service' => Tab::make()
                ->badge(fn()=> $company->original_employees()->byStatus(\App\Enums\EmployeeStatusStatus::IN_SERVICE)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->byStatus(\App\Enums\EmployeeStatusStatus::IN_SERVICE)),

            'ended_service' => Tab::make()
                ->badge(fn()=> $company->original_employees()->byStatus(\App\Enums\EmployeeStatusStatus::ENDED_SERVICE)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->byStatus(\App\Enums\EmployeeStatusStatus::ENDED_SERVICE)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\ImportAction::make()
                ->label('استيراد الموظفين')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->importer(EmployeeImporter::class)
                ->options(function() {
                    $user = Filament::auth()->user();
                    $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);
                    return ['company_id' => $companyId];
                }),
        ];
    }
}

