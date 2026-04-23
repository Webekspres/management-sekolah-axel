<div class="space-y-4 text-sm">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
        <div>
            <p class="text-gray-500">Guru</p>
            <p class="font-medium">{{ $lessonPlan->teacher?->user?->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-gray-500">Mata Pelajaran</p>
            <p class="font-medium">{{ $lessonPlan->subject?->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-gray-500">Topik</p>
            <p class="font-medium">{{ $lessonPlan->topic }}</p>
        </div>
        <div>
            <p class="text-gray-500">Status</p>
            <p class="font-medium">{{ $lessonPlan->status }}</p>
        </div>
    </div>

    <div>
        <p class="text-gray-500">Catatan Revisi</p>
        <p class="font-medium">{{ $lessonPlan->revision_note ?: '-' }}</p>
    </div>

    <div>
        <p class="text-gray-500">File RPP</p>
        <a
            href="{{ \Illuminate\Support\Facades\Storage::url($lessonPlan->file_path) }}"
            target="_blank"
            class="text-primary-600 underline"
            rel="noopener noreferrer"
        >
            Buka file RPP
        </a>
    </div>
</div>
