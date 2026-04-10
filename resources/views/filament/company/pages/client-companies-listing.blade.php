<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <p class="text-sm text-gray-600 mt-1">
                    ابحث برقم السجل التجاري للشركة للربط بها، أو استعرض شركاتك المرتبطة أدناه
                </p>
            </div>
            @if(! $this->search)
                <span class="text-sm text-gray-500">
                    {{ $this->companies->total() }} شركة مرتبطة
                </span>
            @endif
        </div>

        {{-- Search Bar (by CR number) --}}
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="البحث برقم السجل التجاري / Search by CR number"
                class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg
                       focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
            />
            @if($this->search)
                <button
                    wire:click="$set('search', '')"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                    title="مسح البحث"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            @endif
        </div>

        {{-- Search mode hint (when idle) --}}
        @if(! $this->search)
            <div class="flex items-center gap-2 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>اكتب رقم السجل التجاري للبحث عن شركة عميل جديدة وإرسال طلب ربط إليها</span>
            </div>
        @endif

        {{-- Companies Grid --}}
        @if($this->companies->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($this->companies as $company)
                    @php
                        $connection  = $company->receivedConnections->first();
                        $connStatus  = $connection?->status?->value; // null|pending|approved|declined
                        $isApproved  = $connStatus === 'approved';
                        $isPending   = $connStatus === 'pending';
                        $isDeclined  = $connStatus === 'declined';
                        $noConn      = $connection === null;
                    @endphp
                    <div class="bg-white rounded-xl p-5 flex flex-col gap-3 transition-all duration-200 border
                        {{ $isApproved  ? 'border-emerald-300 hover:border-emerald-400 hover:shadow-md' : '' }}
                        {{ $isPending   ? 'border-amber-200 bg-amber-50/30' : '' }}
                        {{ $isDeclined  ? 'border-red-200   bg-red-50/20' : '' }}
                        {{ $noConn      ? 'border-gray-200  hover:border-primary-300 hover:shadow-md' : '' }}
                    ">

                        {{-- Card header row --}}
                        <div class="flex items-start justify-between gap-2">
                            @if($isApproved)
                                <a href="{{ \App\Filament\Company\Pages\ClientCompanyEmployees::getUrl(['companyId' => $company->id]) }}"
                                   class="flex items-center gap-3 group flex-1 min-w-0">
                            @else
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                            @endif
                                <div class="w-11 h-11 bg-gradient-to-br from-emerald-400 to-teal-600 rounded-xl
                                            flex items-center justify-center text-white font-bold text-lg shadow-sm shrink-0">
                                    {{ mb_substr($company->name, 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-semibold text-gray-900 line-clamp-2 text-sm
                                        {{ $isApproved ? 'group-hover:text-primary-600 transition-colors' : '' }}">
                                        {{ $company->name }}
                                    </h3>
                                    @if($company->commercial_registration_number)
                                        <span class="text-xs text-gray-400">
                                            CR: {{ $company->commercial_registration_number }}
                                        </span>
                                    @endif
                                </div>
                            @if($isApproved)
                                </a>
                            @else
                                </div>
                            @endif

                            {{-- Connection status badge --}}
                            @if($isApproved)
                                <span class="shrink-0 inline-flex items-center gap-1 text-xs font-medium
                                             text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    مرتبط
                                </span>
                            @elseif($isPending)
                                <span class="shrink-0 inline-flex items-center gap-1 text-xs font-medium
                                             text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full whitespace-nowrap">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    انتظار
                                </span>
                            @elseif($isDeclined)
                                <span class="shrink-0 inline-flex items-center gap-1 text-xs font-medium
                                             text-red-700 bg-red-100 px-2 py-0.5 rounded-full">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    مرفوض
                                </span>
                            @endif
                        </div>

                        {{-- Body info --}}
                        @if($isApproved)
                            <div class="space-y-1.5">
                                <div class="flex items-center text-sm text-emerald-600">
                                    <svg class="w-4 h-4 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="font-medium">{{ $company->assigned_employees_count ?? 0 }}</span>
                                    <span class="text-gray-400 ml-1">موظف معين</span>
                                </div>
                                @if(($company->pending_employees_count ?? 0) > 0)
                                    <div class="flex items-center text-sm text-amber-500">
                                        <svg class="w-4 h-4 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="font-medium">{{ $company->pending_employees_count }}</span>
                                        <span class="text-gray-400 ml-1">بانتظار موافقة العميل</span>
                                    </div>
                                @endif
                            </div>
                        @elseif($isPending)
                            <p class="text-xs text-amber-600">
                                تم إرسال طلب الربط — بانتظار موافقة شركة العميل.
                            </p>
                        @elseif($isDeclined)
                            <p class="text-xs text-red-500">تم رفض طلب الربط من قِبل شركة العميل.</p>
                        @else
                            <p class="text-xs text-gray-500">
                                أرسل طلب ربط لتتمكن من تعيين موظفين لهذه الشركة.
                            </p>
                        @endif

                        {{-- Action buttons --}}
                        <div class="mt-auto flex flex-col gap-2">
                            @if($isApproved)
                                <button
                                    wire:click="mountAction('uploadEmployees', @js(['companyId' => $company->id]))"
                                    class="w-full inline-flex items-center justify-center gap-2 px-3 py-2
                                           bg-blue-50 hover:bg-blue-100 text-blue-700 text-sm font-medium
                                           rounded-lg border border-blue-200 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    استيراد موظفين (Excel)
                                </button>
                                <a
                                    href="{{ url('/company/bulk-assign-template') }}"
                                    download
                                    class="w-full inline-flex items-center justify-center gap-2 px-3 py-1.5
                                           text-xs text-emerald-700 hover:text-emerald-800 transition-colors"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    تحميل نموذج Excel
                                </a>
                            @elseif($noConn || $isDeclined)
                                <button
                                    wire:click="mountAction('sendConnectionRequest', @js(['companyId' => $company->id]))"
                                    class="w-full inline-flex items-center justify-center gap-2 px-3 py-2
                                           bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium
                                           rounded-lg transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                    إرسال طلب ربط
                                </button>
                            @elseif($isPending)
                                <div class="w-full flex items-center justify-center gap-2 px-3 py-2
                                            bg-amber-50 text-amber-600 text-sm rounded-lg border border-amber-200 cursor-default">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    بانتظار الموافقة
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($this->companies->hasPages())
                <div class="flex items-center justify-between border-t border-gray-200 pt-4">
                    <div class="text-sm text-gray-700">
                        عرض {{ $this->companies->firstItem() ?? 0 }}–{{ $this->companies->lastItem() ?? 0 }}
                        من {{ $this->companies->total() }}
                    </div>
                    <div>{{ $this->companies->links() }}</div>
                </div>
            @endif

        @else
            <div class="text-center py-12 bg-white rounded-xl border border-gray-200">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                @if($this->search)
                    <p class="text-gray-500 text-lg">لا توجد نتائج لـ "{{ $this->search }}"</p>
                    <p class="text-gray-400 text-sm mt-1">تأكد من رقم السجل التجاري وحاول مجدداً</p>
                @else
                    <p class="text-gray-500 text-lg">لا توجد شركات عملاء مرتبطة بعد</p>
                    <p class="text-gray-400 text-sm mt-1">ابحث برقم السجل التجاري وأرسل طلب ربط</p>
                @endif
            </div>
        @endif

    </div>

    {{-- Filament Action modals (sendConnectionRequest + uploadEmployees) --}}
    <x-filament-actions::modals />
</x-filament-panels::page>
