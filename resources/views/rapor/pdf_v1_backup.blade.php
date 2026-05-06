<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapor - {{ $student->user?->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #000; }
        .page { width: 100%; padding: 15mm; page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        h1 { font-size: 14px; text-align: center; margin-bottom: 4px; }
        h2 { font-size: 12px; text-align: center; margin-bottom: 10px; }
        .header-info { margin-bottom: 12px; }
        .header-info table { width: 100%; }
        .header-info td { padding: 2px 4px; }
        .header-info td:first-child { width: 140px; font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.data th, table.data td { border: 1px solid #000; padding: 3px 5px; text-align: center; font-size: 9px; }
        table.data th { background-color: #e0e0e0; font-weight: bold; }
        table.data td.left { text-align: left; }
        .section-title { font-weight: bold; font-size: 11px; margin: 10px 0 5px; border-bottom: 1px solid #000; padding-bottom: 2px; }
        .signature-row { display: flex; justify-content: space-between; margin-top: 20px; }
        .signature-box { text-align: center; width: 30%; }
        .signature-box .line { border-bottom: 1px solid #000; margin: 40px 10px 5px; }
        .predicate-legend { font-size: 9px; margin-top: 8px; }
        .predicate-legend span { margin-right: 12px; }
        .below-kkm { color: #cc0000; font-weight: bold; }
    </style>
</head>
<body>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- HALAMAN 1: Data Absensi & Daftar Nilai --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div class="page">
    <h1>LAPORAN HASIL BELAJAR SISWA</h1>
    <h2>{{ $schoolName ?? 'Homeschooling Tunas Karya Bangsa' }}</h2>

    <div class="header-info">
        <table>
            <tr>
                <td>Nama Siswa</td>
                <td>: {{ $student->user?->name ?? '—' }}</td>
                <td>Kelas</td>
                <td>: {{ $schoolClass?->name ?? '—' }}</td>
            </tr>
            <tr>
                <td>NIS/NISN</td>
                <td>: {{ $student->nipd ?? '—' }} / {{ $student->nisn ?? '—' }}</td>
                <td>Semester</td>
                <td>: {{ $academicYear->semester ?? '—' }}</td>
            </tr>
            <tr>
                <td>Tahun Pembelajaran</td>
                <td>: {{ $academicYear->name ?? '—' }}</td>
                <td>Program</td>
                <td>: Homeschooling</td>
            </tr>
        </table>
    </div>

    {{-- Tabel Absensi per Mapel per Bulan --}}
    <div class="section-title">Data Absensi</div>
    <table class="data">
        <thead>
            <tr>
                <th rowspan="2" class="left">Mata Pelajaran</th>
                @foreach ($semesterMonths as $month)
                    <th>{{ $monthNames[$month] ?? $month }}</th>
                @endforeach
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attendanceBySubject as $subjectId => $subjectData)
                <tr>
                    <td class="left">{{ $subjectData['subject_name'] }}</td>
                    @foreach ($semesterMonths as $month)
                        <td>{{ $subjectData['months'][$month]['total'] ?? 0 }}</td>
                    @endforeach
                    <td>{{ $subjectData['total'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($semesterMonths) + 2 }}" style="text-align:center">Tidak ada data absensi</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Tabel Daftar Nilai --}}
    <div class="section-title">Daftar Nilai</div>
    <table class="data">
        <thead>
            <tr>
                <th class="left">Mata Pelajaran</th>
                <th>PH1</th><th>PH2</th><th>PH3</th><th>PH4</th>
                <th>T1</th><th>T2</th><th>T3</th><th>T4</th>
                <th>ATS</th><th>SAS</th>
                <th>Nilai Rapor</th>
                <th>Guru Bidang Studi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($gradesBySubject as $subjectId => $subjectData)
                <tr>
                    <td class="left">{{ $subjectData['subject_name'] }}</td>
                    @foreach (['PH1','PH2','PH3','PH4','TUGAS1','TUGAS2','TUGAS3','TUGAS4','ATS','SAS'] as $type)
                        <td>{{ isset($subjectData['grades'][$type]) ? number_format((float)$subjectData['grades'][$type], 2) : '—' }}</td>
                    @endforeach
                    <td><strong>{{ isset($subjectData['grades']['RAPOR']) ? number_format((float)$subjectData['grades']['RAPOR'], 2) : '—' }}</strong></td>
                    <td>{{ $subjectData['teacher_name'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" style="text-align:center">Tidak ada data nilai</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- HALAMAN 2: Laporan Hasil Belajar --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div class="page">
    <h1>LAPORAN HASIL BELAJAR SISWA</h1>
    <h2>Halaman 2 — Penilaian Sikap, Pengetahuan & Keterampilan</h2>

    <div class="header-info">
        <table>
            <tr>
                <td>Nama Siswa</td>
                <td>: {{ $student->user?->name ?? '—' }}</td>
                <td>Kelas</td>
                <td>: {{ $schoolClass?->name ?? '—' }}</td>
            </tr>
        </table>
    </div>

    {{-- Nilai Sikap --}}
    <div class="section-title">Nilai Sikap</div>
    <table class="data">
        <thead>
            <tr>
                <th class="left">Aspek</th>
                <th>Nilai</th>
                <th class="left">Deskripsi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attitudeScores as $score)
                <tr>
                    <td class="left">{{ $score->aspect }}</td>
                    <td>{{ number_format((float)$score->score, 2) }}</td>
                    <td class="left">{{ $score->description ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="3" style="text-align:center">Belum ada data</td></tr>
            @endforelse
            @if ($attitudeScores->isNotEmpty())
                <tr>
                    <td class="left"><strong>Rata-rata</strong></td>
                    <td><strong>{{ number_format($attitudeScores->avg('score'), 2) }}</strong></td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- Nilai Pengetahuan & Keterampilan --}}
    <div class="section-title">Nilai Pengetahuan & Keterampilan</div>
    <table class="data">
        <thead>
            <tr>
                <th class="left">Mata Pelajaran</th>
                <th>KKM</th>
                <th>Nilai P</th><th>Pred P</th>
                <th>Nilai K</th><th>Pred K</th>
                <th class="left">Deskripsi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($knowledgeSkillScores as $ks)
                <tr>
                    <td class="left">{{ $ks->subject?->name ?? '—' }}</td>
                    <td>{{ $ks->kkm ?? 70 }}</td>
                    <td class="{{ $ks->knowledge_score !== null && (float)$ks->knowledge_score < ($ks->kkm ?? 70) ? 'below-kkm' : '' }}">
                        {{ $ks->knowledge_score !== null ? number_format((float)$ks->knowledge_score, 2) : '—' }}
                    </td>
                    <td>{{ $ks->knowledge_predicate ?? '—' }}</td>
                    <td class="{{ $ks->skill_score !== null && (float)$ks->skill_score < ($ks->kkm ?? 70) ? 'below-kkm' : '' }}">
                        {{ $ks->skill_score !== null ? number_format((float)$ks->skill_score, 2) : '—' }}
                    </td>
                    <td>{{ $ks->skill_predicate ?? '—' }}</td>
                    <td class="left">{{ $ks->knowledge_description ?? $ks->skill_description ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center">Belum ada data</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Rekap Absensi --}}
    <div class="section-title">Rekap Absensi</div>
    <table class="data" style="width:50%">
        <tr><th>Sakit</th><th>Izin</th><th>Alpa</th><th>Total</th></tr>
        <tr>
            <td>{{ $overallAttendance['sakit'] ?? 0 }}</td>
            <td>{{ $overallAttendance['izin'] ?? 0 }}</td>
            <td>{{ $overallAttendance['alpa'] ?? 0 }}</td>
            <td>{{ $overallAttendance['total'] ?? 0 }}</td>
        </tr>
    </table>

    {{-- Kepribadian --}}
    <div class="section-title">Kepribadian</div>
    @if ($personalityScore)
        <table class="data" style="width:60%">
            <tr>
                <th>Kedisiplinan</th><th>Kerapihan</th><th>Kerajinan</th><th>Kesopanan</th>
            </tr>
            <tr>
                <td>{{ $personalityScore->kedisiplinan }}</td>
                <td>{{ $personalityScore->kerapihan }}</td>
                <td>{{ $personalityScore->kerajinan }}</td>
                <td>{{ $personalityScore->kesopanan }}</td>
            </tr>
        </table>
    @else
        <p style="color:#666">Belum ada data kepribadian.</p>
    @endif

    {{-- TTD --}}
    <div class="signature-row" style="margin-top:30px; display:table; width:100%">
        <div style="display:table-cell; width:33%; text-align:center">
            <p>Orang Tua / Wali</p>
            <div style="height:50px"></div>
            <div style="border-bottom:1px solid #000; margin:0 20px"></div>
            <p>( __________________ )</p>
        </div>
        <div style="display:table-cell; width:33%; text-align:center">
            <p>Wali Kelas</p>
            <div style="height:50px"></div>
            <div style="border-bottom:1px solid #000; margin:0 20px"></div>
            <p>{{ $waliKelasName ?? '( __________________ )' }}</p>
        </div>
        <div style="display:table-cell; width:33%; text-align:center">
            <p>Kepala Sekolah</p>
            <div style="height:50px"></div>
            <div style="border-bottom:1px solid #000; margin:0 20px"></div>
            <p>( __________________ )</p>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- HALAMAN 3: Capaian Pembelajaran --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div class="page">
    <h1>LAPORAN HASIL BELAJAR SISWA</h1>
    <h2>Halaman 3 — Capaian Pembelajaran</h2>

    <div class="header-info">
        <table>
            <tr>
                <td>Nama Siswa</td>
                <td>: {{ $student->user?->name ?? '—' }}</td>
                <td>Kelas</td>
                <td>: {{ $schoolClass?->name ?? '—' }}</td>
            </tr>
        </table>
    </div>

    <div class="section-title">Capaian Pembelajaran</div>
    <table class="data">
        <thead>
            <tr>
                <th class="left">Mata Pelajaran</th>
                <th class="left">Pemaparan Materi</th>
                <th>Rata-rata PH</th>
                <th>ATS</th>
                <th>SAS</th>
                <th class="left">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($learningAchievements as $la)
                <tr>
                    <td class="left">{{ $la->subject?->name ?? '—' }}</td>
                    <td class="left">{{ $la->topic_coverage ?? '—' }}</td>
                    <td>{{ isset($gradesBySubject[$la->subject_id]['grades']) ? (collect($gradesBySubject[$la->subject_id]['grades'])->only(['PH1','PH2','PH3','PH4'])->filter()->avg() ? number_format(collect($gradesBySubject[$la->subject_id]['grades'])->only(['PH1','PH2','PH3','PH4'])->filter()->avg(), 2) : '—') : '—' }}</td>
                    <td>{{ isset($gradesBySubject[$la->subject_id]['grades']['ATS']) ? number_format((float)$gradesBySubject[$la->subject_id]['grades']['ATS'], 2) : '—' }}</td>
                    <td>{{ isset($gradesBySubject[$la->subject_id]['grades']['SAS']) ? number_format((float)$gradesBySubject[$la->subject_id]['grades']['SAS'], 2) : '—' }}</td>
                    <td class="left">{{ $la->notes ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center">Belum ada data capaian pembelajaran</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="predicate-legend">
        <strong>Keterangan Predikat:</strong>
        <span>A = 86–100 (Sangat Baik)</span>
        <span>B = 73–85 (Baik)</span>
        <span>C = 60–72 (Cukup)</span>
        <span>D = &lt;60 (Kurang)</span>
    </div>

    {{-- TTD --}}
    <div style="margin-top:30px; display:table; width:100%">
        <div style="display:table-cell; width:50%; text-align:center">
            <p>Ketua Litbang HS-TKB</p>
            <div style="height:50px"></div>
            <div style="border-bottom:1px solid #000; margin:0 40px"></div>
            <p>( __________________ )</p>
        </div>
        <div style="display:table-cell; width:50%; text-align:center">
            <p>Wali Kelas</p>
            <div style="height:50px"></div>
            <div style="border-bottom:1px solid #000; margin:0 40px"></div>
            <p>{{ $waliKelasName ?? '( __________________ )' }}</p>
        </div>
    </div>
</div>

</body>
</html>
