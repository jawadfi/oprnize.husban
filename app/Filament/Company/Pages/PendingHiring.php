<?php

namespace App\Filament\Company\Pages;

use App\Enums\EmployeeAssignedStatus;
use App\Filament\Schema\EmployeeSchema;
use App\Models\EmployeeAssigned;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class PendingHiring extends Page implements HasTable
{
    use InteractsWithTable;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string $view = 'filament.company.pages.pending-hiring';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn()=>EmployeeAssigned::query()
                    ->with(['employee','company'])
                    ->where(function ($query) {
                        $query->where('employee_assigned.company_id',Filament::auth()->id())
                            ->orWhereHas('employee',function ($query) {
                               return $query->where('employees.company_id',Filament::auth()->id());
                            });
                    })
                    ->where(function ($query){
                        $query->where('status',EmployeeAssignedStatus::PENDING)
                            ->orWhereTodayOrAfter('start_date');
                    })
            )
            ->columns([
                ...EmployeeSchema::getTableColumns(
                    false,
                    'employee.'
                ),
                TextColumn::make('start_date')->date('Y-m-d'),
                TextColumn::make('status')
                    ->formatStateUsing(fn($state)=>EmployeeAssignedStatus::getKey($state))
                    ->badge()
                    ->color(fn($state)=>EmployeeAssignedStatus::getColor($state)),
            ])
            ->actions([
                Action::make('approved')
                    ->icon('heroicon-o-check')
                    ->visible(fn(?EmployeeAssigned $record)=>$record?->company_id === Filament::auth()->id() && $record->status === EmployeeAssignedStatus::PENDING)
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (EmployeeAssigned $record){
                        $record->updateStatus(EmployeeAssignedStatus::APPROVED);
                        Notification::make()
                            ->title("The employee has been approved successfully")
                            ->success()
                            ->send();
                    }),
                Action::make('declined') ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn(?EmployeeAssigned $record)=>$record?->company_id === Filament::auth()->id() && $record->status === EmployeeAssignedStatus::PENDING)                    ->requiresConfirmation()
                    ->action(function (EmployeeAssigned $record){
                        $record->updateStatus(EmployeeAssignedStatus::DECLINED);
                        Notification::make()
                            ->title("The employee has been declined successfully")
                            ->success()
                            ->send();
                    }),
            ])
            ->paginated();
    }
}
