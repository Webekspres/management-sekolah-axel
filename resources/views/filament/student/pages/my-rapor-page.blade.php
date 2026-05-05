<x-filament-panels::page>
    @if (!$hasStudentProfile)
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400">
            <x-filament::icon icon="heroicon-o-information-circle" class="mx-auto mb-3 h-10 w-10 text-gray-400" />
            <p class="text-base font-medium">Profil siswa tidak ditemukan</p>
            <p class="mt-1 text-sm">Data rapor tidak tersedia. Hubungi administrator.</p>
        </div>
    @elseif ($rapors->isEmpty())
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400">
            <x-filament::icon icon="heroicon-o-document-text" class="mx-auto mb-3 h-10 w-10 text-gray-400" />
            <p class="text-base font-medium">Belum ada rapor</p>
            <p class="mt-1 text-sm">Rapor Anda belum tersedia.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Tahun Akademik</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($rapors as $rapor)
                        <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                                {{ $rapor->academicYear?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($rapor->isApproved())
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        Disetujui
                                    </span>
                                @elseif ($rapor->isFinalized())
                                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                        Terfinalisasi
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                        Draft
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($rapor->isApproved() && $rapor->file_path)
                                    <x-filament::button
                                        wire:click="downloadRapor('{{ $rapor->id }}')"
                                        size="sm"
                                        icon="heroicon-o-arrow-down-tray"
                                        color="primary"
                                    >
                                        Download
                                    </x-filament::button>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>
