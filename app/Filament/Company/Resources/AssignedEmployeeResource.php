<?php

namespace App\Filament\Company\Resources;

use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use App\Enums\CompanyTypes;
use App\Enums\EmployeeAssignedStatus;
use App\Filament\Company\Resources\AssignedEmployeeResource\Pages;
use App\Filament\Schema\EmployeeSchema;
use App\Models\Employee;
use App\Models\EmployeeAssigned;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssignedEmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $label = 'Employees';

    protected static ?string $navigationLabel = 'Employees';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        
        // Company model - check type
        if ($user instanceof \App\Models\Company) {
            return $user->type === CompanyTypes::CLIENT;
        }
        
        // User model - check permission
        if ($user instanceof \App\Models\User) {
            return $user->can('view_any_AssignedEmployeeResource');
        }
        
        return false;
    }
    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();
        $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);
        
        if (!$companyId) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return empty query
        }
        
        return parent::getEloquentQuery()->whereHas('assigned', function ($query) use ($companyId) {
            return $query
                ->where('employee_assigned.company_id', $companyId)
                ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
                ->whereDate('employee_assigned.start_date', '<=', now());
        })->with(['assigned' => function ($query) use ($companyId) {
            $query->where('employee_assigned.company_id', $companyId)
                  ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
                  ->orderByDesc('employee_assigned.start_date');
        }]);
    }

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                FilamentExportHeaderAction::make('export'),
            ])
            ->columns((function () {
                $user = Filament::auth()->user();
                $clientCompanyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);
                return EmployeeSchema::getTableColumns(true, '', $clientCompanyId);
            })())
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('end_service')
                    ->label('إنهاء الخدمة')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('إنهاء تعيين الموظف')
                    ->modalDescription('سيتم إنهاء الخدمة وإزالة الموظف من حسابك. لن يتم حذفه من النظام.')
                    ->form([
                        DatePicker::make('end_date')
                            ->label('تاريخ نهاية التعيين')
                            ->default(now()->format('Y-m-d'))
                            ->maxDate(now())
                            ->required(),
                    ])
                    ->action(function (Employee $record, array $data) {
                        $user = Filament::auth()->user();
                        $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);

                        EmployeeAssigned::where('employee_id', $record->id)
                            ->where('company_id', $companyId)
                            ->where('status', EmployeeAssignedStatus::APPROVED)
                            ->update([
                                'status'   => EmployeeAssignedStatus::ENDED,
                                'end_date' => $data['end_date'],
                            ]);

                        Notification::make()
                            ->title('تم إنهاء خدمة الموظف / Service ended')
                            ->body($record->name . ' — ' . $data['end_date'])
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_end_service')
                    ->label('إنهاء الخدمة (مجموعة)')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('إنهاء تعيين الموظفين المحددين')
                    ->form([
                        DatePicker::make('end_date')
                            ->label('تاريخ نهاية التعيين')
                            ->default(now()->format('Y-m-d'))
                            ->maxDate(now())
                            ->required(),
                    ])
                    ->action(function ($records, array $data) {
                        $user = Filament::auth()->user();
                        $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);

                        EmployeeAssigned::whereIn('employee_id', $records->pluck('id'))
                            ->where('company_id', $companyId)
                            ->where('status', EmployeeAssignedStatus::APPROVED)
                            ->update([
                                'status'   => EmployeeAssignedStatus::ENDED,
                                'end_date' => $data['end_date'],
                            ]);

                        Notification::make()
                            ->title('تم إنهاء الخدمة لـ ' . $records->count() . ' موظفين')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAssignedEmployees::route('/'),
        ];
    }
}
