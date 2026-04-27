<x-filament-panels::page>

    <form class="space-y-6" wire:submit.prevent="submit">
        {{ $this->form }}

        <div class="flex items-center justify-between">
            <a
                href="{{ \App\Filament\Pages\ActiveTemporaryAccessList::getUrl() }}"
                class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400"
            >
                Lihat daftar akses yang sedang aktif →
            </a>

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
