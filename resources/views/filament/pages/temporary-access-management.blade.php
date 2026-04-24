<x-filament-panels::page>
    <form class="space-y-6" wire:submit.prevent="submit">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button
                color="primary"
                type="button"
                x-data
                x-on:click="if (confirm('Simpan pemberian akses sementara ini?')) { $wire.submit() }"
                :disabled="$this->isSaveDisabled()"
            >
                Simpan
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
