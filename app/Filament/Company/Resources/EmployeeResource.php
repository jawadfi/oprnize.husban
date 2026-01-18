<?php

namespace App\Filament\Company\Resources;

use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use App\Enums\CompanyTypes;
use App\Enums\EmployeeStatusStatus;
use App\Filament\Company\Imports\EmployeeImporter;
use App\Filament\Company\Resources\EmployeeResource\Pages;
use App\Filament\Company\Resources\EmployeeResource\RelationManagers;
use App\Filament\Imports\TeacherImporter;
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
use Filament\Tables\Table;
use HayderHatem\FilamentExcelImport\Actions\FullImportAction;
use Illuminate\Database\Eloquent\Builder;

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
        $companyId = $user instanceof Company ? $user->id : ($user instanceof \App\Models\User ? $user->company_id : null);
        
        return parent::getEloquentQuery()->where('company_id', $companyId)->with(['currentCompanyAssigned']);
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
            ->columns(EmployeeSchema::getTableColumns())
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
                        $company->used_employees()->attach($records,['start_date'=>$data['start_date']]);
                        Employee::whereIn('id',$records->pluck('id')->toArray())->update(['company_assigned_id'=>$company->id]);
                        Notification::make()
                            ->title('Employees assigned successfully')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
