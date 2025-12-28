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
        return Filament::auth()->check();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function table(Table $table): Table
    {
        $company = Filament::auth()->user();
        
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
                    ->action(function ($records) {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record->status->value === LeaveRequestStatus::PENDING) {
                                $record->update(['status' => LeaveRequestStatus::APPROVED]);
                                $count++;
                            }
                        }
                        
                        Notification::make()
                            ->title($count > 0 ? "{$count} leave request(s) approved successfully" : 'No pending requests to approve')
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
                            if ($record->status->value === LeaveRequestStatus::PENDING) {
                                $record->update(['status' => LeaveRequestStatus::REJECTED]);
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
                if ($company->type === CompanyTypes::PROVIDER) {
                    // PROVIDER companies see requests for their original employees
                    $query->where('company_id', $company->id);
                } else {
                    // CLIENT companies see requests for employees assigned to them
                    $query->whereHas('employee', function (Builder $q) use ($company) {
                        $q->where('company_assigned_id', $company->id);
                    });
                }
            });
    }
}

