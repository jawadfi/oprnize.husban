<?php

namespace App\Filament\Company\Pages;

use App\Enums\CompanyTypes;
use App\Enums\EmployeeAssignedStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\EmployeeAssigned;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
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

    public ?int $selectedBranchId = null;

    public ?int $selectedProviderCompanyId = null;

    public function hasSelectedProvider(): bool
    {
        return $this->selectedProviderCompanyId !== null;
    }

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
    public function isClientSide(): bool
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

    private function getCurrentCompanyId(): ?int
    {
        $user = Filament::auth()->user();

        return $user instanceof \App\Models\Company
            ? (int) $user->id
            : ($user instanceof \App\Models\User ? (int) $user->company_id : null);
    }

    public function getBranchCards(): array
    {
        if (!$this->isClientSide()) {
            return [];
        }

        // Branch filtering is only meaningful after choosing a provider company.
        if (!$this->hasSelectedProvider()) {
            return [];
        }

        $companyId = $this->getCurrentCompanyId();
        if (!$companyId) {
            return [];
        }

        $branches = Branch::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $baseQuery = EmployeeAssigned::query()
            ->where('company_id', $companyId)
            ->where(function ($query) {
                $query->where('status', EmployeeAssignedStatus::PENDING)
                    ->orWhere('start_date', '>=', now()->toDateString());
            });

        $baseQuery->whereHas('employee', function ($query) {
            $query->where('employees.company_id', $this->selectedProviderCompanyId);
        });

        $allCount = (clone $baseQuery)->count();
        $unassignedCount = (clone $baseQuery)->whereNull('branch_id')->count();

        $cards = [
            [
                'id' => null,
                'name' => 'All / الكل',
                'count' => $allCount,
                'is_active' => $this->selectedBranchId === null,
                'is_unassigned' => false,
            ],
            [
                'id' => -1,
                'name' => 'Unassigned / بدون فرع',
                'count' => $unassignedCount,
                'is_active' => $this->selectedBranchId === -1,
                'is_unassigned' => true,
            ],
        ];

        foreach ($branches as $branch) {
            $cards[] = [
                'id' => (int) $branch->id,
                'name' => $branch->name,
                'count' => (clone $baseQuery)->where('branch_id', $branch->id)->count(),
                'is_active' => $this->selectedBranchId === (int) $branch->id,
                'is_unassigned' => false,
            ];
        }

        return $cards;
    }

    public function getProviderCards(): array
    {
        if (!$this->isClientSide()) {
            return [];
        }

        $companyId = $this->getCurrentCompanyId();
        if (!$companyId) {
            return [];
        }

        $baseQuery = EmployeeAssigned::query()
            ->where('employee_assigned.company_id', $companyId)
            ->where(function ($query) {
                $query->where('employee_assigned.status', EmployeeAssignedStatus::PENDING)
                    ->orWhere('employee_assigned.start_date', '>=', now()->toDateString());
            });

        $providers = Company::query()
            ->where('type', CompanyTypes::PROVIDER)
            ->whereKeyNot($companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $cards = [];

        foreach ($providers as $provider) {
            $count = (clone $baseQuery)
                ->whereHas('employee', function ($query) use ($provider) {
                    $query->where('company_id', $provider->id);
                })
                ->count();

            $cards[] = [
                'id' => (int) $provider->id,
                'name' => $provider->name,
                'count' => $count,
                'is_active' => $this->selectedProviderCompanyId === (int) $provider->id,
            ];
        }

        return $cards;
    }

    public function selectBranchFilter(?int $branchId = null): void
    {
        $this->selectedBranchId = $branchId;
        $this->resetTable();
    }

    public function selectProviderFilter(?int $providerCompanyId = null): void
    {
        $this->selectedProviderCompanyId = $providerCompanyId;
        // Reset branch filter whenever provider changes.
        $this->selectedBranchId = null;
        $this->resetTable();
    }

    public function assignToBranch(int $assignmentId, int $branchId): void
    {
        Log::info('PendingHiring.assignToBranch.start', [
            'assignment_id' => $assignmentId,
            'branch_id' => $branchId,
            'selected_branch_filter' => $this->selectedBranchId,
            'user_id' => Filament::auth()->id(),
        ]);

        if (!$this->isClientSide()) {
            Log::warning('PendingHiring.assignToBranch.denied_not_client_side', [
                'assignment_id' => $assignmentId,
                'branch_id' => $branchId,
                'user_id' => Filament::auth()->id(),
            ]);

            Notification::make()
                ->title('هذه العملية متاحة للعميل فقط')
                ->warning()
                ->send();
            return;
        }

        $companyId = $this->getCurrentCompanyId();
        if (!$companyId) {
            Log::warning('PendingHiring.assignToBranch.missing_company_id', [
                'assignment_id' => $assignmentId,
                'branch_id' => $branchId,
                'user_id' => Filament::auth()->id(),
            ]);
            return;
        }

        $branch = Branch::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereKey($branchId)
            ->first();

        if (!$branch) {
            Log::warning('PendingHiring.assignToBranch.invalid_branch', [
                'assignment_id' => $assignmentId,
                'branch_id' => $branchId,
                'company_id' => $companyId,
            ]);

            Notification::make()
                ->title('الفرع غير صالح')
                ->danger()
                ->send();
            return;
        }

        $assignment = EmployeeAssigned::query()
            ->where('company_id', $companyId)
            ->whereKey($assignmentId)
            ->first();

        if (!$assignment) {
            Log::warning('PendingHiring.assignToBranch.assignment_not_found', [
                'assignment_id' => $assignmentId,
                'branch_id' => $branchId,
                'company_id' => $companyId,
            ]);

            Notification::make()
                ->title('تعذر العثور على سجل الموظف')
                ->danger()
                ->send();
            return;
        }

        $assignment->update(['branch_id' => $branchId]);

        $assignment->employee->branches()->syncWithoutDetaching([
            $branchId => [
                'start_date' => $assignment->start_date ?? now(),
                'is_active' => true,
            ],
        ]);

        Log::info('PendingHiring.assignToBranch.success', [
            'assignment_id' => $assignmentId,
            'employee_id' => $assignment->employee_id,
            'branch_id' => $branchId,
            'company_id' => $companyId,
        ]);

        Notification::make()
            ->title('تم تعيين الموظف للفرع بنجاح')
            ->success()
            ->send();

        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function() {
                $companyId = $this->getCurrentCompanyId();
                
                if (!$companyId) {
                    return EmployeeAssigned::query()->whereRaw('1 = 0');
                }

                if ($this->isClientSide() && $this->selectedProviderCompanyId === null) {
                    return EmployeeAssigned::query()->whereRaw('1 = 0');
                }
                
                $query = EmployeeAssigned::query()
                    ->with(['employee.currentPayroll', 'company', 'branch'])
                    ->where(function ($query) use ($companyId) {
                        $query->where('employee_assigned.company_id', $companyId)
                            ->orWhereHas('employee', function ($q) use ($companyId) {
                               return $q->where('employees.company_id', $companyId);
                            });
                    })
                    ->where(function ($query) {
                        $query->where('status', EmployeeAssignedStatus::PENDING)
                            ->orWhere('start_date', '>=', now()->toDateString());
                    });

                if ($this->selectedBranchId !== null) {
                    if ($this->selectedBranchId === -1) {
                        $query->whereNull('employee_assigned.branch_id');
                    } else {
                        $query->where('employee_assigned.branch_id', $this->selectedBranchId);
                    }
                }

                if ($this->selectedProviderCompanyId !== null) {
                    $query->whereHas('employee', function ($q) {
                        $q->where('employees.company_id', $this->selectedProviderCompanyId);
                    });
                }

                return $query;
            })
            ->columns([
                TextColumn::make('drag_to_branch')
                    ->label('Drag')
                    ->alignCenter()
                    ->html()
                    ->formatStateUsing(fn(EmployeeAssigned $record) => new HtmlString(
                        '<span class="inline-flex items-center justify-center rounded-md border border-gray-300 px-2 py-1 text-xs font-medium cursor-grab" '
                        . 'title="اسحب للتعيين">'
                        . '⠿'
                        . '</span>'
                    ))
                    ->toggleable(),
                // --- Employee Identity ---
                TextColumn::make('employee.name')
                    ->label('اسم الموظف / Name')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->extraAttributes(fn(EmployeeAssigned $record): array => [
                        'data-assignment-id' => (string) $record->id,
                        'style' => 'cursor: grab',
                    ]),
                TextColumn::make('employee.emp_id')
                    ->label('الرقم الوظيفي / Emp ID')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('employee.nationality')
                    ->label('الجنسية / Nationality')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('employee.job_title')
                    ->label('المسمى الوظيفي / Job Title')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),

                // --- Salary Info (from provider payroll) ---
                TextColumn::make('employee.currentPayroll.basic_salary')
                    ->label('الراتب الأساسي / Basic Salary')
                    ->money('SAR')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('employee.currentPayroll.housing_allowance')
                    ->label('بدل السكن / Housing')
                    ->money('SAR')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('employee.currentPayroll.transportation_allowance')
                    ->label('بدل المواصلات / Transport')
                    ->money('SAR')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('employee.currentPayroll.food_allowance')
                    ->label('بدل الطعام / Food')
                    ->money('SAR')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('employee.currentPayroll.other_allowance')
                    ->label('بدلات أخرى / Other Allow.')
                    ->money('SAR')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('employee.currentPayroll.fees')
                    ->label('الرسوم / Fees')
                    ->money('SAR')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('employee.currentPayroll.total_salary')
                    ->label('إجمالي الراتب / Total Salary')
                    ->money('SAR')
                    ->placeholder('-')
                    ->weight('bold')
                    ->color('success')
                    ->toggleable(),

                // --- Assignment Info ---
                TextColumn::make('company.name')
                    ->label('الشركة المعينة / Assigned To')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->label('تاريخ البدء / Start Date')
                    ->date('Y-m-d'),
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
                        ->form(function (?EmployeeAssigned $record) {
                        if ($this->isClientSide()) {
                            return [
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('تاريخ التعيين / Assignment Start Date')
                                    ->required()
                                    ->default($record?->start_date ?? now()),
                                Forms\Components\Select::make('branch_id')
                                    ->label('اختر الفرع / Choose Branch (Location)')
                                    ->options(fn () => $this->getClientBranches())
                                    ->required()
                                    ->searchable()
                                    ->helperText('يجب تحديد الفرع قبل الموافقة / Branch is required before approval'),
                            ];
                        }

                        return [];
                    })
                    ->visible(function (?EmployeeAssigned $record) {
                        $user = Filament::auth()->user();
                        $companyId = (int) ($user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null));
                        // Only the CLIENT company (company_id in the assignment) can approve
                        return (int) $record?->company_id === $companyId
                            && $record->status === EmployeeAssignedStatus::PENDING;
                    })
                    ->requiresConfirmation()
                    ->action(function (EmployeeAssigned $record, array $data) {
                        $record->update([
                            'status' => EmployeeAssignedStatus::APPROVED,
                            'start_date' => $data['start_date'],
                            'branch_id' => $data['branch_id'],
                        ]);

                        // Update employee's company_assigned_id
                        $record->employee->update(['company_assigned_id' => $record->company_id]);

                        // Also add to branch_employee pivot table
                        $record->employee->branches()->syncWithoutDetaching([
                            $data['branch_id'] => [
                                'start_date' => $data['start_date'],
                                'is_active' => true,
                            ],
                        ]);

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
                            return (int) $record->company_id === (int) $user->id;
                        }

                        // Authorized user in the client company
                        if ($user instanceof \App\Models\User) {
                            return (int) $user->company_id === (int) $record->company_id;
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
                            return (int) $record->company_id === (int) $user->id;
                        }

                        // Branch manager can set for their branch
                        if ($user instanceof \App\Models\User) {
                            if ($user->isBranchManager() && $record->branch_id) {
                                return $user->managedBranches()->where('branches.id', $record->branch_id)->exists();
                            }
                            return (int) $user->company_id === (int) $record->company_id;
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
                        $companyId = (int) ($user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null));
                        return ((int) $record?->company_id === $companyId || (int) $record?->employee->company_id === $companyId) 
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
                                    Forms\Components\DatePicker::make('start_date')
                                        ->label('تاريخ التعيين / Assignment Start Date')
                                        ->required()
                                        ->default(now()),
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
                            $companyId = (int) ($user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null));

                            $count = 0;
                            foreach ($records as $record) {
                                if ((int) $record->company_id === $companyId
                                    && $record->status === EmployeeAssignedStatus::PENDING) {
                                    $record->update([
                                        'status' => EmployeeAssignedStatus::APPROVED,
                                        'start_date' => $data['start_date'],
                                        'branch_id' => $data['branch_id'],
                                    ]);
                                    $record->employee->update(['company_assigned_id' => $record->company_id]);

                                    $record->employee->branches()->syncWithoutDetaching([
                                        $data['branch_id'] => [
                                            'start_date' => $data['start_date'],
                                            'is_active' => true,
                                        ],
                                    ]);

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
                            $companyId = (int) ($user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null));
                            
                            $count = 0;
                            foreach ($records as $record) {
                                if (((int) $record->company_id === $companyId || (int) $record->employee->company_id === $companyId) 
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
