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

    protected static ?string $navigationLabel = 'Pending Hiring';

    public function mount(): void
    {
        abort_unless($this->canAccess(), 403);
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        
        // Company model has all access
        if ($user instanceof \App\Models\Company) {
            return true;
        }
        
        // User model needs permission
        if ($user instanceof \App\Models\User) {
            return $user->can('page_PendingHiring');
        }
        
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function() {
                $user = Filament::auth()->user();
                $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);
                
                if (!$companyId) {
                    return EmployeeAssigned::query()->whereRaw('1 = 0');
                }
                
                return EmployeeAssigned::query()
                    ->with(['employee','company'])
                    ->where(function ($query) use ($companyId) {
                        $query->where('employee_assigned.company_id', $companyId)
                            ->orWhereHas('employee',function ($q) use ($companyId) {
                               return $q->where('employees.company_id', $companyId);
                            });
                    })
                    ->where(function ($query){
                        $query->where('status',EmployeeAssignedStatus::PENDING)
                            ->orWhereTodayOrAfter('start_date');
                    });
            })
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
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->visible(function(?EmployeeAssigned $record) {
                        $user = Filament::auth()->user();
                        $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);
                        return $record?->company_id === $companyId && $record->status === EmployeeAssignedStatus::PENDING;
                    })
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (EmployeeAssigned $record){
                        $record->updateStatus(EmployeeAssignedStatus::APPROVED);
                        Notification::make()
                            ->title("The employee has been approved successfully")
                            ->success()
                            ->send();
                    }),
                Action::make('declined')
                    ->label('Decline')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(function(?EmployeeAssigned $record) {
                        $user = Filament::auth()->user();
                        $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);
                        return $record?->company_id === $companyId && $record->status === EmployeeAssignedStatus::PENDING;
                    })
                    ->requiresConfirmation()
                    ->action(function (EmployeeAssigned $record){
                        $record->updateStatus(EmployeeAssignedStatus::DECLINED);
                        Notification::make()
                            ->title("The employee has been declined successfully")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $user = Filament::auth()->user();
                            $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);
                            
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->company_id === $companyId && $record->status === EmployeeAssignedStatus::PENDING) {
                                    $record->updateStatus(EmployeeAssignedStatus::APPROVED);
                                    $count++;
                                }
                            }
                            
                            Notification::make()
                                ->title("Approved {$count} employee(s) successfully")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    \Filament\Tables\Actions\BulkAction::make('decline_selected')
                        ->label('Decline Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $user = Filament::auth()->user();
                            $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);
                            
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->company_id === $companyId && $record->status === EmployeeAssignedStatus::PENDING) {
                                    $record->updateStatus(EmployeeAssignedStatus::DECLINED);
                                    $count++;
                                }
                            }
                            
                            Notification::make()
                                ->title("Declined {$count} employee(s)")
                                ->warning()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->paginated();
    }
}
