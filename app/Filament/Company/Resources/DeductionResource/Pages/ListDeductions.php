<?php

namespace App\Filament\Company\Resources\DeductionResource\Pages;

use App\Filament\Company\Imports\DeductionImporter;
use App\Filament\Company\Resources\DeductionResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListDeductions extends ListRecords
{
    protected static string $resource = DeductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ImportAction::make()
                ->label('استيراد الخصومات والإضافات')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->importer(DeductionImporter::class)
                ->options(function() {
                    $user = Filament::auth()->user();
                    $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);
                    return ['company_id' => $companyId];
                }),
            Actions\CreateAction::make(),
        ];
    }
}
