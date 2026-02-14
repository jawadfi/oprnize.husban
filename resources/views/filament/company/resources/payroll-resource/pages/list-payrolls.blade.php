<x-filament-panels::page>
    <style>
        :root {
            --oprnize-primary: #076EA7;
            --oprnize-bg: #FAFAFA;
            --oprnize-border: #E8E8E8;
            --oprnize-text-1: #4A4A4A;
            --oprnize-text-2: #848484;
            --oprnize-text-3: #C3C3C3;
            --oprnize-text-4: #AAAAAA;
            --oprnize-bg-blue: #F2F6FF;
            --oprnize-green: #05D251;
            --oprnize-red: #E00505;
            --oprnize-card-shadow: 0px 5px 10px rgba(190, 190, 190, 0.10);
        }
        .oprnize-card {
            background: white;
            box-shadow: var(--oprnize-card-shadow);
            border-radius: 12px;
            border: 1px solid #F3F3F3;
            padding: 24px 16px;
        }
        .oprnize-card-title {
            color: var(--oprnize-text-2);
            font-size: 16px;
            font-weight: 500;
            font-family: 'Inter', sans-serif;
        }
        .oprnize-card-value {
            color: var(--oprnize-text-1);
            font-size: 18px;
            font-weight: 600;
        }
        .oprnize-card-change-up {
            color: var(--oprnize-green);
            font-size: 10px;
        }
        .oprnize-card-change-down {
            color: var(--oprnize-red);
            font-size: 10px;
        }
        .oprnize-card-date {
            color: var(--oprnize-text-4);
            font-size: 10px;
        }
        .oprnize-search-input {
            border: 1px solid var(--oprnize-border);
            border-radius: 4px;
            padding: 12px 16px;
            font-size: 14px;
            color: var(--oprnize-text-1);
            background: white;
        }
        .oprnize-search-input::placeholder {
            color: var(--oprnize-text-3);
        }
        .oprnize-month-picker {
            border: 1px solid var(--oprnize-border);
            border-radius: 4px;
            padding: 12px 16px;
            background: white;
            font-size: 14px;
            color: var(--oprnize-text-3);
        }
        .oprnize-btn-export {
            background: var(--oprnize-bg);
            border: 1px solid var(--oprnize-border);
            border-radius: 64px;
            padding: 10px 16px;
            color: var(--oprnize-text-4);
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        .oprnize-btn-export:hover {
            background: #f0f0f0;
        }
        .oprnize-btn-calculate {
            background: var(--oprnize-primary);
            border-radius: 64px;
            padding: 10px 16px;
            color: white;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }
        .oprnize-btn-calculate:hover {
            background: #065d8e;
        }
        /* Override Filament table styles to match Figma */
        .fi-ta-header-cell {
            background: var(--oprnize-bg) !important;
            color: var(--oprnize-text-2) !important;
            font-size: 16px !important;
            font-weight: 400 !important;
            text-align: center !important;
            border: 1px solid var(--oprnize-border) !important;
            padding: 20px 8px !important;
        }
        .fi-ta-cell {
            color: var(--oprnize-text-4) !important;
            font-size: 16px !important;
            font-weight: 400 !important;
            text-align: center !important;
            border: 1px solid var(--oprnize-border) !important;
            padding: 20px 8px !important;
        }
        .fi-ta-row:nth-child(odd) .fi-ta-cell {
            background: white;
        }
        .fi-ta-row:nth-child(even) .fi-ta-cell {
            background: white;
        }
        .fi-ta-row.fi-active .fi-ta-cell,
        .fi-ta-row:hover .fi-ta-cell {
            background: var(--oprnize-bg-blue) !important;
        }
        .fi-ta-table {
            border-radius: 4px !important;
            overflow: hidden;
        }
        .oprnize-company-select {
            border: 1px solid var(--oprnize-primary);
            border-radius: 8px;
            padding: 10px 16px;
            background: white;
            font-size: 14px;
            color: var(--oprnize-text-1);
            min-width: 200px;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23076EA7' d='M6 8.5L1.5 4h9L6 8.5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 32px;
        }
        .oprnize-company-select:focus {
            outline: 2px solid var(--oprnize-primary);
            outline-offset: 1px;
        }
        .oprnize-company-label {
            color: var(--oprnize-primary);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>

    <div class="space-y-6">
        {{-- Company Filter (PROVIDER only) --}}
        @if($this->isProvider())
        <div class="flex items-center gap-4 p-4 bg-white rounded-xl border border-blue-100 shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                </svg>
                <span class="oprnize-company-label">Company Filter / فلتر الشركة</span>
            </div>
            <select 
                wire:model.live="selectedCompany"
                class="oprnize-company-select"
            >
                @foreach($this->getCompanyOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @if($this->selectedCompany === 'in_house')
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-amber-50 text-amber-700 rounded-full text-xs font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    موظفين داخليين - أدخل القيم يدوياً
                </span>
            @elseif($this->selectedCompany !== 'all')
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-50 text-green-700 rounded-full text-xs font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    في الخدمة - يتم الاحتساب تلقائياً
                </span>
            @endif
        </div>
        @endif

        {{-- Summary Cards (Figma design) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Total Employee Card --}}
            <div class="oprnize-card">
                <div class="flex items-center justify-between mb-6">
                    <span class="oprnize-card-title">Total Employee</span>
                    <svg class="w-6 h-6" fill="none" stroke="#848484" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div class="flex items-center gap-4">
                    <span class="oprnize-card-value">{{ $this->getTotalEmployees() }}</span>
                    <span class="oprnize-card-change-up flex items-center gap-0.5">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M6 2.5L9.5 7H2.5L6 2.5Z" fill="#05D251"/></svg>
                        {{ $this->getEmployeePercentageChange() }} %
                    </span>
                </div>
                <p class="oprnize-card-date mt-2">Last Update: {{ $this->getLastUpdateDate() }}</p>
            </div>

            {{-- Total Overtime Card --}}
            <div class="oprnize-card">
                <div class="flex items-center justify-between mb-6">
                    <span class="oprnize-card-title">Total Overtime</span>
                    <svg class="w-6 h-6" fill="none" stroke="#848484" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex items-center gap-4">
                    <span class="oprnize-card-value">SAR {{ number_format($this->getTotalOvertime(), 2) }}</span>
                    <span class="oprnize-card-change-down flex items-center gap-0.5">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M6 9.5L2.5 5H9.5L6 9.5Z" fill="#E00505"/></svg>
                        {{ $this->getOvertimePercentageChange() }} %
                    </span>
                </div>
                <p class="oprnize-card-date mt-2">Last Update: {{ $this->getLastUpdateDate() }}</p>
            </div>

            {{-- Tot. Without Overtime Card --}}
            <div class="oprnize-card">
                <div class="flex items-center justify-between mb-6">
                    <span class="oprnize-card-title">Tot. Without Overtime</span>
                    <svg class="w-6 h-6" fill="none" stroke="#848484" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                </div>
                <div class="flex items-center gap-4">
                    <span class="oprnize-card-value">SAR {{ number_format($this->getTotalWithoutOvertime(), 2) }}</span>
                    <span class="oprnize-card-change-up flex items-center gap-0.5">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M6 2.5L9.5 7H2.5L6 2.5Z" fill="#05D251"/></svg>
                        {{ $this->getWithoutOvertimePercentageChange() }} %
                    </span>
                </div>
                <p class="oprnize-card-date mt-2">Last Update: {{ $this->getLastUpdateDate() }}</p>
            </div>

            {{-- Net Payment Card --}}
            <div class="oprnize-card">
                <div class="flex items-center justify-between mb-6">
                    <span class="oprnize-card-title">Net Payment</span>
                    <svg class="w-6 h-6" fill="none" stroke="#848484" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                    </svg>
                </div>
                <div class="flex items-center gap-4">
                    <span class="oprnize-card-value">SAR {{ number_format($this->getNetPayment(), 2) }}</span>
                    <span class="oprnize-card-change-up flex items-center gap-0.5">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M6 2.5L9.5 7H2.5L6 2.5Z" fill="#05D251"/></svg>
                        {{ $this->getNetPaymentPercentageChange() }} %
                    </span>
                </div>
                <p class="oprnize-card-date mt-2">Last Update: {{ $this->getLastUpdateDate() }}</p>
            </div>
        </div>

        {{-- Action Bar (Figma design) --}}
        <div class="flex flex-col md:flex-row gap-3 items-center justify-between">
            <div class="flex items-center gap-3 flex-1">
                {{-- Search --}}
                <div class="relative flex-1 max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5" fill="none" stroke="#B1B1B1" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="tableSearch"
                        placeholder="Search by ( Employee name .ID)"
                        class="oprnize-search-input block w-full pl-12 pr-4 py-3"
                    />
                </div>

                {{-- Month Picker --}}
                <div class="flex items-center gap-2">
                    <button wire:click="previousMonth" class="p-1 text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="#B1B1B1" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </button>
                    <div class="oprnize-month-picker min-w-[160px] text-center">
                        {{ $this->getSelectedMonthYear() }}
                    </div>
                    <button wire:click="nextMonth" class="p-1 text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="#B1B1B1" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-4">
                <button type="button" wire:click="exportPayroll" class="oprnize-btn-export">
                    <svg class="w-5 h-5" fill="none" stroke="#B1B1B1" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Export
                </button>
                <button type="button" wire:click="calculatePayroll" class="oprnize-btn-calculate">
                    <svg class="w-5 h-5" fill="none" stroke="white" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008H18v-.008zm0 2.25h.008v.008H18V13.5zM7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Calculate Payroll
                </button>
            </div>
        </div>

        {{-- Payroll Table --}}
        {{ $this->table }}
    </div>
</x-filament-panels::page>

