<?php

namespace App\Filament\Company\Resources\EmployeeResource\Pages;
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
        return [
            'available' => Tab::make()
                ->badge(fn()=> EmployeeResource::getModel()::byStatus(\App\Enums\EmployeeStatusStatus::AVAILABLE)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->byStatus(\App\Enums\EmployeeStatusStatus::AVAILABLE)),

            'in_service' => Tab::make()
                ->badge(fn()=> EmployeeResource::getModel()::byStatus(\App\Enums\EmployeeStatusStatus::IN_SERVICE)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->byStatus(\App\Enums\EmployeeStatusStatus::IN_SERVICE)),

            'ended_service' => Tab::make()
                ->badge(fn()=> EmployeeResource::getModel()::byStatus(\App\Enums\EmployeeStatusStatus::ENDED_SERVICE)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->byStatus(\App\Enums\EmployeeStatusStatus::ENDED_SERVICE)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
