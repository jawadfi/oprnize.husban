<?php

namespace App\Filament\Company\Pages;

use App\Enums\CompanyTypes;
use App\Enums\EmployeeAssignedStatus;
use App\Enums\EmployeeStatusStatus;
use App\Filament\Company\Pages\ProviderCompaniesListing;
use App\Filament\Schema\EmployeeSchema;
use App\Models\Company;
use App\Models\Employee;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Actions\Action as PageAction;
use Filament\Pages\Page;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ProviderCompanyEmployees extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static string $view = 'filament.company.pages.provider-company-employees';

    protected static ?string $slug = 'provider-company-employees';

    public ?Company $company = null;

    #[Url]
    public ?int $companyId = null;

    public function mount(): void
    {
        abort_unless($this->canAccess(), 403);
        
        $companyId = request()->query('companyId');
        
        if ($companyId) {
            $this->companyId = (int) $companyId;
            $this->company = Company::where('type', CompanyTypes::PROVIDER)
                ->findOrFail($this->companyId);
        } else {
            abort(404);
        }
    }

    public static function canAccess(): bool
    {
        return Filament::auth()->check() && 
               Filament::auth()->user()->type === CompanyTypes::CLIENT;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Employee::query()
                    ->where('company_id', $this->company?->id ?? $this->companyId)
                    ->withPayrollData() // Only show employees with complete payroll data
                    ->with(['currentCompanyAssigned'])
            )
            ->columns([
                ...EmployeeSchema::getTableColumns(
                    false,
                    ''
                ),
            ])
            ->defaultSort('id', 'asc')
            ->paginated([10, 25, 50])
            ->bulkActions([
                BulkAction::make('assign')
                    ->label('Assign Employees')
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->required()
                            ->default(now()),
                    ])
                    ->action(function ($records, array $data) {
                        /** @var \App\Models\Company $clientCompany */
                        $clientCompany = Filament::auth()->user();
                        
                        // Filter out employees already assigned to this client company
                        $employeeIds = $records->pluck('id')->toArray();
                        $existingAssignments = \App\Models\EmployeeAssigned::whereIn('employee_id', $employeeIds)
                            ->where('company_id', $clientCompany->id)
                            ->pluck('employee_id')
                            ->toArray();
                        
                        $newEmployeeIds = array_diff($employeeIds, $existingAssignments);
                        
                        if (!empty($newEmployeeIds)) {
                            // Get the employee models for the new assignments
                            $newEmployees = Employee::whereIn('id', $newEmployeeIds)->get();
                            
                            // Attach employees to client company with status PENDING
                            $clientCompany->used_employees()->attach($newEmployees, [
                                'start_date' => $data['start_date'],
                                'status' => EmployeeAssignedStatus::PENDING,
                            ]);
                            
                            // Update employee's company_assigned_id
                            Employee::whereIn('id', $newEmployeeIds)
                                ->update(['company_assigned_id' => $clientCompany->id]);
                        }
                        
                        Notification::make()
                            ->title('Employees assigned successfully. Waiting for approval.')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('back')
                ->label('back')
                ->icon('heroicon-o-arrow-right')
                ->url(ProviderCompaniesListing::getUrl())
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return 'Employees of ' . ($this->company?->name ?? '');
    }
}

