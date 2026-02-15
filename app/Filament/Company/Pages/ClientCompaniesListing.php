<?php

namespace App\Filament\Company\Pages;

use App\Enums\CompanyTypes;
use App\Enums\EmployeeAssignedStatus;
use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class ClientCompaniesListing extends Page
{
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static string $view = 'filament.company.pages.client-companies-listing';

    protected static ?string $navigationLabel = 'العملاء / Clients';

    protected static ?string $title = 'شركات العملاء / Client Companies';

    protected static ?int $navigationSort = 1;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        abort_unless($this->canAccess(), 403);
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
        return static::canAccess();
    }

    public function getCompaniesProperty(): LengthAwarePaginator
    {
        $user = Filament::auth()->user();
        $providerId = $user instanceof Company ? $user->id : ($user instanceof User ? $user->company_id : null);

        $query = Company::query()
            ->where('type', CompanyTypes::CLIENT)
            ->withCount([
                'used_employees as assigned_employees_count' => function ($q) use ($providerId) {
                    $q->where('employees.company_id', $providerId)
                      ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED);
                },
                'used_employees as pending_employees_count' => function ($q) use ($providerId) {
                    $q->where('employees.company_id', $providerId)
                      ->where('employee_assigned.status', EmployeeAssignedStatus::PENDING);
                },
            ])
            ->orderBy('name');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return $query->paginate(12, pageName: 'page');
    }

    public function updatedSearch(): void
    {
        $this->resetPage('page');
    }
}
