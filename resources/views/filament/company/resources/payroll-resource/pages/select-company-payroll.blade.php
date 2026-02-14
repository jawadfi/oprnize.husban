<x-filament-panels::page>
    <style>
        .company-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #F3F3F3;
            box-shadow: 0px 5px 10px rgba(190, 190, 190, 0.10);
            padding: 24px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .company-card:hover {
            border-color: #076EA7;
            box-shadow: 0px 5px 20px rgba(7, 110, 167, 0.15);
            transform: translateY(-2px);
        }
        .company-card-name {
            font-size: 18px;
            font-weight: 600;
            color: #4A4A4A;
        }
        .company-card-name-ar {
            font-size: 14px;
            font-weight: 500;
            color: #848484;
            margin-top: 2px;
        }
        .company-card-email {
            font-size: 14px;
            color: #848484;
            margin-top: 4px;
        }
        .company-card-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #F2F6FF;
            color: #076EA7;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            margin-top: 12px;
        }
        .company-card-all {
            border: 2px dashed #076EA7;
            background: #F2F6FF;
        }
        .company-card-all:hover {
            background: #E5EFFA;
        }
        .company-card-inhouse {
            border: 2px solid #059669;
            background: #ECFDF5;
        }
        .company-card-inhouse:hover {
            background: #D1FAE5;
            border-color: #047857;
        }
        .company-card-inhouse .company-card-badge {
            background: #D1FAE5;
            color: #059669;
        }
        .company-card-nopayroll {
            border: 2px solid #D97706;
            background: #FFFBEB;
        }
        .company-card-nopayroll:hover {
            background: #FEF3C7;
            border-color: #B45309;
        }
        .company-card-nopayroll .company-card-badge {
            background: #FEF3C7;
            color: #D97706;
        }
        .section-title {
            font-size: 22px;
            font-weight: 600;
            color: #4A4A4A;
            margin-bottom: 8px;
        }
        .section-desc {
            font-size: 14px;
            color: #848484;
            margin-bottom: 24px;
        }
        .section-subtitle {
            font-size: 16px;
            font-weight: 600;
            color: #6B7280;
            margin-top: 32px;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid #E5E7EB;
        }
    </style>

    <div>
        <p class="section-title">اختر الشركة / Select Company</p>
        <p class="section-desc">اختر شركة العميل لعرض وحساب رواتب الموظفين المعينين لها</p>

        @php
            $companies = $this->getClientCompanies();
            $specialCards = collect($companies)->whereIn('type', ['all', 'in_house', 'no_payroll']);
            $clientCards = collect($companies)->where('type', 'client');
        @endphp

        {{-- Special Cards Row --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            @foreach ($specialCards as $company)
                <div
                    wire:click="selectCompany('{{ $company['id'] }}')"
                    class="company-card {{ $company['type'] === 'all' ? 'company-card-all' : '' }} {{ $company['type'] === 'in_house' ? 'company-card-inhouse' : '' }} {{ $company['type'] === 'no_payroll' ? 'company-card-nopayroll' : '' }}"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="company-card-name">{{ $company['name'] }}</p>
                            @if (!empty($company['name_ar']))
                                <p class="company-card-name-ar">{{ $company['name_ar'] }}</p>
                            @endif
                            @if ($company['email'])
                                <p class="company-card-email">{{ $company['email'] }}</p>
                            @endif
                        </div>
                        <div>
                            @if ($company['type'] === 'all')
                                <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                </svg>
                            @elseif ($company['type'] === 'in_house')
                                <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                </svg>
                            @elseif ($company['type'] === 'no_payroll')
                                <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            @endif
                        </div>
                    </div>

                    <div class="company-card-badge">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                        {{ $company['employee_count'] }} موظف
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Client Companies Section --}}
        @if ($clientCards->count() > 0)
            <p class="section-subtitle">🏢 شركات العملاء / Client Companies</p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($clientCards as $company)
                    <div
                        wire:click="selectCompany('{{ $company['id'] }}')"
                        class="company-card"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="company-card-name">{{ $company['name'] }}</p>
                                @if ($company['email'])
                                    <p class="company-card-email">{{ $company['email'] }}</p>
                                @endif
                                @if ($company['city'])
                                    <p class="company-card-email">{{ $company['city'] }}</p>
                                @endif
                            </div>
                            <div>
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3H21m-3.75 3H21" />
                                </svg>
                            </div>
                        </div>

                        <div class="company-card-badge">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                            {{ $company['employee_count'] }} موظف
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($clientCards->count() === 0)
            <div class="text-center py-12 text-gray-400">
                <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <p class="text-lg">لا توجد شركات عملاء بعد</p>
                <p class="text-sm mt-2">قم بتعيين موظفين لشركات عملاء أولاً</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
