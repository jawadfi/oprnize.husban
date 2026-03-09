<x-filament-panels::page.simple>
    {{-- Back and Main Menu buttons --}}
    <div class="flex gap-3 justify-center mb-4">
        <a
            href="{{ filament()->getLoginUrl() }}"
            class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700"
        >
            <x-heroicon-o-arrow-left class="w-4 h-4" />
            رجوع / Back
        </a>
        <a
            href="{{ url('/') }}"
            class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700"
        >
            <x-heroicon-o-home class="w-4 h-4" />
            القائمة الرئيسية / Main Menu
        </a>
    </div>

    <p class="text-center text-sm text-gray-500 dark:text-gray-400">
        {{
            __('filament-panels::pages/auth/email-verification/email-verification-prompt.messages.notification_sent', [
                'email' => filament()->auth()->user()->getEmailForVerification(),
            ])
        }}
    </p>

    <p class="text-center text-sm text-gray-500 dark:text-gray-400">
        {{ __('filament-panels::pages/auth/email-verification/email-verification-prompt.messages.notification_not_received') }}

        {{ $this->resendNotificationAction }}
    </p>
</x-filament-panels::page.simple>
