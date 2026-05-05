<x-filament-panels::page>
    @if (!$hasStudentProfile)
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400">
            <x-filament::icon icon="heroicon-o-information-circle" class="mx-auto mb-3 h-10 w-10 text-gray-400" />
            <p class="text-base font-medium">Profil siswa tidak ditemukan</p>
            <p class="mt-1 text-sm">Data nilai tidak tersedia. Hubungi administrator untuk menghubungkan akun Anda dengan profil siswa.</p>
        </div>
    @elseif (empty($gradesBySubject))
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400">
            <x-filament::icon icon="heroicon-o-clipboard-document-list" class="mx-auto mb-3 h-10 w-10 text-gray-400" />
            <p class="text-base font-medium">Belum ada nilai</p>
            <p class="mt-1 text-sm">Nilai untuk tahun akademik aktif ({{ $activeAcademicYearName ?? '—' }}) belum tersedia.</p>
        </div>
    @else
        <div class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            Tahun Akademik: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $activeAcademicYearName }}</span>
        </div>

        <div class="space-y-4">
            @foreach ($gradesBySubject as $item)
                <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                    <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-100">{{ $item['subject_name'] }}</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    @foreach (['PH1','PH2','PH3','PH4','TUGAS1','TUGAS2','TUGAS3','TUGAS4','ATS','SAS','RAPOR'] as $type)
                                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 {{ $type === 'RAPOR' ? 'text-primary-600 dark:text-primary-400' : '' }}">
                                            {{ $type }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="bg-white dark:bg-gray-900">
                                    @foreach (['PH1','PH2','PH3','PH4','TUGAS1','TUGAS2','TUGAS3','TUGAS4','ATS','SAS','RAPOR'] as $type)
                                        <td class="px-3 py-2 text-center {{ $type === 'RAPOR' ? 'font-semibold text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300' }}">
                                            {{ isset($item['grades'][$type]) ? number_format((float) $item['grades'][$type], 2) : '—' }}
                                        </td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
