<?php

namespace App\Filament\Company\Pages;

use App\Enums\CompanyTypes;
use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveType;
use App\Models\LeaveRequest;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeaveRequests extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.company.pages.leave-requests';

    protected static ?string $navigationLabel = 'Leave Requests';

    protected static ?string $title = 'Leave Requests';


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
        
        // User model — branch managers can access (supervisor role)
        if ($user instanceof User) {
            if ($user->isBranchManager()) {
                return true;
            }
            try {
                return $user->hasPermissionTo('page_LeaveRequests', 'company');
            } catch (\Exception $e) {
                return true;
            }
        }
        
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function table(Table $table): Table
    {
        $authUser = Filament::auth()->user();
        $isBranchManager = ($authUser instanceof User && $authUser->isBranchManager());
        
        // Ensure company relationship is loaded for User model
        if ($authUser instanceof User) {
            $authUser->load('company');
            $company = $authUser->company;
        } elseif ($authUser instanceof \App\Models\Company) {
            $company = $authUser;
        } else {
            $company = null;
        }
        
        if (!$company) {
            abort(403, 'Company not found for user: ' . ($authUser ? $authUser->email : 'unknown'));
        }
        
        return $table
            ->query(function () use ($company, $authUser, $isBranchManager) {
                return $this->getQuery($company, $authUser, $isBranchManager);
            })
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Employee Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.emp_id')
                    ->label('Employee ID (1)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.iqama_no')
                    ->label('Employee ID (2)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('leave_type')
                    ->label('Leave Type')
                    ->formatStateUsing(fn ($state) => LeaveType::getTranslatedKey($state->value))
                    ->badge()
                    ->color(fn ($state) => LeaveType::getColor($state->value))
                    ->sortable(),
                TextColumn::make('employee.nationality')
                    ->label('Nationality')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('End Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('days_count')
                    ->label('Days Count')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => LeaveRequestStatus::getTranslatedKey($state->value))
                    ->badge()
                    ->color(fn ($state) => LeaveRequestStatus::getColor($state->value))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        '' => 'All',
                        LeaveRequestStatus::PENDING => 'Pending',
                        LeaveRequestStatus::PENDING_SUPERVISOR_APPROVAL => 'Pending Supervisor',
                        LeaveRequestStatus::PENDING_CLIENT_APPROVAL => 'Pending Client Approval',
                        LeaveRequestStatus::PENDING_PROVIDER_APPROVAL => 'Pending Provider Approval',
                        LeaveRequestStatus::APPROVED => 'Approved',
                        LeaveRequestStatus::REJECTED => 'Rejected',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['value'])) {
                            return $query->where('status', $data['value']);
                        }
                        return $query;
                    }),
            ])
            ->searchable()
            ->defaultSort('created_at', 'desc')
            ->paginated([12])
            ->actions([
                Action::make('approve')
                    ->label('موافقة / Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (LeaveRequest $record) use ($company, $isBranchManager) {
                        $this->approveRecord($record, $company, $isBranchManager);
                    })
                    ->visible(function (LeaveRequest $record) use ($isBranchManager) {
                        $status = $record->status->value;
                        if ($isBranchManager && $status === LeaveRequestStatus::PENDING_SUPERVISOR_APPROVAL) {
                            return true;
                        }
                        return in_array($status, [
                            LeaveRequestStatus::PENDING,
                            LeaveRequestStatus::PENDING_CLIENT_APPROVAL,
                            LeaveRequestStatus::PENDING_PROVIDER_APPROVAL,
                        ]);
                    }),
                Action::make('reject')
                    ->label('رفض / Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (LeaveRequest $record) {
                        $record->update([
                            'status' => LeaveRequestStatus::REJECTED,
                            'current_approver_company_id' => null,
                        ]);
                        Notification::make()->title('تم رفض الطلب / Request rejected')->success()->send();
                    })
                    ->visible(function (LeaveRequest $record) {
                        $status = $record->status->value;
                        return in_array($status, [
                            LeaveRequestStatus::PENDING,
                            LeaveRequestStatus::PENDING_SUPERVISOR_APPROVAL,
                            LeaveRequestStatus::PENDING_CLIENT_APPROVAL,
                            LeaveRequestStatus::PENDING_PROVIDER_APPROVAL,
                        ]);
                    }),
            ])
            ->bulkActions([
                BulkAction::make('accept')
                    ->label('Accept All')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function ($records) use ($company, $isBranchManager) {
                        $count = 0;
                        
                        foreach ($records as $record) {
                            if ($this->approveRecord($record, $company, $isBranchManager, false)) {
                                $count++;
                            }
                        }
                        
                        Notification::make()
                            ->title($count > 0 ? "{$count} request(s) approved successfully" : 'No pending requests to approve')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('reject')
                    ->label('Reject All')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(function ($records) {
                        $count = 0;
                        foreach ($records as $record) {
                            $statusValue = $record->status->value;
                            if (in_array($statusValue, [
                                LeaveRequestStatus::PENDING,
                                LeaveRequestStatus::PENDING_SUPERVISOR_APPROVAL,
                                LeaveRequestStatus::PENDING_CLIENT_APPROVAL,
                                LeaveRequestStatus::PENDING_PROVIDER_APPROVAL,
                            ])) {
                                $record->update([
                                    'status' => LeaveRequestStatus::REJECTED,
                                    'current_approver_company_id' => null,
                                ]);
                                $count++;
                            }
                        }
                        
                        Notification::make()
                            ->title($count > 0 ? "{$count} leave request(s) rejected" : 'No pending requests to reject')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    /**
     * Approve a single record through the 3-level flow.
     */
    protected function approveRecord(LeaveRequest $record, $company, bool $isBranchManager, bool $notify = true): bool
    {
        $status = $record->status->value;

        // Level 1: Supervisor (branch manager) approves → move to client HR
        if ($status === LeaveRequestStatus::PENDING_SUPERVISOR_APPROVAL && $isBranchManager) {
            $record->moveToClientApproval();
            if ($notify) {
                Notification::make()->title('تمت الموافقة — أُحيل لـ HR المستأجر / Approved — forwarded to client HR')->success()->send();
            }
            return true;
        }

        // Backward compat: old PENDING status
        if ($status === LeaveRequestStatus::PENDING) {
            $isClientCompany = $record->employee->company_assigned_id === $company->id;
            if ($isClientCompany) {
                $record->update(['current_approver_company_id' => $company->id]);
                $record->moveToProviderApproval();
            } else {
                $record->finalizeApproval();
            }
            if ($notify) {
                Notification::make()->title('تمت الموافقة / Approved')->success()->send();
            }
            return true;
        }

        // Level 2: Client HR approves → move to provider HR
        if ($status === LeaveRequestStatus::PENDING_CLIENT_APPROVAL) {
            $record->moveToProviderApproval();
            if ($notify) {
                Notification::make()->title('تمت الموافقة — أُحيل لـ HR المؤجر / Approved — forwarded to provider HR')->success()->send();
            }
            return true;
        }

        // Level 3: Provider HR approves → finalize (deducts balance for annual leave)
        if ($status === LeaveRequestStatus::PENDING_PROVIDER_APPROVAL) {
            $record->finalizeApproval();
            if ($notify) {
                Notification::make()->title('اعتماد نهائي ✅ — تم تحديث الرصيد / Final approval — balance updated')->success()->send();
            }
            return true;
        }

        return false;
    }

    protected function getQuery($company, $authUser = null, bool $isBranchManager = false): Builder
    {
        return LeaveRequest::query()
            ->with(['employee'])
            ->where(function (Builder $query) use ($company, $authUser, $isBranchManager) {
                // Branch managers see PENDING_SUPERVISOR_APPROVAL for employees in their branches
                if ($isBranchManager && $authUser instanceof User) {
                    $branchIds = $authUser->managedBranches()->pluck('id')->toArray();
                    $query->orWhere(function (Builder $q) use ($branchIds) {
                        $q->where('status', LeaveRequestStatus::PENDING_SUPERVISOR_APPROVAL)
                            ->whereHas('employee', function (Builder $empQ) use ($branchIds) {
                                $empQ->whereHas('branches', function (Builder $bQ) use ($branchIds) {
                                    $bQ->whereIn('branches.id', $branchIds)
                                        ->where('branch_employee.is_active', true);
                                });
                            });
                    });
                }

                // Company sees requests where it is the current approver
                $query->orWhere('current_approver_company_id', $company->id);

                // Backward compatibility: old PENDING requests
                $query->orWhere(function (Builder $q) use ($company) {
                    $q->where('status', LeaveRequestStatus::PENDING)
                        ->where(function (Builder $subQuery) use ($company) {
                            if ($company->type === CompanyTypes::PROVIDER) {
                                $subQuery->where('company_id', $company->id);
                            } else {
                                $subQuery->whereHas('employee', function (Builder $empQuery) use ($company) {
                                    $empQuery->where('company_assigned_id', $company->id);
                                });
                            }
                        });
                });

                // Also show approved/rejected for history
                $query->orWhere(function (Builder $q) use ($company) {
                    $q->whereIn('status', [LeaveRequestStatus::APPROVED, LeaveRequestStatus::REJECTED])
                        ->where(function (Builder $sub) use ($company) {
                            $sub->where('company_id', $company->id)
                                ->orWhereHas('employee', function (Builder $empQ) use ($company) {
                                    $empQ->where('company_assigned_id', $company->id);
                                });
                        });
                });
            });
    }
}

