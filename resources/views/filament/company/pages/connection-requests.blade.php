<x-filament-panels::page>
    <div class="space-y-8">

        {{-- Pending Requests Section --}}
        <div>
            <h2 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-6 h-6 bg-amber-100 text-amber-700 rounded-full text-xs font-bold">
                    {{ $this->pendingRequests->total() }}
                </span>
                طلبات الربط المعلقة / Pending Requests
            </h2>

            @if($this->pendingRequests->count() > 0)
                <div class="flex flex-col gap-3">
                    @foreach($this->pendingRequests as $connection)
                        <div class="bg-white border border-amber-200 rounded-xl p-4 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 bg-gradient-to-br from-violet-400 to-indigo-600 rounded-lg
                                            flex items-center justify-center text-white font-bold text-base shrink-0">
                                    {{ mb_substr($connection->provider->name ?? '?', 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 truncate">{{ $connection->provider->name ?? 'غير معروف' }}</p>
                                    @if($connection->provider?->commercial_registration_number)
                                        <p class="text-xs text-gray-400">CR: {{ $connection->provider->commercial_registration_number }}</p>
                                    @endif
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $connection->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button
                                    wire:click="mountAction('approveConnection', @js(['connectionId' => $connection->id]))"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700
                                           text-white text-sm font-medium rounded-lg transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    موافقة
                                </button>
                                <button
                                    wire:click="mountAction('declineConnection', @js(['connectionId' => $connection->id]))"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 hover:bg-red-100
                                           text-red-700 text-sm font-medium rounded-lg border border-red-200 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    رفض
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($this->pendingRequests->hasPages())
                    <div class="mt-3">{{ $this->pendingRequests->links() }}</div>
                @endif
            @else
                <div class="text-center py-10 bg-gray-50 border border-gray-200 rounded-xl">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-gray-400 text-sm">لا توجد طلبات معلقة / No pending requests</p>
                </div>
            @endif
        </div>

        {{-- All Other Connections Section --}}
        @if($this->allRequests->count() > 0)
            <div>
                <h2 class="text-base font-semibold text-gray-900 mb-4">
                    سجل الطلبات / Request History
                </h2>
                <div class="flex flex-col gap-3">
                    @foreach($this->allRequests as $connection)
                        @php
                            $isApproved = $connection->status->value === 'approved';
                        @endphp
                        <div class="bg-white border rounded-xl p-4 flex items-center justify-between gap-4
                            {{ $isApproved ? 'border-emerald-200' : 'border-gray-200' }}">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 bg-gradient-to-br from-violet-400 to-indigo-600 rounded-lg
                                            flex items-center justify-center text-white font-bold text-base shrink-0">
                                    {{ mb_substr($connection->provider->name ?? '?', 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 truncate">{{ $connection->provider->name ?? 'غير معروف' }}</p>
                                    @if($connection->provider?->commercial_registration_number)
                                        <p class="text-xs text-gray-400">CR: {{ $connection->provider->commercial_registration_number }}</p>
                                    @endif
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $connection->updated_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            @if($isApproved)
                                <span class="shrink-0 inline-flex items-center gap-1 text-xs font-medium
                                             text-emerald-700 bg-emerald-100 px-2.5 py-1 rounded-full">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    مرتبط / Connected
                                </span>
                            @else
                                <span class="shrink-0 inline-flex items-center gap-1 text-xs font-medium
                                             text-red-700 bg-red-100 px-2.5 py-1 rounded-full">
                                    مرفوض / Declined
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if($this->allRequests->hasPages())
                    <div class="mt-3">{{ $this->allRequests->links() }}</div>
                @endif
            </div>
        @endif

    </div>

    {{-- Filament Action modals --}}
    <x-filament-actions::modals />
</x-filament-panels::page>
