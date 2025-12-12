<?php

namespace App\Filament\Company\Resources;

use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use App\Enums\CompanyTypes;
use App\Filament\Company\Resources\AssignedEmployeeResource\Pages;
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

    public static function canAccess(): bool
    {
        return Filament::auth()->user()->type === CompanyTypes::CLIENT;
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('assigned',function ($query){
            return $query->where('employee_assigned.company_id',Filament::auth()->id());
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
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('Employee ID')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Employee Name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('job_title')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('identity_number')->label('ID Number')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('nationality')->label('Nationality')->sortable()->searchable(),
            ])
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
