<?php

namespace App\Filament\Company\Resources\BranchResource\Pages;

use App\Enums\EmployeeAssignedStatus;
use App\Filament\Company\Resources\BranchResource;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManageBranchEmployees extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = BranchResource::class;

    protected static string $view = 'filament.company.resources.branch-resource.pages.manage-branch-employees';

    public Branch $record;

    public function getTitle(): string
    {
        return 'إدارة موظفي الفرع: ' . $this->record->name;
    }

    public function getBreadcrumb(): ?string
    {
        return 'إدارة الموظفين';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('assignEmployee')
                ->label('إضافة موظف للفرع / Assign Employee')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->form([
                    Forms\Components\Select::make('employee_id')
                        ->label('الموظف / Employee')
                        ->options(function () {
                            return $this->getAssignableEmployees();
                        })
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\DatePicker::make('start_date')
                        ->label('تاريخ البداية / Start Date')
                        ->required()
                        ->default(now()),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('تاريخ النهاية / End Date')
                        ->nullable()
                        ->after('start_date'),
                ])
                ->action(function (array $data) {
                    // Check if employee is already assigned to this branch
                    $exists = $this->record->employees()
                        ->where('employee_id', $data['employee_id'])
                        ->wherePivot('is_active', true)
                        ->exists();

                    if ($exists) {
                        Notification::make()
                            ->title('الموظف مضاف مسبقاً لهذا الفرع')
                            ->danger()
                            ->send();
                        return;
                    }

                    $this->record->employees()->attach($data['employee_id'], [
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                        'is_active' => true,
                    ]);

                    Notification::make()
                        ->title('تم إضافة الموظف للفرع بنجاح')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('back')
                ->label('العودة / Back')
                ->icon('heroicon-o-arrow-left')
                ->url(BranchResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    /**
     * Get employees assigned to this CLIENT company that are not yet in this branch
     */
    protected function getAssignableEmployees(): array
    {
        $user = Filament::auth()->user();
        $companyId = $user instanceof Company
            ? $user->id
            : ($user instanceof User ? $user->company_id : null);

        if (!$companyId) {
            return [];
        }

        // Get employees assigned to this CLIENT company (approved)
        $alreadyInBranch = $this->record->employees()
            ->wherePivot('is_active', true)
            ->pluck('employees.id')
            ->toArray();

        return Employee::where('company_assigned_id', $companyId)
            ->whereNotIn('id', $alreadyInBranch)
            ->get()
            ->mapWithKeys(function ($employee) {
                return [$employee->id => $employee->name . ' - ' . ($employee->identity_number ?? '')];
            })
            ->toArray();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Employee::query()
                    ->whereHas('branches', function (Builder $query) {
                        $query->where('branch_id', $this->record->id)
                            ->where('branch_employee.is_active', true);
                    })
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم / Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('identity_number')
                    ->label('رقم الهوية / ID')
                    ->searchable(),

                Tables\Columns\TextColumn::make('branches_pivot_start_date')
                    ->label('تاريخ البداية / Start')
                    ->getStateUsing(function (Employee $record) {
                        $pivot = $record->branches()
                            ->where('branch_id', $this->record->id)
                            ->wherePivot('is_active', true)
                            ->first()?->pivot;
                        return $pivot?->start_date;
                    })
                    ->date(),

                Tables\Columns\TextColumn::make('branches_pivot_end_date')
                    ->label('تاريخ النهاية / End')
                    ->getStateUsing(function (Employee $record) {
                        $pivot = $record->branches()
                            ->where('branch_id', $this->record->id)
                            ->wherePivot('is_active', true)
                            ->first()?->pivot;
                        return $pivot?->end_date;
                    })
                    ->date()
                    ->placeholder('مفتوح / Open'),
            ])
            ->actions([
                Tables\Actions\Action::make('transfer')
                    ->label('نقل / Transfer')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('new_branch_id')
                            ->label('الفرع الجديد / New Branch')
                            ->options(function () {
                                $user = Filament::auth()->user();
                                $companyId = $user instanceof Company
                                    ? $user->id
                                    : ($user instanceof User ? $user->company_id : null);

                                return Branch::where('company_id', $companyId)
                                    ->where('id', '!=', $this->record->id)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->required(),

                        Forms\Components\DatePicker::make('transfer_date')
                            ->label('تاريخ النقل / Transfer Date')
                            ->required()
                            ->default(now()),
                    ])
                    ->action(function (Employee $record, array $data) {
                        // End current branch assignment
                        $record->branches()->updateExistingPivot($this->record->id, [
                            'end_date' => $data['transfer_date'],
                            'is_active' => false,
                        ]);

                        // Assign to new branch
                        $record->branches()->attach($data['new_branch_id'], [
                            'start_date' => $data['transfer_date'],
                            'is_active' => true,
                        ]);

                        Notification::make()
                            ->title('تم نقل الموظف بنجاح')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('remove')
                    ->label('إزالة / Remove')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('إزالة الموظف من الفرع')
                    ->modalDescription('هل أنت متأكد من إزالة هذا الموظف من الفرع؟')
                    ->action(function (Employee $record) {
                        $record->branches()->updateExistingPivot($this->record->id, [
                            'end_date' => now(),
                            'is_active' => false,
                        ]);

                        Notification::make()
                            ->title('تم إزالة الموظف من الفرع')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('لا يوجد موظفين في هذا الفرع')
            ->emptyStateDescription('أضف موظفين باستخدام زر "إضافة موظف للفرع" أعلاه')
            ->emptyStateIcon('heroicon-o-user-group');
    }
}
