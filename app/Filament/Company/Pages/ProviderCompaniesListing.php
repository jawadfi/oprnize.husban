<?php

namespace App\Filament\Company\Pages;

use App\Enums\CompanyTypes;
use App\Models\Company;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class ProviderCompaniesListing extends Page
{
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static string $view = 'filament.company.pages.provider-companies-listing';

    protected static ?string $navigationLabel = 'Companies';

    protected static ?string $title = 'Companies';

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        abort_unless($this->canAccess(), 403);
    }

    public static function canAccess(): bool
    {
        return Filament::auth()->check() && 
               Filament::auth()->user()->type === CompanyTypes::CLIENT;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getCompaniesProperty(): LengthAwarePaginator
    {
        $query = Company::query()
            ->where('type', CompanyTypes::PROVIDER)
            ->withCount('original_employees')
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

