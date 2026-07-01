<x-filament-panels::page>
    <x-filament-panels::page.header.actions
        :actions="$this->getHeaderActions()"
    />

    {{ $this->table }}
</x-filament-panels::page>
