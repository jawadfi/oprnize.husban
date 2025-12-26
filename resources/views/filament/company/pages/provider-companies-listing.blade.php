<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mt-1">Select the company to show salaries</p>
            </div>
            <div class="text-sm text-gray-600">
                Total: {{ $this->companies->total() }} company
            </div>
        </div>

        {{-- Search Bar --}}
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search"
                placeholder="Search by (company name)"
                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            />
        </div>

        {{-- Companies Grid --}}
        @if($this->companies->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($this->companies as $company)
                    <a 
                        href="{{ \App\Filament\Company\Pages\ProviderCompanyEmployees::getUrl(['companyId' => $company->id]) }}"
                        class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200"
                    >
                        <div class="flex items-start justify-between mb-3">
                            {{-- Company Logo Placeholder --}}
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-xl">
                                {{ strtoupper(substr($company->name, 0, 1)) }}
                            </div>
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">{{ $company->name }}</h3>
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>{{ $company->original_employees_count ?? 0 }} Employee</span>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="flex items-center justify-between border-t border-gray-200 pt-4">
                <div class="text-sm text-gray-700">
                    Showing {{ $this->companies->firstItem() ?? 0 }}-{{ $this->companies->lastItem() ?? 0 }} from {{ $this->companies->total() }} Entries
                </div>
                <div class="flex items-center space-x-2">
                    {{ $this->companies->links() }}
                </div>
                <div class="text-sm text-gray-600">
                    12 Results per page
                </div>
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-gray-500">No companies found.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>

