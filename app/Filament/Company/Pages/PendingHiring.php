<?php

namespace App\Filament\Company\Pages;

use App\Enums\CompanyTypes;
use App\Enums\EmployeeAssignedStatus;
use App\Filament\Schema\EmployeeSchema;
use App\Models\Branch;
use App\Models\EmployeeAssigned;
use Filament\Facades\Filament;
use Filament\Forms;
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

    /**
     * Determine if current user is on the CLIENT side (the one who approves with branch)
     */
    private function isClientSide(): bool
    {
        $user = Filament::auth()->user();

        if ($user instanceof \App\Models\Company) {
            return $user->type === CompanyTypes::CLIENT;
        }

        if ($user instanceof \App\Models\User) {
            return $user->company?->type === CompanyTypes::CLIENT;
        }

        return false;
    }

    /**
     * Get branches for the current client company
     */
    private function getClientBranches(): array
    {
        $user = Filament::auth()->user();
        $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);

        if (!$companyId) {
            return [];
        }

        return Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('name', 'id')
            ->toArray();
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
                    ->with(['employee', 'company', 'branch'])
                    ->where(function ($query) use ($companyId) {
                        $query->where('employee_assigned.company_id', $companyId)
                            ->orWhereHas('employee', function ($q) use ($companyId) {
                               return $q->where('employees.company_id', $companyId);
                            });
                    })
                    ->where(function ($query) {
                        $query->where('status', EmployeeAssignedStatus::PENDING)
                            ->orWhereTodayOrAfter('start_date');
                    });
            })
            ->columns([
                ...EmployeeSchema::getTableColumns(
                    false,
                    'employee.'
                ),
                TextColumn::make('company.name')
                    ->label('الشركة المعينة / Assigned To')
                    ->searchable(),
                TextColumn::make('start_date')->date('Y-m-d'),
                TextColumn::make('branch.name')
                    ->label('الفرع / Branch')
                    ->placeholder('-'),
                TextColumn::make('arrival_date')
                    ->label('تاريخ الوصول / Arrival')
                    ->date('Y-m-d')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->formatStateUsing(fn($state) => EmployeeAssignedStatus::getKey($state))
                    ->badge()
                    ->color(fn($state) => EmployeeAssignedStatus::getColor($state)),
            ])
            ->actions([
                // CLIENT approves with branch selection popup
                Action::make('approved')
                    ->label('موافقة / Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->form(function () {
                        if ($this->isClientSide()) {
                            return [
                                Forms\Components\Select::make('branch_id')
                                    ->label('اختر الفرع / Choose Branch (Location)')
                                    ->options(fn () => $this->getClientBranches())
                                    ->required()
                                    ->searchable()
                                    ->helperText('سيتم تعيين الموظف في الفرع المحدد / Employee will be placed in the selected branch'),
                            ];
                        }

                        return [];
                    })
                    ->visible(function (?EmployeeAssigned $record) {
                        $user = Filament::auth()->user();
                        $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);
                        return ($record?->company_id === $companyId || $record?->employee->company_id === $companyId) 
                            && $record->status === EmployeeAssignedStatus::PENDING;
                    })
                    ->requiresConfirmation()
                    ->action(function (EmployeeAssigned $record, array $data) {
                        $record->updateStatus(EmployeeAssignedStatus::APPROVED);

                        // Update employee's company_assigned_id
                        $record->employee->update(['company_assigned_id' => $record->company_id]);

                        // If client selected a branch, assign employee to that branch
                        if (!empty($data['branch_id'])) {
                            $record->update(['branch_id' => $data['branch_id']]);

                            // Also add to branch_employee pivot table
                            $record->employee->branches()->syncWithoutDetaching([
                                $data['branch_id'] => [
                                    'start_date' => $record->start_date ?? now(),
                                    'is_active' => true,
                                ],
                            ]);
                        }

                        Notification::make()
                            ->title("تمت الموافقة بنجاح / Employee approved successfully")
                            ->success()
                            ->send();
                    }),

                // Edit start date (for client company owner / authorized users)
                Action::make('edit_start_date')
                    ->label('تعديل تاريخ التعيين / Edit Start Date')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->form(fn (EmployeeAssigned $record) => [
                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ التعيين / Assignment Start Date')
                            ->required()
                            ->default($record->start_date),
                    ])
                    ->visible(function (?EmployeeAssigned $record) {
                        if (!$record) return false;

                        $user = Filament::auth()->user();

                        // Company owner (CLIENT side)
                        if ($user instanceof \App\Models\Company) {
                            return $record->company_id === $user->id;
                        }

                        // Authorized user in the client company
                        if ($user instanceof \App\Models\User) {
                            return $user->company_id === $record->company_id;
                        }

                        return false;
                    })
                    ->requiresConfirmation()
                    ->action(function (EmployeeAssigned $record, array $data) {
                        $record->update(['start_date' => $data['start_date']]);

                        // Also update branch pivot start_date if branch is assigned
                        if ($record->branch_id) {
                            $record->employee->branches()->updateExistingPivot(
                                $record->branch_id,
                                ['start_date' => $data['start_date']]
                            );
                        }

                        Notification::make()
                            ->title("تم تعديل تاريخ التعيين بنجاح / Start date updated")
                            ->success()
                            ->send();
                    }),

                // Set arrival date (for branch managers)
                Action::make('set_arrival')
                    ->label('تحديد تاريخ الوصول / Set Arrival')
                    ->icon('heroicon-o-calendar-days')
                    ->color('info')
                    ->form([
                        Forms\Components\DatePicker::make('arrival_date')
                            ->label('تاريخ وصول الموظف للفرع / Employee Arrival Date')
                            ->required()
                            ->default(now()),
                    ])
                    ->visible(function (?EmployeeAssigned $record) {
                        // Only for approved employees that don't have arrival date yet
                        if ($record->status !== EmployeeAssignedStatus::APPROVED) {
                            return false;
                        }

                        $user = Filament::auth()->user();
                        
                        // Company owner can always set
                        if ($user instanceof \App\Models\Company) {
                            return $record->company_id === $user->id;
                        }

                        // Branch manager can set for their branch
                        if ($user instanceof \App\Models\User) {
                            if ($user->isBranchManager() && $record->branch_id) {
                                return $user->managedBranches()->where('branches.id', $record->branch_id)->exists();
                            }
                            return $user->company_id === $record->company_id;
                        }

                        return false;
                    })
                    ->action(function (EmployeeAssigned $record, array $data) {
                        $record->update(['arrival_date' => $data['arrival_date']]);

                        Notification::make()
                            ->title("تم تحديد تاريخ الوصول بنجاح / Arrival date set")
                            ->success()
                            ->send();
                    }),

                Action::make('declined')
                    ->label('رفض / Decline')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(function (?EmployeeAssigned $record) {
                        $user = Filament::auth()->user();
                        $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);
                        return ($record?->company_id === $companyId || $record?->employee->company_id === $companyId) 
                            && $record->status === EmployeeAssignedStatus::PENDING;
                    })
                    ->requiresConfirmation()
                    ->action(function (EmployeeAssigned $record) {
                        $record->updateStatus(EmployeeAssignedStatus::DECLINED);
                        Notification::make()
                            ->title("تم رفض الموظف / Employee declined")
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\BulkAction::make('approve_selected')
                        ->label('موافقة على المحدد / Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->form(function () {
                            if ($this->isClientSide()) {
                                return [
                                    Forms\Components\Select::make('branch_id')
                                        ->label('اختر الفرع / Choose Branch')
                                        ->options(fn () => $this->getClientBranches())
                                        ->required()
                                        ->searchable(),
                                ];
                            }

                            return [];
                        })
                        ->requiresConfirmation()
                        ->action(function ($records, array $data) {
                            $user = Filament::auth()->user();
                            $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);
                            
                            $count = 0;
                            foreach ($records as $record) {
                                if (($record->company_id === $companyId || $record->employee->company_id === $companyId) 
                                    && $record->status === EmployeeAssignedStatus::PENDING) {
                                    $record->updateStatus(EmployeeAssignedStatus::APPROVED);
                                    $record->employee->update(['company_assigned_id' => $record->company_id]);

                                    if (!empty($data['branch_id'])) {
                                        $record->update(['branch_id' => $data['branch_id']]);
                                        $record->employee->branches()->syncWithoutDetaching([
                                            $data['branch_id'] => [
                                                'start_date' => $record->start_date ?? now(),
                                                'is_active' => true,
                                            ],
                                        ]);
                                    }

                                    $count++;
                                }
                            }
                            
                            Notification::make()
                                ->title("تمت الموافقة على {$count} موظف بنجاح")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    \Filament\Tables\Actions\BulkAction::make('decline_selected')
                        ->label('رفض المحدد / Decline Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $user = Filament::auth()->user();
                            $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);
                            
                            $count = 0;
                            foreach ($records as $record) {
                                if (($record->company_id === $companyId || $record->employee->company_id === $companyId) 
                                    && $record->status === EmployeeAssignedStatus::PENDING) {
                                    $record->updateStatus(EmployeeAssignedStatus::DECLINED);
                                    $count++;
                                }
                            }
                            
                            Notification::make()
                                ->title("تم رفض {$count} موظف")
                                ->warning()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->paginated();
    }
}
