<?php

namespace App\Filament\Company\Resources\BranchEntryResource\Pages;

use App\Enums\BranchEntryStatus;
use App\Filament\Company\Resources\BranchEntryResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use App\Models\User;

class EditBranchEntry extends EditRecord
{
    protected static string $resource = BranchEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('submit')
                ->label('إرسال للمراجعة / Submit')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === BranchEntryStatus::DRAFT)
                ->action(function () {
                    $user = Filament::auth()->user();

                    BranchEntryResource::applyApprovedEntry($this->record);

                    $this->record->update([
                        'status' => BranchEntryStatus::APPROVED,
                        'submitted_by' => $user instanceof User ? $user->id : null,
                        'submitted_at' => now(),
                        'reviewed_by' => $user instanceof User ? $user->id : null,
                        'reviewed_at' => now(),
                        'review_notes' => null,
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('تم إرسال واعتماد الإدخال بنجاح')
                        ->success()
                        ->send();

                    $this->redirect(BranchEntryResource::getUrl('index'));
                }),

            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === BranchEntryStatus::DRAFT),
        ];
    }
}
