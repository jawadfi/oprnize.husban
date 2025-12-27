<x-filament-panels::page>
<div>
    <form wire:submit="create">
        {{ $this->form }}
    </form>

    <x-filament-actions::modals />
</div>
</x-filament-panels::page>

