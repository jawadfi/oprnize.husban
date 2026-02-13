<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Month Selector --}}
        <div class="flex items-center gap-4 p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('Select Month') }}:
            </label>
            <input 
                type="month" 
                wire:model.live="selectedMonth"
                class="border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
        </div>

        {{-- Instructions --}}
        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                📊 {{ __('How Dual Company Payroll Works') }}
            </h3>
            <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside">
                <li><strong>{{ __('Provider Company') }}:</strong> {{ __('Calculates cost for providing employees') }}</li>
                <li><strong>{{ __('Client Company') }}:</strong> {{ __('Calculates cost for receiving employees') }}</li>
                <li>{{ __('Both companies can calculate payroll independently using "Calculate Payroll" button') }}</li>
                <li>{{ __('This page shows side-by-side comparison for verification and matching') }}</li>
                <li>{{ __('Red status = significant difference, Green status = matched') }}</li>
            </ul>
        </div>

        {{-- Payroll Comparison Table --}}
        {{ $this->table }}
    </div>
</x-filament-panels::page>
