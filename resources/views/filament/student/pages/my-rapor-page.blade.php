<x-filament-panels::page>
    @if (!$hasStudentProfile)
        <x-filament::section icon="heroicon-o-information-circle">
            <x-slot name="heading">
                Profil siswa tidak ditemukan
            </x-slot>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                Data rapor tidak tersedia. Hubungi administrator untuk menghubungkan akun Anda dengan profil siswa.
            </p>
        </x-filament::section>
    @else
        {{ $this->table }}
    @endif
</x-filament-panels::page>
