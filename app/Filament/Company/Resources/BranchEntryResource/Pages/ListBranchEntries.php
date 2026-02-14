<?php

namespace App\Filament\Company\Resources\BranchEntryResource\Pages;

use App\Filament\Company\Resources\BranchEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBranchEntries extends ListRecords
{
    protected static string $resource = BranchEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إدخال جديد / New Entry'),
        ];
    }
}
