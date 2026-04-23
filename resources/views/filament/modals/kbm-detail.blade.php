<div class="space-y-4 text-sm">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
        <div>
            <p class="text-gray-500">Tanggal</p>
            <p class="font-medium">{{ optional($kbm->date)->format('d M Y') }}</p>
        </div>
        <div>
            <p class="text-gray-500">Status</p>
            <p class="font-medium">{{ $kbm->status }}</p>
        </div>
        <div>
            <p class="text-gray-500">Guru</p>
            <p class="font-medium">{{ $kbm->schedule?->teacher?->user?->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-gray-500">Kelas</p>
            <p class="font-medium">{{ $kbm->schedule?->schoolClass?->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-gray-500">Mata Pelajaran</p>
            <p class="font-medium">{{ $kbm->schedule?->subject?->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-gray-500">RPP</p>
            <p class="font-medium">{{ $kbm->lessonPlan?->topic ?? '-' }}</p>
        </div>
    </div>

    <div>
        <p class="text-gray-500">Catatan Proses</p>
        <p class="font-medium">{{ $kbm->process_note }}</p>
    </div>

    <div>
        <p class="text-gray-500">Kendala</p>
        <p class="font-medium">{{ $kbm->problem_note ?: '-' }}</p>
    </div>

    <div>
        <p class="text-gray-500">Solusi / Tindak Lanjut</p>
        <p class="font-medium">{{ $kbm->solution_note ?: '-' }}</p>
    </div>

    <div>
        <p class="text-gray-500">Catatan Revisi</p>
        <p class="font-medium">{{ $kbm->revision_note ?: '-' }}</p>
    </div>

    @if (filled($kbm->documentation_path))
        <div>
            <p class="text-gray-500">Dokumentasi</p>
            <a
                href="{{ \Illuminate\Support\Facades\Storage::url($kbm->documentation_path) }}"
                target="_blank"
                class="text-primary-600 underline"
                rel="noopener noreferrer"
            >
                Buka dokumentasi
            </a>
        </div>
    @endif
</div>
