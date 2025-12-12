<?php

namespace App\Filament\Company\Resources;

use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use App\Enums\CompanyTypes;
use App\Enums\EmployeeStatusStatus;
use App\Filament\Company\Resources\EmployeeResource\Pages;
use App\Filament\Company\Resources\EmployeeResource\RelationManagers;
use App\Helpers\Helpers;
use App\Models\Company;
use App\Models\Employee;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeResource extends Resource
{
    protected $activeTab;
    protected static ?string $model = Employee::class;

    public static function canAccess(): bool
    {
        return Filament::auth()->user()->type === CompanyTypes::PROVIDER;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id',Filament::auth()->id())->with(['currentCompanyAssigned']);
    }

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make([
                    Forms\Components\TextInput::make('name')->label('Employee Name')->required(),
                    Forms\Components\TextInput::make('job_title')->required(),
                    Forms\Components\TextInput::make('identity_number')
                        ->unique(ignoreRecord:true)
                        ->label('ID Number')->required(),
                    Forms\Components\Select::make('nationality')
                        ->options(Helpers::same_key_value(config('helpers.nationalities')))
                        ->preload()
                        ->searchable()
                        ->required(),
                ]) ->columns(2)
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
                Tables\Columns\TextColumn::make('currentCompanyAssigned.name')
                    ->label('Company Assigned')
                    ->badge()
                    ->visible(fn($livewire) => $livewire->activeTab===EmployeeStatusStatus::IN_SERVICE)
                    ->sortable()
                    ->searchable(),
            ])
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
                            ->options(Company::query()
                                ->whereKeyNot(Filament::auth()->id())
                                ->ofType(CompanyTypes::CLIENT)
                                ->pluck('name', 'id'))
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
