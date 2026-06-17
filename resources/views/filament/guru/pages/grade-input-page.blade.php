<x-filament-panels::page>
    @php
        $scheduleModel = $this->resolveSchedule();
        $academicYear = $this->resolveAcademicYear();
        $subject = $scheduleModel?->subject?->name ?? '—';
        $class = $scheduleModel?->schoolClass?->name ?? '—';
        $studentCount = count($students);
    @endphp

    @if (!$hasActiveAcademicYear)
        <x-filament::section>
            <div class="py-12 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-warning-50 dark:bg-warning-900/20">
                    <x-filament::icon
                        icon="heroicon-o-exclamation-triangle"
                        class="h-6 w-6 text-warning-600 dark:text-warning-400"
                    />
                </div>
                <h3 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">Tidak Ada Tahun Akademik Aktif</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">Sesi input nilai memerlukan tahun akademik yang aktif. Silakan hubungi administrator.</p>
            </div>
        </x-filament::section>
    @elseif (empty($students))
        <x-filament::section>
            <div class="py-12 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-50 dark:bg-gray-800">
                    <x-filament::icon
                        icon="heroicon-o-users"
                        class="h-6 w-6 text-gray-400 dark:text-gray-500"
                    />
                </div>
                <h3 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">Tidak Ada Siswa</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Belum ada siswa terdaftar di kelas ini untuk tahun akademik yang aktif.</p>
            </div>
        </x-filament::section>
    @else
        <div x-data="{
            search: '',
            activeRow: null,
            activeCol: null,
            grades: @js($grades),
            
            calculateRapor(studentId) {
                const sGrades = this.grades[studentId];
                if (!sGrades) return null;

                // PH Average
                const phs = ['PH1', 'PH2', 'PH3', 'PH4']
                    .map(key => parseFloat(sGrades[key]))
                    .filter(val => !isNaN(val));
                const avgPh = phs.length > 0 ? phs.reduce((a, b) => a + b, 0) / phs.length : 0;

                // Tugas Average
                const tugas = ['TUGAS1', 'TUGAS2', 'TUGAS3', 'TUGAS4']
                    .map(key => parseFloat(sGrades[key]))
                    .filter(val => !isNaN(val));
                const avgTugas = tugas.length > 0 ? tugas.reduce((a, b) => a + b, 0) / tugas.length : 0;

                const ats = parseFloat(sGrades['ATS']) || 0;
                const sas = parseFloat(sGrades['SAS']) || 0;

                // Formula: (avgPh + avgTugas + ats + sas) / 4
                const score = (avgPh + avgTugas + ats + sas) / 4;
                return score > 0 ? score.toFixed(2) : null;
            },

            getPredicate(score) {
                if (!score) return null;
                const s = parseFloat(score);
                if (s >= 86) return { label: 'A', color: 'success' };
                if (s >= 73) return { label: 'B', color: 'info' };
                if (s >= 60) return { label: 'C', color: 'warning' };
                return { label: 'D', color: 'danger' };
            },

            handleKeydown(event, studentId, colIndex) {
                const inputs = Array.from(document.querySelectorAll('.grade-input'));
                const currentIndex = inputs.indexOf(event.target);
                const colsCount = 10; // PH1-4, TUGAS1-4, ATS, SAS

                if (event.key === 'ArrowRight') {
                    if (currentIndex < inputs.length - 1) inputs[currentIndex + 1].focus();
                } else if (event.key === 'ArrowLeft') {
                    if (currentIndex > 0) inputs[currentIndex - 1].focus();
                } else if (event.key === 'ArrowDown') {
                    if (currentIndex + colsCount < inputs.length) inputs[currentIndex + colsCount].focus();
                } else if (event.key === 'ArrowUp') {
                    if (currentIndex - colsCount >= 0) inputs[currentIndex - colsCount].focus();
                }
            },

            get progress() {
                let filled = 0;
                const total = Object.keys(this.grades).length;
                for (const id in this.grades) {
                    const g = this.grades[id];
                    // Consider filled if ATS and SAS are filled (minimal requirement for rapor usually)
                    if (g.ATS && g.SAS) filled++;
                }
                return Math.round((filled / total) * 100);
            }
        }" class="space-y-6">

            {{-- ── Header: Summary & Info ── --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Detail Card --}}
                <x-filament::section class="md:col-span-2">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
                                <x-filament::icon icon="heroicon-m-academic-cap" class="h-5 w-5 text-primary-600" />
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $subject }}</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $class }} &bull; {{ $academicYear?->name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 py-2 border-t border-gray-100 dark:border-gray-800">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-1">Total Siswa</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $studentCount }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-1">Tahun Ajaran</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $academicYear?->name }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-1">Progres Input (ATS & SAS)</p>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-primary-500 transition-all duration-500" :style="`width: ${progress}%`"></div>
                                </div>
                                <span class="text-xs font-bold text-primary-600 dark:text-primary-400" x-text="progress + '%'"></span>
                            </div>
                        </div>
                    </div>
                </x-filament::section>

                {{-- Quick Filters & Actions --}}
                <x-filament::section>
                    <div class="space-y-4">
                        <div>
                            <label for="search" class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 block">Cari Siswa</label>
                            <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                                <x-filament::input
                                    x-model="search"
                                    placeholder="Ketik nama siswa..."
                                    type="search"
                                />
                            </x-filament::input.wrapper>
                        </div>
                        <div class="pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <span class="text-[10px] text-gray-500 dark:text-gray-400 italic">Navigasi: &larr; &uarr; &rarr; &darr;</span>
                            <x-filament::button
                                wire:click="saveGrades"
                                wire:loading.attr="disabled"
                                icon="heroicon-m-check-circle"
                                size="sm"
                            >
                                Simpan
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            {{-- ── Main Input Table ── --}}
            <x-filament::section>
                <div class="overflow-x-auto -mx-6 px-6">
                    <table class="w-full text-sm text-left border-separate border-spacing-0">
                        <thead class="sticky top-0 z-30">
                            <tr class="bg-gray-50 dark:bg-gray-900">
                                <th rowspan="2" class="sticky left-0 z-40 px-4 py-4 font-bold text-gray-600 dark:text-gray-300 border-b-2 border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 min-w-[220px]">
                                    Nama Siswa
                                </th>
                                <th colspan="4" class="px-3 py-2 text-center font-bold text-amber-700 dark:text-amber-400 border-b border-r border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 uppercase tracking-widest text-[10px]">
                                    Penilaian Harian (PH)
                                </th>
                                <th colspan="4" class="px-3 py-2 text-center font-bold text-sky-700 dark:text-sky-400 border-b border-r border-sky-200 dark:border-sky-800 bg-sky-50 dark:bg-sky-900/20 uppercase tracking-widest text-[10px]">
                                    Tugas / PR
                                </th>
                                <th colspan="2" class="px-3 py-2 text-center font-bold text-violet-700 dark:text-violet-400 border-b border-r border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/20 uppercase tracking-widest text-[10px]">
                                    Asesmen
                                </th>
                                <th rowspan="2" class="px-3 py-4 text-center font-bold text-emerald-700 dark:text-emerald-400 border-b-2 border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 min-w-[120px] uppercase tracking-widest text-[10px]">
                                    Hasil Rapor
                                </th>
                            </tr>
                            <tr class="bg-gray-50/90 dark:bg-gray-900/90 backdrop-blur-sm">
                                @foreach(['1','2','3','4'] as $i)
                                    <th class="px-2 py-2 text-center text-[10px] font-black text-amber-600 dark:text-amber-500 border-b-2 border-r border-amber-100 dark:border-amber-900/30">PH{{$i}}</th>
                                @endforeach
                                @foreach(['1','2','3','4'] as $i)
                                    <th class="px-2 py-2 text-center text-[10px] font-black text-sky-600 dark:text-sky-500 border-b-2 border-r border-sky-100 dark:border-sky-900/30">T{{$i}}</th>
                                @endforeach
                                <th class="px-2 py-2 text-center text-[10px] font-black text-violet-600 dark:text-violet-500 border-b-2 border-r border-violet-100 dark:border-violet-900/30 uppercase tracking-tighter">ATS</th>
                                <th class="px-2 py-2 text-center text-[10px] font-black text-violet-600 dark:text-violet-500 border-b-2 border-r border-violet-100 dark:border-violet-900/30 uppercase tracking-tighter">SAS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($students as $index => $student)
                                <tr 
                                    x-show="search === '' || @js(strtolower($student->user?->name)).includes(search.toLowerCase())"
                                    x-on:mouseenter="activeRow = '{{ $student->id }}'"
                                    x-on:mouseleave="activeRow = null"
                                    class="group transition-colors duration-150"
                                    :class="activeRow === '{{ $student->id }}' ? 'bg-primary-50/30 dark:bg-primary-900/5' : ''"
                                >
                                    {{-- Student Name --}}
                                    <td class="sticky left-0 z-20 px-4 py-3 border-r border-gray-100 dark:border-gray-800 transition-colors"
                                        :class="activeRow === '{{ $student->id }}' ? 'bg-primary-50 dark:bg-primary-900/20' : '{{ $index % 2 === 0 ? 'bg-white dark:bg-gray-950' : 'bg-gray-50 dark:bg-gray-900' }}'">
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-gray-900 dark:text-white truncate" title="{{ $student->user?->name }}">
                                                {{ $student->user?->name }}
                                            </span>
                                            <span class="text-[10px] text-gray-400 dark:text-gray-500 font-mono tracking-tighter">
                                                {{ $student->nipd ?? 'NIPD —' }}
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Grade Inputs --}}
                                    @foreach (['PH1','PH2','PH3','PH4', 'TUGAS1','TUGAS2','TUGAS3','TUGAS4', 'ATS', 'SAS'] as $colIdx => $type)
                                        @php
                                            $isPh = str_contains($type, 'PH');
                                            $isTugas = str_contains($type, 'TUGAS');
                                            $isAssessment = in_array($type, ['ATS', 'SAS']);
                                        @endphp
                                        <td class="px-1.5 py-2 border-r border-gray-100 dark:border-gray-800 text-center transition-colors"
                                            :class="{
                                                'bg-amber-50/30 dark:bg-amber-900/10': activeCol === '{{ $type }}' && {{ $isPh ? 'true' : 'false' }},
                                                'bg-sky-50/30 dark:bg-sky-900/10': activeCol === '{{ $type }}' && {{ $isTugas ? 'true' : 'false' }},
                                                'bg-violet-50/30 dark:bg-violet-900/10': activeCol === '{{ $type }}' && {{ $isAssessment ? 'true' : 'false' }},
                                            }">
                                            <div class="relative group/input">
                                                <input
                                                    type="number"
                                                    min="0"
                                                    max="100"
                                                    step="0.01"
                                                    x-model="grades['{{ $student->id }}']['{{ $type }}']"
                                                    x-on:focus="activeCol = '{{ $type }}'; activeRow = '{{ $student->id }}'"
                                                    x-on:blur="activeCol = null"
                                                    x-on:keydown="handleKeydown($event, '{{ $student->id }}', {{ $colIdx }})"
                                                    wire:model.blur="grades.{{ $student->id }}.{{ $type }}"
                                                    placeholder="—"
                                                    class="grade-input w-full bg-transparent border-0 border-b-2 border-transparent text-center p-1 text-sm font-medium text-gray-700 dark:text-gray-300 transition-all placeholder:text-gray-300 dark:placeholder:text-gray-700
                                                    {{ $isPh ? 'focus:border-amber-500' : '' }}
                                                    {{ $isTugas ? 'focus:border-sky-500' : '' }}
                                                    {{ $isAssessment ? 'focus:border-violet-500' : '' }}
                                                    focus:ring-0"
                                                />
                                            </div>
                                        </td>
                                    @endforeach

                                    {{-- Rapor Result --}}
                                    <td class="px-3 py-2 text-center border-emerald-50 dark:border-emerald-900/20 bg-emerald-50/10 dark:bg-emerald-900/5 transition-colors"
                                        :class="activeRow === '{{ $student->id }}' ? 'bg-emerald-50 dark:bg-emerald-900/20' : ''">
                                        <template x-if="calculateRapor('{{ $student->id }}')">
                                            <div class="flex flex-col items-center gap-1">
                                                <span class="text-sm font-black text-emerald-600 dark:text-emerald-400" x-text="calculateRapor('{{ $student->id }}')"></span>
                                                <template x-if="getPredicate(calculateRapor('{{ $student->id }}'))">
                                                    <span class="text-[9px] px-1.5 py-0.5 rounded-full font-bold uppercase tracking-widest shadow-sm"
                                                        :class="{
                                                            'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400': getPredicate(calculateRapor('{{ $student->id }}')).color === 'success',
                                                            'bg-info-100 text-info-700 dark:bg-info-900/30 dark:text-info-400': getPredicate(calculateRapor('{{ $student->id }}')).color === 'info',
                                                            'bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400': getPredicate(calculateRapor('{{ $student->id }}')).color === 'warning',
                                                            'bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400': getPredicate(calculateRapor('{{ $student->id }}')).color === 'danger',
                                                        }"
                                                        x-text="getPredicate(calculateRapor('{{ $student->id }}')).label">
                                                    </span>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!calculateRapor('{{ $student->id }}')">
                                            <span class="text-gray-300 dark:text-gray-700 font-mono text-xs">—</span>
                                        </template>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>

            {{-- ── Legend & Footer ── --}}
            <div class="flex flex-col md:flex-row gap-4 items-center justify-between px-4 py-2 bg-gray-50/50 dark:bg-gray-900/20 rounded-xl border border-gray-100 dark:border-gray-800">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-success-500"></div>
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-tighter">A: 86-100</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-info-500"></div>
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-tighter">B: 73-85</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-warning-500"></div>
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-tighter">C: 60-72</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-danger-500"></div>
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-tighter">D: &lt; 60</span>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-400 font-medium italic">Data otomatis tersimpan di memory lokal saat mengetik</span>
                    <x-filament::button
                        wire:click="saveGrades"
                        wire:loading.attr="disabled"
                        color="primary"
                        icon="heroicon-m-check-badge"
                    >
                        Selesaikan & Simpan ke Server
                    </x-filament::button>
                </div>
            </div>

        </div>
    @endif


    <style>
        /* Hide arrows in number inputs */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] {
            -moz-appearance: textfield;
        }
        
        /* Smooth transitions for sticky elements */
        .sticky {
            transition: background-color 0.2s ease;
        }
    </style>
</x-filament-panels::page>

