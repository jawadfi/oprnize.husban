<x-filament-panels::page>
    <form wire:submit="calculate">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" size="lg">
                <x-heroicon-o-calculator class="w-5 h-5 me-1" />
                احسب المكافأة
            </x-filament::button>
        </div>
    </form>

    @if($this->result !== null)
        <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-3">
            {{-- سنوات الخدمة --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-2 text-sm font-medium text-gray-500 dark:text-gray-400">مدة الخدمة</div>
                <div class="text-3xl font-bold text-cyan-700 dark:text-cyan-400">
                    {{ $this->serviceYears }} <span class="text-base font-normal">سنة</span>
                </div>
            </div>

            {{-- سبب إنهاء الخدمة --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-2 text-sm font-medium text-gray-500 dark:text-gray-400">سبب إنهاء الخدمة</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ \App\Enums\TerminationReason::getTranslatedEnum()[(int) $this->data['reason']] ?? '-' }}
                </div>
            </div>

            {{-- المكافأة --}}
            <div class="rounded-xl border border-cyan-300 bg-cyan-50 p-6 shadow-sm dark:border-cyan-700 dark:bg-cyan-900/30">
                <div class="mb-2 text-sm font-medium text-cyan-700 dark:text-cyan-400">مكافأة نهاية الخدمة</div>
                <div class="text-3xl font-bold text-cyan-800 dark:text-cyan-300">
                    {{ number_format($this->result, 2) }} <span class="text-base font-normal">SAR</span>
                </div>
            </div>
        </div>

        {{-- جدول التوضيح --}}
        <div class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">تفاصيل الحساب</h3>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="px-4 py-2 text-right font-medium text-gray-600 dark:text-gray-400">البند</th>
                        <th class="px-4 py-2 text-right font-medium text-gray-600 dark:text-gray-400">القيمة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <tr>
                        <td class="px-4 py-2 text-gray-700 dark:text-gray-300">الراتب الشهري</td>
                        <td class="px-4 py-2 text-gray-900 dark:text-white font-medium">{{ number_format((float) $this->data['salary'], 2) }} SAR</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 text-gray-700 dark:text-gray-300">مدة الخدمة</td>
                        <td class="px-4 py-2 text-gray-900 dark:text-white font-medium">{{ $this->serviceYears }} سنة</td>
                    </tr>
                    @php
                        $reasonInt = (int) $this->data['reason'];
                    @endphp
                    @if($reasonInt === \App\Enums\TerminationReason::ARTICLE_80)
                        <tr>
                            <td class="px-4 py-2 text-red-600 font-medium" colspan="2">
                                فصل بموجب المادة 80 — لا يستحق مكافأة
                            </td>
                        </tr>
                    @elseif($reasonInt === \App\Enums\TerminationReason::RESIGNATION && $this->serviceYears < 2)
                        <tr>
                            <td class="px-4 py-2 text-amber-600 font-medium" colspan="2">
                                استقالة بأقل من سنتين — لا يستحق مكافأة
                            </td>
                        </tr>
                    @else
                        @php
                            $salary = (float) $this->data['salary'];
                            $yearsP1 = min($this->serviceYears, 5);
                            $yearsP2 = max(0, $this->serviceYears - 5);
                            $isResignation = $reasonInt === \App\Enums\TerminationReason::RESIGNATION;

                            $bonusP1 = $yearsP1 * ($salary / 2);
                            $bonusP2 = $yearsP2 * $salary;

                            if ($isResignation) {
                                $bonusP1 *= (1 / 3);
                                $bonusP2 *= (2 / 3);
                            }
                        @endphp
                        <tr>
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                                أول 5 سنوات ({{ round($yearsP1, 2) }} سنة × نصف الراتب{{ $isResignation ? ' × ⅓' : '' }})
                            </td>
                            <td class="px-4 py-2 text-gray-900 dark:text-white font-medium">{{ number_format($bonusP1, 2) }} SAR</td>
                        </tr>
                        @if($yearsP2 > 0)
                        <tr>
                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                                ما بعد 5 سنوات ({{ round($yearsP2, 2) }} سنة × الراتب الكامل{{ $isResignation ? ' × ⅔' : '' }})
                            </td>
                            <td class="px-4 py-2 text-gray-900 dark:text-white font-medium">{{ number_format($bonusP2, 2) }} SAR</td>
                        </tr>
                        @endif
                        <tr class="bg-cyan-50 dark:bg-cyan-900/20 font-bold">
                            <td class="px-4 py-2 text-cyan-800 dark:text-cyan-300">الإجمالي</td>
                            <td class="px-4 py-2 text-cyan-800 dark:text-cyan-300">{{ number_format($this->result, 2) }} SAR</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>
