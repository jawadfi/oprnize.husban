<x-filament-panels::page>
    @php
        $currentPayroll = $this->getCurrentPayroll();
    @endphp

    @if($currentPayroll)
        <x-filament::section>
            <x-slot name="heading">
                Current Salary Information
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Basic Salary</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($currentPayroll->basic_salary, 2) }} SAR
                    </p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Allowances</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($currentPayroll->total_other_allow, 2) }} SAR
                    </p>
                </div>

                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Salary</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ number_format($currentPayroll->total_salary, 2) }} SAR
                    </p>
                </div>

                <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Monthly Cost</p>
                    <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                        {{ number_format($currentPayroll->monthly_cost, 2) }} SAR
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Allowances Breakdown</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Housing Allowance:</span>
                            <span class="text-sm font-medium">{{ number_format($currentPayroll->housing_allowance, 2) }} SAR</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Transportation Allowance:</span>
                            <span class="text-sm font-medium">{{ number_format($currentPayroll->transportation_allowance, 2) }} SAR</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Food Allowance:</span>
                            <span class="text-sm font-medium">{{ number_format($currentPayroll->food_allowance, 2) }} SAR</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Other Allowance:</span>
                            <span class="text-sm font-medium">{{ number_format($currentPayroll->other_allowance, 2) }} SAR</span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Additional Information</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Fees:</span>
                            <span class="text-sm font-medium">{{ number_format($currentPayroll->fees, 2) }} SAR</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Last Updated:</span>
                            <span class="text-sm font-medium">{{ $currentPayroll->updated_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>
    @endif

    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Salary History
        </x-slot>
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>

