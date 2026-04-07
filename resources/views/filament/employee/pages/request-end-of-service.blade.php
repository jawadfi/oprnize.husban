<x-filament-panels::page>
<div>
    <form wire:submit="create">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" size="lg">
                Send Request
            </x-filament::button>
        </div>
    </form>

    <x-filament-actions::modals />
</div>
</x-filament-panels::page>
