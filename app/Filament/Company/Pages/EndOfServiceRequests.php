<?php

namespace App\Filament\Company\Pages;

use App\Enums\CompanyTypes;
use App\Enums\EndOfServiceRequestStatus;
use App\Enums\TerminationReason;
use App\Models\EndOfServiceRequest;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EndOfServiceRequests extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-minus';
    protected static string $view = 'filament.company.pages.end-of-service-requests';
    protected static ?string $navigationLabel = 'End of Service Requests';
    protected static ?string $title = 'End of Service Requests';
    protected static ?int $navigationSort = 91;
    protected static ?string $navigationGroup = 'الأدوات';

    public function mount(): void
    {
        abort_unless($this->canAccess(), 403);
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        if ($user instanceof \App\Models\Company) {
            return true;
        }

        if ($user instanceof User) {
            if ($user->isBranchManager()) {
                return true;
            }

            try {
                return $user->hasPermissionTo('page_EndOfServiceRequests', 'company');
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

        if ($authUser instanceof User) {
            $authUser->load('company');
            $company = $authUser->company;
        } elseif ($authUser instanceof \App\Models\Company) {
            $company = $authUser;
        } else {
            $company = null;
        }

        if (! $company) {
            abort(403);
        }

        return $table
            ->query(fn () => $this->getQuery($company, $authUser, $isBranchManager))
            ->columns([
                TextColumn::make('employee.name')->label('Employee')->searchable()->sortable(),
                TextColumn::make('employee.emp_id')->label('Emp ID')->searchable()->sortable(),
                TextColumn::make('termination_reason')
                    ->label('Reason')
                    ->formatStateUsing(fn ($state) => TerminationReason::getTranslatedKey($state->value))
                    ->badge()
                    ->color(fn ($state) => TerminationReason::getColor($state->value)),
                TextColumn::make('service_start_date')->label('Service Start')->date('d/m/Y')->sortable(),
                TextColumn::make('last_working_date')->label('Last Working Date')->date('d/m/Y')->sortable(),
                TextColumn::make('salary_amount')->label('Actual Wage')->money('SAR')->sortable(),
                TextColumn::make('estimated_amount')->label('Estimated Benefit')->money('SAR')->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => EndOfServiceRequestStatus::getTranslatedKey($state->value))
                    ->badge()
                    ->color(fn ($state) => EndOfServiceRequestStatus::getColor($state->value))
                    ->sortable(),
                TextColumn::make('notes')->label('Notes')->limit(50)->tooltip(fn ($record) => $record->notes),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        '' => 'All',
                        EndOfServiceRequestStatus::PENDING_SUPERVISOR_APPROVAL => 'Pending Supervisor',
                        EndOfServiceRequestStatus::PENDING_CLIENT_APPROVAL => 'Pending Client Approval',
                        EndOfServiceRequestStatus::PENDING_PROVIDER_APPROVAL => 'Pending Provider Approval',
                        EndOfServiceRequestStatus::APPROVED => 'Approved',
                        EndOfServiceRequestStatus::REJECTED => 'Rejected',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! empty($data['value'])) {
                            return $query->where('status', $data['value']);
                        }

                        return $query;
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (EndOfServiceRequest $record) use ($company, $isBranchManager) {
                        $this->approveRecord($record, $company, $isBranchManager);
                    })
                    ->visible(function (EndOfServiceRequest $record) use ($isBranchManager) {
                        $status = $record->status->value;

                        if ($isBranchManager && $status === EndOfServiceRequestStatus::PENDING_SUPERVISOR_APPROVAL) {
                            return true;
                        }

                        return in_array($status, [
                            EndOfServiceRequestStatus::PENDING_CLIENT_APPROVAL,
                            EndOfServiceRequestStatus::PENDING_PROVIDER_APPROVAL,
                        ], true);
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (EndOfServiceRequest $record) {
                        $record->reject();
                        Notification::make()->title('Request rejected')->success()->send();
                    })
                    ->visible(fn (EndOfServiceRequest $record) => in_array($record->status->value, [
                        EndOfServiceRequestStatus::PENDING_SUPERVISOR_APPROVAL,
                        EndOfServiceRequestStatus::PENDING_CLIENT_APPROVAL,
                        EndOfServiceRequestStatus::PENDING_PROVIDER_APPROVAL,
                    ], true)),
            ]);
    }

    protected function approveRecord(EndOfServiceRequest $record, $company, bool $isBranchManager): void
    {
        $status = $record->status->value;

        if ($status === EndOfServiceRequestStatus::PENDING_SUPERVISOR_APPROVAL && $isBranchManager) {
            $record->moveToClientApproval();
            Notification::make()->title('Approved and forwarded to client HR')->success()->send();
            return;
        }

        if ($status === EndOfServiceRequestStatus::PENDING_CLIENT_APPROVAL) {
            $record->moveToProviderApproval();
            Notification::make()->title('Approved and forwarded to provider HR')->success()->send();
            return;
        }

        if ($status === EndOfServiceRequestStatus::PENDING_PROVIDER_APPROVAL) {
            $record->finalizeApproval();
            Notification::make()->title('Final approval completed')->success()->send();
        }
    }

    protected function getQuery($company, $authUser = null, bool $isBranchManager = false): Builder
    {
        return EndOfServiceRequest::query()
            ->with(['employee'])
            ->where(function (Builder $query) use ($company, $authUser, $isBranchManager) {
                if ($isBranchManager && $authUser instanceof User) {
                    $branchIds = $authUser->managedBranches()->pluck('id')->toArray();
                    $query->orWhere(function (Builder $q) use ($branchIds) {
                        $q->where('status', EndOfServiceRequestStatus::PENDING_SUPERVISOR_APPROVAL)
                            ->whereHas('employee', function (Builder $employeeQuery) use ($branchIds) {
                                $employeeQuery->whereHas('branches', function (Builder $branchQuery) use ($branchIds) {
                                    $branchQuery->whereIn('branches.id', $branchIds)
                                        ->where('branch_employee.is_active', true);
                                });
                            });
                    });
                }

                $query->orWhere('current_approver_company_id', $company->id);

                $query->orWhere(function (Builder $q) use ($company) {
                    $q->whereIn('status', [EndOfServiceRequestStatus::APPROVED, EndOfServiceRequestStatus::REJECTED])
                        ->where(
                            $company->type === CompanyTypes::PROVIDER ? 'provider_company_id' : 'client_company_id',
                            $company->id
                        );
                });
            });
    }
}
