{{-- Halaman 3: Capaian Pembelajaran + Keterangan Predikat --}}
{{-- Requirements: 1.2, 15.1-15.5, 16.1-16.3 --}}

<div class="page">

    {{-- Sub-task 8.1: Header --}}
    @include('rapor.partials._header')

    {{-- Sub-task 8.2: Tabel Capaian Pembelajaran (Requirements 15.1-15.5) --}}
    <div class="section-title" style="font-weight: bold; text-align: center; margin-top: 8px; margin-bottom: 4px;">
        CAPAIAN PEMBELAJARAN
    </div>

    <table>
        <thead>
            {{-- Header baris 1 (Requirement 15.2) --}}
            <tr>
                <th rowspan="2" style="text-align: center; vertical-align: middle; width: 20%;">Mata Pelajaran</th>
                <th rowspan="2" style="text-align: center; vertical-align: middle; width: 25%;">Pemaparan Materi</th>
                <th colspan="4" style="text-align: center;">Hasil Pembelajaran</th>
            </tr>
            {{-- Header baris 2 (Requirement 15.3) --}}
            <tr>
                <th style="text-align: center; width: 10%;">PH (Rata-rata)</th>
                <th style="text-align: center; width: 10%;">ATS</th>
                <th style="text-align: center; width: 10%;">SAS</th>
                <th style="text-align: center; width: 25%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($learningAchievements as $la)
                @php
                    // Cari data nilai untuk mata pelajaran ini dari $gradesBySubject
                    $subjectGrades = collect($gradesBySubject)->firstWhere('subject_name', $la->subject->name ?? '');

                    // Hitung rata-rata PH1-PH4 (Requirement 15.4)
                    $phValues = collect(['PH1', 'PH2', 'PH3', 'PH4'])
                        ->map(fn ($k) => $subjectGrades['grades'][$k] ?? null)
                        ->filter(fn ($v) => $v !== null && $v !== '—')
                        ->map(fn ($v) => (float) $v);
                    $phAvg = $phValues->isNotEmpty() ? round($phValues->avg(), 2) : '—';
                @endphp
                <tr>
                    <td>{{ $la->subject->name ?? '—' }}</td>
                    <td class="deskripsi-cell">{{ $la->material_coverage_status ?? $la->topic_coverage ?? '—' }}</td>
                    <td style="text-align: center;">{{ $la->daily_assessment_predicate ?? $phAvg }}</td>
                    <td style="text-align: center;">{{ $la->midterm_assessment_predicate ?? $subjectGrades['grades']['ATS'] ?? '—' }}</td>
                    <td style="text-align: center;">{{ $la->final_assessment_predicate ?? $subjectGrades['grades']['SAS'] ?? '—' }}</td>
                    <td class="deskripsi-cell">{{ $la->achievement_status ?? $la->notes ?? '—' }}</td>
                </tr>
            @empty
                {{-- Fallback jika kosong (Requirement 15.5) --}}
                <tr>
                    <td colspan="6" style="text-align: center;">Belum ada data capaian pembelajaran</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Sub-task 8.3: Tabel Keterangan Predikat (Requirements 16.1-16.3) --}}
    <div class="section-title" style="font-weight: bold; text-align: center; margin-top: 12px; margin-bottom: 4px;">
        KETERANGAN PREDIKAT
    </div>

    <table style="width: 50%;">
        <thead>
            <tr>
                <th style="text-align: center; width: 35%;">Nilai</th>
                <th style="text-align: center; width: 15%;">Huruf</th>
                <th style="text-align: center; width: 50%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;">86 - 100</td>
                <td style="text-align: center;">A</td>
                <td>Sangat Baik</td>
            </tr>
            <tr>
                <td style="text-align: center;">73 - 85</td>
                <td style="text-align: center;">B</td>
                <td>Baik</td>
            </tr>
            <tr>
                <td style="text-align: center;">60 - 72</td>
                <td style="text-align: center;">C</td>
                <td>Cukup</td>
            </tr>
            <tr>
                <td style="text-align: center;">&lt; 60</td>
                <td style="text-align: center;">D</td>
                <td>Kurang</td>
            </tr>
        </tbody>
    </table>

    {{-- Sub-task 8.1: Footer --}}
    @include('rapor.partials._footer_resmi')

</div>
