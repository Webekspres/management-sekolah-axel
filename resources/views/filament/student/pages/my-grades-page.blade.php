<x-filament-panels::page>
    @if (!$hasStudentProfile)
        <x-filament::section icon="heroicon-o-information-circle">
            <x-slot name="heading">
                Profil siswa tidak ditemukan
            </x-slot>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                Data nilai tidak tersedia. Hubungi administrator untuk menghubungkan akun Anda dengan profil siswa.
            </p>
        </x-filament::section>
    @else
        @if ($activeAcademicYearName)
            <div class="mb-4 flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Tahun Akademik:</span>
                <x-filament::badge color="info">
                    {{ $activeAcademicYearName }}
                </x-filament::badge>
            </div>
        @endif

        {{ $this->table }}
    @endif
</x-filament-panels::page>
