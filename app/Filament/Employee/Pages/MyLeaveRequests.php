<?php

namespace App\Filament\Employee\Pages;

use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveType;
use App\Models\LeaveRequest;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class MyLeaveRequests extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.employee.pages.my-leave-requests';

    protected static ?string $navigationLabel = 'My Leave Requests';

    protected static ?int $navigationSort = 2;

    public function getTitle(): string | Htmlable
    {
        return 'My Leave Requests';
    }

    public function getHeading(): string | Htmlable
    {
        return 'My Leave Requests';
    }

    public function table(Table $table): Table
    {
        $employee = Filament::auth()->user();

        return $table
            ->query(
                LeaveRequest::query()->where('employee_id', $employee->id)
            )
            ->columns([
                TextColumn::make('leave_type')
                    ->label('Leave Type')
                    ->formatStateUsing(fn ($state) => LeaveType::getTranslatedKey($state->value))
                    ->badge()
                    ->color(fn ($state) => LeaveType::getColor($state->value))
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('End Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('days_count')
                    ->label('Days')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => LeaveRequestStatus::getTranslatedKey($state->value))
                    ->badge()
                    ->color(fn ($state) => LeaveRequestStatus::getColor($state->value))
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->notes)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime('d/m/Y H:i')
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
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No leave requests found')
            ->emptyStateDescription('You haven\'t submitted any leave requests yet. Use the "Request Leave" page to submit a new request.')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }
}

