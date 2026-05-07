<x-filament-panels::page>
    <div class="space-y-6">
        @if ($this->hasMultipleLogFiles())
            <x-filament::section>
                <x-slot name="heading">
                    Pilih File Log
                </x-slot>

                {{ $this->form }}
            </x-filament::section>
        @endif

        {{ $this->table }}
    </div>
</x-filament-panels::page>
