<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        @foreach($this->getLinks() as $link)
            <a href="{{ $link['url'] }}"
               class="block rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-cyan-400 hover:shadow">
                <div class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-cyan-50 text-cyan-700">
                    <x-filament::icon :icon="$link['icon']" class="h-5 w-5" />
                </div>
                <h3 class="text-base font-semibold text-gray-900">{{ $link['title'] }}</h3>
                <p class="mt-1 text-sm text-gray-600">{{ $link['description'] }}</p>
            </a>
        @endforeach
    </div>
</x-filament-panels::page>
