<?php

namespace App\Filament\Company\Resources;

use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use App\Enums\CompanyTypes;
use App\Enums\EmployeeStatusStatus;
use App\Filament\Company\Imports\EmployeeImporter;
use App\Filament\Company\Resources\EmployeeResource\Pages;
use App\Filament\Company\Resources\EmployeeResource\RelationManagers;
use App\Filament\Schema\EmployeeSchema;
use App\Helpers\Helpers;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeResource extends Resource
{
    protected $activeTab;
    protected static ?string $model = Employee::class;

    protected static ?string $label = 'Employees';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        
        // Company model - check type
        if ($user instanceof Company) {
            return $user->type === CompanyTypes::PROVIDER;
        }
        
        // User model - check permission
        // Filament Shield generates permissions based on model name, not Resource name
        if ($user instanceof User) {
            return $user->can('view_any_employee');
        }
        
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();

        $companyId = match (true) {
            $user instanceof Company            => $user->id,
            $user instanceof \App\Models\User   => $user->company_id,
            default                             => null,
        };

        $query = parent::getEloquentQuery()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->with(['currentCompanyAssigned']);

        // Guard: if the company cannot be determined, return an empty result set
        // rather than leaking rows (e.g. WHERE company_id IS NULL matching all
        // employees whose company_id was never set).
        if (! $companyId) {
            return $query->whereRaw('0 = 1');
        }

        // Qualify the column to avoid ambiguity when joins are present.
        return $query->where('employees.company_id', $companyId);
    }

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(
                    EmployeeSchema::getFormComponents()
            );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                FilamentExportHeaderAction::make('export'),
            ])
            ->columns([
                ...EmployeeSchema::getTableColumns(),
                Tables\Columns\IconColumn::make('has_payroll')
                    ->label('Payroll Data')
                    ->state(fn(Employee $record) => $record->hasPayrollData())
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->trueColor('success')
                    ->falseIcon('heroicon-o-x-circle')
                    ->falseColor('danger')
                    ->alignCenter()
                    ->width('100px'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('assigned_to')
                    ->visible(fn($livewire) => $livewire->activeTab===EmployeeStatusStatus::AVAILABLE)
                    ->deselectRecordsAfterCompletion()
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Forms\Components\Select::make('company_id')
                            ->label('Assign to Company')
                            ->placeholder('Search by (Name, CR number)')
                            ->options(function () {
                                $user = Filament::auth()->user();
                                $companyId = $user instanceof \App\Models\Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);
                                return Company::query()
                                    ->whereKeyNot($companyId)
                                    ->ofType(CompanyTypes::CLIENT)
                                    ->pluck('name', 'id');
                            })
                            ->searchable(['name','commercial_registration_number'])
                            ->preload()
                            ->required(),
                        Forms\Components\DatePicker::make('start_date')->label('Start Date')->required(),
                    ])->action(function ($records, array $data){
                        /** @var Company $company */
                        $company = Company::find($data['company_id']);
                        $company->used_employees()->attach($records, [
                            'start_date' => $data['start_date'],
                            'status' => \App\Enums\EmployeeAssignedStatus::PENDING,
                        ]);
                        Notification::make()
                            ->title('تم إرسال طلب التعيين / Assignment request sent — awaiting client approval')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
