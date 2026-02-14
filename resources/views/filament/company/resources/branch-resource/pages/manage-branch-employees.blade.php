<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Branch Info Card --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">الفرع / Branch</span>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $this->record->name }}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">الموقع / Location</span>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $this->record->location ?? 'غير محدد' }}</p>
                </div>
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">المدير / Manager</span>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $this->record->manager?->name ?? 'لم يتم التعيين' }}</p>
                </div>
            </div>
        </div>

        {{-- Employees Table --}}
        {{ $this->table }}
    </div>
</x-filament-panels::page>
