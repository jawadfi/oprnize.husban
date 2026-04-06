<?php

namespace App\Filament\Employee\Pages;

use App\Enums\EndOfServiceRequestStatus;
use App\Enums\TerminationReason;
use App\Models\EndOfServiceRequest;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class MyEndOfServiceRequests extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string $view = 'filament.employee.pages.my-end-of-service-requests';
    protected static ?string $navigationLabel = 'My End of Service Requests';
    protected static ?int $navigationSort = 5;

    public function table(Table $table): Table
    {
        $employee = Filament::auth()->user();

        return $table
            ->query(EndOfServiceRequest::query()->where('employee_id', $employee->id))
            ->columns([
                TextColumn::make('termination_reason')
                    ->label('Reason')
                    ->formatStateUsing(fn ($state) => TerminationReason::getTranslatedKey($state->value))
                    ->badge()
                    ->color(fn ($state) => TerminationReason::getColor($state->value)),
                TextColumn::make('last_working_date')
                    ->label('Last Working Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('estimated_amount')
                    ->label('Estimated Benefit')
                    ->money('SAR')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => EndOfServiceRequestStatus::getTranslatedKey($state->value))
                    ->badge()
                    ->color(fn ($state) => EndOfServiceRequestStatus::getColor($state->value))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->notes)
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->defaultSort('created_at', 'desc');
    }

    public function getTitle(): string | Htmlable
    {
        return 'My End of Service Requests';
    }

    public function getHeading(): string | Htmlable
    {
        return 'My End of Service Requests';
    }
}
