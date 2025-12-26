<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Total Employee Card --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Employee</p>
                        <p class="text-2xl font-bold text-gray-900 mt-2">{{ $this->getTotalEmployees() }}</p>
                        <p class="text-sm text-green-600 mt-1">↑{{ $this->getEmployeePercentageChange() }}%</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-3">Last Update: {{ $this->getLastUpdateDate() }}</p>
            </div>

            {{-- Total Overtime Card --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Overtime</p>
                        <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($this->getTotalOvertime(), 2) }} SAR</p>
                        <p class="text-sm text-green-600 mt-1">↑{{ $this->getOvertimePercentageChange() }}%</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-3">Last Update: {{ $this->getLastUpdateDate() }}</p>
            </div>

            {{-- Total Without Overtime Card --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Without Overtime</p>
                        <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($this->getTotalWithoutOvertime(), 2) }} SAR</p>
                        <p class="text-sm text-green-600 mt-1">↑{{ $this->getWithoutOvertimePercentageChange() }}%</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-3">Last Update: {{ $this->getLastUpdateDate() }}</p>
            </div>

            {{-- Net Payment Card --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Net Payment</p>
                        <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($this->getNetPayment(), 2) }} SAR</p>
                        <p class="text-sm text-green-600 mt-1">↑{{ $this->getNetPaymentPercentageChange() }}%</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-3">Last Update: {{ $this->getLastUpdateDate() }}</p>
            </div>
        </div>

        {{-- Action Bar --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                {{-- Search --}}
                <div class="flex-1 w-full md:w-auto">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="tableSearch"
                            placeholder="Search by (Employee name .ID)"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        />
                    </div>
                </div>

                {{-- Date Selector --}}
                <div class="flex items-center gap-2">
                    <button 
                        wire:click="previousMonth"
                        class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <div class="px-4 py-2 bg-gray-50 rounded-lg border border-gray-200 min-w-[150px] text-center">
                        <span class="font-medium text-gray-900">{{ $this->getSelectedMonthYear() }}</span>
                    </div>
                    <button 
                        wire:click="nextMonth"
                        class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        onclick="document.querySelector('[data-export-action]')?.click()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors font-medium"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Export
                    </button>
                    <button
                        type="button"
                        wire:click="calculatePayroll"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors font-medium"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Calculate Payroll
                    </button>
                </div>
            </div>
        </div>

        {{-- Table --}}
        {{ $this->table }}
    </div>
</x-filament-panels::page>

