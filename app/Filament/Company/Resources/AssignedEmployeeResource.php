<?php

namespace App\Filament\Company\Resources;

use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use App\Enums\CompanyTypes;
use App\Enums\EmployeeAssignedStatus;
use App\Filament\Company\Resources\AssignedEmployeeResource\Pages;
use App\Filament\Schema\EmployeeSchema;
use App\Models\Employee;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssignedEmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $label = 'Assigned Employees';
    
    protected static ?string $navigationLabel = 'Assigned Employees';

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
        
        return parent::getEloquentQuery()->whereHas('assigned',function ($query) use ($companyId) {
            return $query
                ->where('employee_assigned.company_id', $companyId)
                ->where('employee_assigned.status',EmployeeAssignedStatus::APPROVED)
                ->whereDate('employee_assigned.start_date','<=',now());
        });
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
            ->columns(EmployeeSchema::getTableColumns())
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAssignedEmployees::route('/'),
        ];
    }
}
