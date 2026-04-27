<x-filament-panels::page>

    {{-- Form pemberian akses --}}
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

    {{-- Tabel akses aktif --}}
    @php $activeAbilities = $this->getActiveAbilities(); @endphp

    <div class="mt-8">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Akses Sementara Aktif
            <span class="ml-2 text-sm font-normal text-gray-500">({{ $activeAbilities->count() }} aktif)</span>
        </h2>

        @if($activeAbilities->isEmpty())
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 text-center text-sm text-gray-500">
                Tidak ada akses sementara yang aktif saat ini.
            </div>
        @else
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase">
                        <tr>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">Policy</th>
                            <th class="px-4 py-3">Ability</th>
                            <th class="px-4 py-3">Jenjang</th>
                            <th class="px-4 py-3">Berakhir</th>
                            <th class="px-4 py-3">Diberikan Oleh</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-900">
                        @foreach($activeAbilities as $ability)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $ability->user?->name ?? '-' }}
                                    <div class="text-xs text-gray-400">{{ $ability->user?->email }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $ability->accessPolicy?->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-md bg-primary-50 dark:bg-primary-900/20 px-2 py-1 text-xs font-medium text-primary-700 dark:text-primary-400">
                                        {{ $ability->ability }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">
                                    {{ $ability->level?->name ?? 'Semua jenjang' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">
                                    @if($ability->expires_at)
                                        <span class="{{ $ability->expires_at->diffInHours(now()) < 24 ? 'text-danger-600 dark:text-danger-400 font-medium' : '' }}">
                                            {{ $ability->expires_at->format('d M Y H:i') }}
                                            <div class="text-gray-400">({{ $ability->expires_at->diffForHumans() }})</div>
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">
                                    {{ $ability->grantedBy?->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <x-filament::button
                                        color="danger"
                                        size="xs"
                                        x-data
                                        x-on:click="if (confirm('Cabut akses ini?')) { $wire.revokeAbility('{{ $ability->id }}') }"
                                    >
                                        Cabut
                                    </x-filament::button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</x-filament-panels::page>
