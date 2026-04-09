<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <p class="text-sm text-gray-600 mt-1">اختر شركة العميل لعرض وإدارة الموظفين المعينين / Select a client company to manage assigned employees</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-600">الإجمالي: {{ $this->companies->total() }} شركة</span>
                {{-- Download Excel template --}}
                <a
                    href="{{ url('/company/bulk-assign-template') }}"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors"
                    download
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    تحميل نموذج Excel / Download Template
                </a>
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
                placeholder="البحث باسم الشركة / Search by company name"
                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            />
        </div>

        {{-- Companies Grid --}}
        @if($this->companies->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($this->companies as $company)
                    <div class="bg-white rounded-xl p-5 border border-gray-200 hover:border-primary-300 hover:shadow-md transition-all duration-200 flex flex-col gap-3">
                        {{-- Card top row --}}
                        <div class="flex items-start justify-between">
                            <a
                                href="{{ \App\Filament\Company\Pages\ClientCompanyEmployees::getUrl(['companyId' => $company->id]) }}"
                                class="flex items-center gap-3 group flex-1"
                            >
                                <div class="w-12 h-12 bg-gradient-to-br from-emerald-400 to-teal-600 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-sm shrink-0">
                                    {{ mb_substr($company->name, 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-semibold text-gray-900 line-clamp-2 group-hover:text-primary-600 transition-colors">{{ $company->name }}</h3>
                                </div>
                            </a>
                            <svg class="w-5 h-5 text-gray-300 shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>

                        {{-- Stats --}}
                        <div class="space-y-1.5">
                            <div class="flex items-center text-sm text-emerald-600">
                                <svg class="w-4 h-4 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="font-medium">{{ $company->assigned_employees_count ?? 0 }}</span>
                                <span class="text-gray-400 ml-1">موظف معين</span>
                            </div>
                            @if(($company->pending_employees_count ?? 0) > 0)
                                <div class="flex items-center text-sm text-amber-500">
                                    <svg class="w-4 h-4 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="font-medium">{{ $company->pending_employees_count }}</span>
                                    <span class="text-gray-400 ml-1">بانتظار الموافقة</span>
                                </div>
                            @endif
                        </div>

                        {{-- Upload Excel button --}}
                        <button
                            wire:click="mountAction('uploadEmployees', @js(['companyId' => $company->id]))"
                            class="w-full mt-1 inline-flex items-center justify-center gap-2 px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 text-sm font-medium rounded-lg border border-blue-200 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            استيراد موظفين / Import Employees
                        </button>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($this->companies->hasPages())
                <div class="flex items-center justify-between border-t border-gray-200 pt-4">
                    <div class="text-sm text-gray-700">
                        عرض {{ $this->companies->firstItem() ?? 0 }}-{{ $this->companies->lastItem() ?? 0 }} من {{ $this->companies->total() }}
                    </div>
                    <div>
                        {{ $this->companies->links() }}
                    </div>
                </div>
            @endif
        @else
            <div class="text-center py-12 bg-white rounded-xl border border-gray-200">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <p class="text-gray-500 text-lg">لا توجد شركات عملاء</p>
                <p class="text-gray-400 text-sm mt-1">No client companies found</p>
            </div>
        @endif
    </div>

    {{-- Filament Action modals (uploadEmployees) --}}
    <x-filament-actions::modals />
</x-filament-panels::page>
