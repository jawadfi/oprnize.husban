<?php

namespace App\Filament\Company\Pages;

use App\Enums\CompanyTypes;
use App\Enums\EmployeeAssignedStatus;
use App\Filament\Schema\EmployeeSchema;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeAssigned;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Actions\Action as PageAction;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ClientCompanyEmployees extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static string $view = 'filament.company.pages.client-company-employees';

    protected static ?string $slug = 'client-company-employees';

    public ?Company $company = null;

    #[Url]
    public ?int $companyId = null;

    public function mount(): void
    {
        abort_unless($this->canAccess(), 403);

        $companyId = request()->query('companyId');

        if ($companyId) {
            $this->companyId = (int) $companyId;
            $this->company = Company::where('type', CompanyTypes::CLIENT)
                ->findOrFail($this->companyId);
        } else {
            abort(404);
        }
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        if ($user instanceof Company) {
            return $user->type === CompanyTypes::PROVIDER;
        }

        if ($user instanceof User) {
            return $user->company?->type === CompanyTypes::PROVIDER;
        }

        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        $user = Filament::auth()->user();
        $providerId = $user instanceof Company ? $user->id : ($user instanceof User ? $user->company_id : null);

        return $table
            ->query(
                Employee::query()
                    ->where('company_id', $providerId)
                    ->with(['currentCompanyAssigned'])
            )
            ->columns([
                ...EmployeeSchema::getTableColumns(false, ''),
                TextColumn::make('assignment_status')
                    ->label('حالة التعيين / Assignment')
                    ->getStateUsing(function (Employee $record) {
                        $assignment = EmployeeAssigned::where('employee_id', $record->id)
                            ->where('company_id', $this->companyId)
                            ->first();

                        if (!$assignment) {
                            return 'غير معين';
                        }

                        return EmployeeAssignedStatus::getKey($assignment->status);
                    })
                    ->badge()
                    ->color(function (Employee $record) {
                        $assignment = EmployeeAssigned::where('employee_id', $record->id)
                            ->where('company_id', $this->companyId)
                            ->first();

                        if (!$assignment) {
                            return 'gray';
                        }

                        return EmployeeAssignedStatus::getColor($assignment->status);
                    }),
            ])
            ->actions([
                Action::make('assign')
                    ->label('تعيين للعميل / Assign')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ البدء / Start Date')
                            ->required()
                            ->default(now()),
                    ])
                    ->action(function (Employee $record, array $data) {
                        $clientCompany = Company::findOrFail($this->companyId);

                        // Check if already assigned to this client
                        $existing = EmployeeAssigned::where('employee_id', $record->id)
                            ->where('company_id', $clientCompany->id)
                            ->first();

                        if ($existing) {
                            Notification::make()
                                ->title('هذا الموظف مُعين مسبقاً لهذا العميل')
                                ->warning()
                                ->send();
                            return;
                        }

                        // Attach employee to client company with APPROVED status (provider assigns directly)
                        $clientCompany->used_employees()->attach($record->id, [
                            'start_date' => $data['start_date'],
                            'status' => EmployeeAssignedStatus::APPROVED,
                        ]);

                        // Update employee's company_assigned_id
                        $record->update(['company_assigned_id' => $clientCompany->id]);

                        Notification::make()
                            ->title('تم تعيين الموظف بنجاح')
                            ->body('Employee assigned to ' . $clientCompany->name)
                            ->success()
                            ->send();
                    })
                    ->visible(function (Employee $record) {
                        // Only show for employees not already assigned to this client
                        $existing = EmployeeAssigned::where('employee_id', $record->id)
                            ->where('company_id', $this->companyId)
                            ->first();
                        return !$existing;
                    }),

                Action::make('unassign')
                    ->label('إلغاء التعيين / Unassign')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('إلغاء تعيين الموظف')
                    ->modalDescription('هل أنت متأكد من إلغاء تعيين هذا الموظف من هذا العميل؟')
                    ->action(function (Employee $record) {
                        // Remove from employee_assigned pivot
                        EmployeeAssigned::where('employee_id', $record->id)
                            ->where('company_id', $this->companyId)
                            ->delete();

                        // Clear company_assigned_id only if it matches this client
                        if ($record->company_assigned_id == $this->companyId) {
                            $record->update(['company_assigned_id' => null]);
                        }

                        Notification::make()
                            ->title('تم إلغاء تعيين الموظف')
                            ->success()
                            ->send();
                    })
                    ->visible(function (Employee $record) {
                        return EmployeeAssigned::where('employee_id', $record->id)
                            ->where('company_id', $this->companyId)
                            ->exists();
                    }),
            ])
            ->defaultSort('id', 'asc')
            ->paginated([10, 25, 50])
            ->bulkActions([
                BulkAction::make('bulk_assign')
                    ->label('تعيين المحدد / Assign Selected')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ البدء / Start Date')
                            ->required()
                            ->default(now()),
                    ])
                    ->action(function ($records, array $data) {
                        $clientCompany = Company::findOrFail($this->companyId);

                        $employeeIds = $records->pluck('id')->toArray();

                        // Filter out already assigned
                        $existingIds = EmployeeAssigned::whereIn('employee_id', $employeeIds)
                            ->where('company_id', $clientCompany->id)
                            ->pluck('employee_id')
                            ->toArray();

                        $newIds = array_diff($employeeIds, $existingIds);

                        if (!empty($newIds)) {
                            foreach ($newIds as $id) {
                                $clientCompany->used_employees()->attach($id, [
                                    'start_date' => $data['start_date'],
                                    'status' => EmployeeAssignedStatus::APPROVED,
                                ]);
                            }

                            Employee::whereIn('id', $newIds)
                                ->update(['company_assigned_id' => $clientCompany->id]);
                        }

                        $count = count($newIds);
                        Notification::make()
                            ->title("تم تعيين {$count} موظف بنجاح")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('bulk_unassign')
                    ->label('إلغاء تعيين المحدد / Unassign Selected')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $count = 0;
                        foreach ($records as $record) {
                            $deleted = EmployeeAssigned::where('employee_id', $record->id)
                                ->where('company_id', $this->companyId)
                                ->delete();

                            if ($deleted && $record->company_assigned_id == $this->companyId) {
                                $record->update(['company_assigned_id' => null]);
                                $count++;
                            }
                        }

                        Notification::make()
                            ->title("تم إلغاء تعيين {$count} موظف")
                            ->warning()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('back')
                ->label('رجوع / Back')
                ->icon('heroicon-o-arrow-right')
                ->url(ClientCompaniesListing::getUrl())
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return 'موظفين ' . ($this->company?->name ?? '') . ' / Employees for ' . ($this->company?->name ?? '');
    }
}
