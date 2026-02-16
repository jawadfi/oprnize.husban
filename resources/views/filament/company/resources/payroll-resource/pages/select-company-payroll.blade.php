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

        /* Category Cards */
        .category-card {
            background: white;
            border-radius: 16px;
            border: 2px solid #E5E7EB;
            box-shadow: 0px 5px 15px rgba(190, 190, 190, 0.12);
            padding: 32px 24px;
            cursor: pointer;
            transition: all 0.25s ease;
            text-align: center;
        }
        .category-card:hover {
            transform: translateY(-4px);
            box-shadow: 0px 10px 30px rgba(7, 110, 167, 0.15);
        }
        .category-card-contracted {
            border-color: #6366F1;
        }
        .category-card-contracted:hover {
            border-color: #4F46E5;
            background: #EEF2FF;
        }
        .category-card-run {
            border-color: #076EA7;
        }
        .category-card-run:hover {
            border-color: #065d8e;
            background: #F0F9FF;
        }
        .category-card-review {
            border-color: #059669;
        }
        .category-card-review:hover {
            border-color: #047857;
            background: #ECFDF5;
        }
        .category-card-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        .category-card-title {
            font-size: 20px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 4px;
        }
        .category-card-title-ar {
            font-size: 16px;
            font-weight: 600;
            color: #6B7280;
            margin-bottom: 12px;
        }
        .category-card-desc {
            font-size: 13px;
            color: #9CA3AF;
            line-height: 1.5;
        }
        .category-card-features {
            margin-top: 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: center;
        }
        .category-feature-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        .feature-enabled {
            background: #ECFDF5;
            color: #059669;
        }
        .feature-disabled {
            background: #FEF2F2;
            color: #DC2626;
        }
    </style>

    <div>
        @if (!$this->payrollCategory)
            {{-- STEP 1: Category Selection --}}
            <p class="section-title">اختر نوع كشف الرواتب / Select Payroll Category</p>
            <p class="section-desc">اختر نوع عملية كشف الرواتب التي تريد الوصول إليها</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-8">
                {{-- Contracted Payroll --}}
                <div wire:click="selectCategory('contracted')" class="category-card category-card-contracted">
                    <div class="category-card-icon" style="background: #EEF2FF;">
                        <svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </div>
                    <p class="category-card-title">Contracted Payroll</p>
                    <p class="category-card-title-ar">كشف رواتب تعاقدي</p>
                    <p class="category-card-desc">عرض كشوف الرواتب التعاقدية - للعرض فقط بدون تعديل</p>
                    <div class="category-card-features">
                        <span class="category-feature-tag feature-enabled">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            View
                        </span>
                        <span class="category-feature-tag feature-disabled">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            Edit
                        </span>
                        <span class="category-feature-tag feature-disabled">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            Export
                        </span>
                        <span class="category-feature-tag feature-disabled">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            Calculate
                        </span>
                    </div>
                </div>

                {{-- Run Payroll --}}
                <div wire:click="selectCategory('run')" class="category-card category-card-run">
                    <div class="category-card-icon" style="background: #F0F9FF;">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008H18v-.008zm0 2.25h.008v.008H18V13.5zM7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <p class="category-card-title">Run Payroll</p>
                    <p class="category-card-title-ar">تشغيل الرواتب</p>
                    <p class="category-card-desc">إدارة وتشغيل واحتساب كشوف الرواتب مع التعديل</p>
                    <div class="category-card-features">
                        <span class="category-feature-tag feature-enabled">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            View
                        </span>
                        <span class="category-feature-tag feature-enabled">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Edit
                        </span>
                        <span class="category-feature-tag feature-disabled">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            Export
                        </span>
                        <span class="category-feature-tag feature-enabled">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Calculate
                        </span>
                    </div>
                </div>

                {{-- Review --}}
                <div wire:click="selectCategory('review')" class="category-card category-card-review">
                    <div class="category-card-icon" style="background: #ECFDF5;">
                        <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                    </div>
                    <p class="category-card-title">Review</p>
                    <p class="category-card-title-ar">مراجعة الرواتب</p>
                    <p class="category-card-desc">مراجعة كشوف الرواتب وتصدير البيانات فقط</p>
                    <div class="category-card-features">
                        <span class="category-feature-tag feature-enabled">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            View
                        </span>
                        <span class="category-feature-tag feature-disabled">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            Edit
                        </span>
                        <span class="category-feature-tag feature-enabled">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Export
                        </span>
                        <span class="category-feature-tag feature-disabled">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            Calculate
                        </span>
                    </div>
                </div>
            </div>
        @else
            {{-- STEP 2: Company Selection (after category is chosen) --}}
            <div class="flex items-center gap-3 mb-6">
                <button wire:click="resetCategory" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-full hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    العودة لاختيار النوع
                </button>
                @php
                    $categoryBadges = [
                        'contracted' => ['label' => 'Contracted Payroll / كشف رواتب تعاقدي', 'color' => '#6366F1', 'bg' => '#EEF2FF'],
                        'run' => ['label' => 'Run Payroll / تشغيل الرواتب', 'color' => '#076EA7', 'bg' => '#F0F9FF'],
                        'review' => ['label' => 'Review / مراجعة الرواتب', 'color' => '#059669', 'bg' => '#ECFDF5'],
                    ];
                    $badge = $categoryBadges[$this->payrollCategory] ?? $categoryBadges['run'];
                @endphp
                <span class="px-4 py-2 rounded-full text-sm font-semibold" style="background: {{ $badge['bg'] }}; color: {{ $badge['color'] }};">
                    {{ $badge['label'] }}
                </span>
            </div>

            @if ($this->companyType === 'client')
                <p class="section-title">اختر شركة المزود / Select Provider</p>
                <p class="section-desc">اختر شركة المزود لعرض وحساب رواتب الموظفين المستعارين منها</p>
            @else
                <p class="section-title">اختر الشركة / Select Company</p>
                <p class="section-desc">اختر شركة العميل لعرض وحساب رواتب الموظفين المعينين لها</p>
            @endif

        @php
            $companies = $this->getClientCompanies();
            $specialCards = collect($companies)->whereIn('type', ['all', 'in_house', 'no_payroll']);
            $regularCards = collect($companies)->whereNotIn('type', ['all', 'in_house', 'no_payroll']);
        @endphp

        {{-- Special Cards Row --}}
        <div class="grid grid-cols-1 md:grid-cols-{{ $specialCards->count() > 0 ? min($specialCards->count(), 3) : 1 }} gap-6 mb-6">
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

        {{-- Company Cards Section --}}
        @if ($regularCards->count() > 0)
            @if ($this->companyType === 'client')
                <p class="section-subtitle">🏭 شركات المزودين / Provider Companies</p>
            @else
                <p class="section-subtitle">🏢 شركات العملاء / Client Companies</p>
            @endif
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($regularCards as $company)
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

        @if ($regularCards->count() === 0)
            <div class="text-center py-12 text-gray-400">
                <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                @if ($this->companyType === 'client')
                    <p class="text-lg">لا توجد شركات مزودة بعد</p>
                    <p class="text-sm mt-2">لم يتم تعيين موظفين من أي شركة مزودة حتى الآن</p>
                @else
                    <p class="text-lg">لا توجد شركات عملاء بعد</p>
                    <p class="text-sm mt-2">قم بتعيين موظفين لشركات عملاء أولاً</p>
                @endif
            </div>
        @endif
        @endif
    </div>
</x-filament-panels::page>
