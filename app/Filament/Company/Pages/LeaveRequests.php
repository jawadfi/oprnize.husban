<?php

namespace App\Filament\Company\Pages;

use App\Enums\CompanyTypes;
use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveType;
use App\Models\LeaveRequest;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
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
        
        // User model needs permission
        if ($user instanceof \App\Models\User) {
            return $user->hasPermissionTo('page_LeaveRequests', 'company');
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
        
        // Ensure company relationship is loaded for User model
        if ($authUser instanceof \App\Models\User) {
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
            ->query(function () use ($company) {
                return $this->getQuery($company);
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
            ->bulkActions([
                BulkAction::make('accept')
                    ->label('Accept')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function ($records) use ($company) {
                        $count = 0;
                        $movedToProvider = 0;
                        $finalized = 0;
                        
                        foreach ($records as $record) {
                            // Handle old PENDING status (backward compatibility)
                            if ($record->status->value === LeaveRequestStatus::PENDING) {
                                // Migrate old PENDING to new workflow
                                $isClientCompany = $record->employee->company_assigned_id === $company->id;
                                
                                if ($isClientCompany) {
                                    // Client company - set as current approver and move to provider
                                    $record->update([
                                        'current_approver_company_id' => $company->id,
                                    ]);
                                    $record->moveToProviderApproval();
                                    $movedToProvider++;
                                    $count++;
                                } else {
                                    // Provider company - finalize directly (old workflow)
                                    $record->update([
                                        'status' => LeaveRequestStatus::APPROVED,
                                        'current_approver_company_id' => null,
                                    ]);
                                    $finalized++;
                                    $count++;
                                }
                            } elseif ($record->isPendingClientApproval()) {
                                // Client company approving - move to provider
                                $record->moveToProviderApproval();
                                $movedToProvider++;
                                $count++;
                            } elseif ($record->isPendingProviderApproval()) {
                                // Provider company approving - finalize
                                $record->finalizeApproval();
                                $finalized++;
                                $count++;
                            }
                        }
                        
                        $message = '';
                        if ($movedToProvider > 0 && $finalized > 0) {
                            $message = "{$movedToProvider} request(s) moved to provider approval, {$finalized} request(s) finalized";
                        } elseif ($movedToProvider > 0) {
                            $message = "{$movedToProvider} request(s) moved to provider approval";
                        } elseif ($finalized > 0) {
                            $message = "{$finalized} request(s) approved successfully";
                        } else {
                            $message = 'No pending requests to approve';
                        }
                        
                        Notification::make()
                            ->title($count > 0 ? $message : 'No pending requests to approve')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(function ($records) {
                        $count = 0;
                        foreach ($records as $record) {
                            // Can reject if pending (old status), pending client, or provider approval
                            $statusValue = $record->status->value;
                            if ($statusValue === LeaveRequestStatus::PENDING 
                                || $record->isPendingClientApproval() 
                                || $record->isPendingProviderApproval()) {
                                $record->update([
                                    'status' => LeaveRequestStatus::REJECTED,
                                    'current_approver_company_id' => null,
                                ]);
                                $count++;
                            }
                        }
                        
                        Notification::make()
                            ->title($count > 0 ? "{$count} leave request(s) rejected successfully" : 'No pending requests to reject')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    protected function getQuery($company): Builder
    {
        return LeaveRequest::query()
            ->with(['employee'])
            ->where(function (Builder $query) use ($company) {
                // New workflow: requests where this company is the current approver
                $query->where('current_approver_company_id', $company->id)
                    // OR backward compatibility: old PENDING requests
                    ->orWhere(function (Builder $q) use ($company) {
                        $q->where('status', LeaveRequestStatus::PENDING)
                            ->where(function (Builder $subQuery) use ($company) {
                if ($company->type === CompanyTypes::PROVIDER) {
                                    // Provider companies see old pending requests for their employees
                                    $subQuery->where('company_id', $company->id);
                } else {
                                    // Client companies see old pending requests for employees assigned to them
                                    $subQuery->whereHas('employee', function (Builder $empQuery) use ($company) {
                                        $empQuery->where('company_assigned_id', $company->id);
                    });
                }
                            });
                    });
            });
    }
}

