{{-- Halaman 1: Data Absensi + Daftar Nilai --}}
<div class="page">

    @include('rapor.partials._header')

    {{-- Identitas 2 kolom (satu tabel datar — DomPDF gagal pada tabel bersarang) --}}
    <table style="border: none; margin-bottom: 4px; width: 100%;">
        <tr>
            <td style="border: none;" class="identitas-label">Nama</td>
            <td style="border: none;" class="identitas-sep">:</td>
            <td style="border: none;" class="identitas-val">{{ $student->user?->name ?? '' }}</td>
            <td style="border: none;" class="identitas-label">Kelas</td>
            <td style="border: none;" class="identitas-sep">:</td>
            <td style="border: none;" class="identitas-val">{{ $schoolClass->name ?? '' }}</td>
        </tr>
        <tr>
            <td style="border: none;" class="identitas-label">NIS/NISN</td>
            <td style="border: none;" class="identitas-sep">:</td>
            <td style="border: none;" class="identitas-val">{{ $student->nis ?? '' }} / {{ $student->nisn ?? '' }}</td>
            <td style="border: none;" class="identitas-label">Semester</td>
            <td style="border: none;" class="identitas-sep">:</td>
            <td style="border: none;" class="identitas-val">{{ $academicYear->semester ?? '' }}</td>
        </tr>
        <tr>
            <td style="border: none;" class="identitas-label">Program</td>
            <td style="border: none;" class="identitas-sep">:</td>
            <td style="border: none;" class="identitas-val">{{ $rapor->program ?? '' }}</td>
            <td style="border: none;" class="identitas-label">Tahun Pembelajaran</td>
            <td style="border: none;" class="identitas-sep">:</td>
            <td style="border: none;" class="identitas-val">{{ $academicYear->name ?? '' }}</td>
        </tr>
        <tr>
            <td style="border: none;" class="identitas-label">Sumber Pembelajaran</td>
            <td style="border: none;" class="identitas-sep">:</td>
            <td style="border: none;" class="identitas-val" colspan="4">{{ $rapor->sumber_pembelajaran ?? '' }}</td>
        </tr>
    </table>

    {{-- DATA ABSENSI --}}
    <div class="section-title">DATA ABSENSI PESERTA DIDIK</div>

    @php
        $bulanPenuh = [
            1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',
            5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',
            9=>'September',10=>'Oktober',11=>'November',12=>'Desember',
        ];
        $totalAbsensiCols = 2 + count($semesterMonths);
    @endphp

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 22%; vertical-align: middle;">Mata Pelajaran</th>
                <th colspan="{{ count($semesterMonths) }}" style="text-align: center;">Jumlah Sesi Pada Bulan</th>
                <th rowspan="2" style="width: 9%; vertical-align: middle;">Total Sesi</th>
            </tr>
            <tr>
                @foreach ($semesterMonths as $month)
                    <th>{{ $bulanPenuh[$month] ?? ($monthNames[$month] ?? $month) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($attendanceBySubject as $subjectId => $subjectData)
                <tr>
                    <td>{{ $subjectData['subject_name'] }}</td>
                    @foreach ($semesterMonths as $month)
                        <td style="text-align: center;">{{ $subjectData['months'][$month]['total'] ?? '' }}</td>
                    @endforeach
                    <td style="text-align: center;">{{ $subjectData['total'] ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $totalAbsensiCols }}" style="text-align: center;">Tidak ada data absensi</td>
                </tr>
            @endforelse
            {{-- Baris JUMLAH --}}
            @if ($attendanceBySubject->isNotEmpty())
                @php
                    $jumlahPerBulan = [];
                    foreach ($semesterMonths as $m) {
                        $jumlahPerBulan[$m] = collect($attendanceBySubject)->sum(fn($d) => $d['months'][$m]['total'] ?? 0);
                    }
                    $jumlahTotal = collect($attendanceBySubject)->sum('total');
                @endphp
                <tr>
                    <td style="font-weight: bold;">JUMLAH</td>
                    @foreach ($semesterMonths as $month)
                        <td style="text-align: center; font-weight: bold;">{{ $jumlahPerBulan[$month] ?: '' }}</td>
                    @endforeach
                    <td style="text-align: center; font-weight: bold;">{{ $jumlahTotal ?: '' }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- DAFTAR NILAI --}}
    <div class="section-title" style="margin-top: 6px;">DAFTAR NILAI PESERTA DIDIK</div>

    @php
        $fmt = fn($v) => ($v !== null && $v !== '—' && $v !== '')
            ? rtrim(rtrim(number_format((float)$v, 2), '0'), '.')
            : '';
        $totalNilaiCols = 12;
    @endphp

    <table style="font-size: 8pt;">
        <thead>
            <tr>
                <th rowspan="2" style="width: 18%; vertical-align: middle;">Mata Pelajaran</th>
                <th colspan="4" style="text-align: center;">Penilaian Harian</th>
                <th colspan="4" style="text-align: center;">Tugas/PR</th>
                <th rowspan="2" style="width: 5%; vertical-align: middle;">ATS</th>
                <th rowspan="2" style="width: 5%; vertical-align: middle;">SAS</th>
                <th rowspan="2" style="width: 7%; vertical-align: middle;">Nilai Rapor</th>
                <th rowspan="2" style="width: 13%; vertical-align: middle;">Guru Bidang Studi</th>
            </tr>
            <tr>
                <th style="width: 5%;">1</th>
                <th style="width: 5%;">2</th>
                <th style="width: 5%;">3</th>
                <th style="width: 5%;">4</th>
                <th style="width: 5%;">1</th>
                <th style="width: 5%;">2</th>
                <th style="width: 5%;">3</th>
                <th style="width: 5%;">4</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($gradesBySubject as $subjectId => $subjectData)
                <tr>
                    <td>{{ $subjectData['subject_name'] }}</td>
                    <td style="text-align: center;">{{ $fmt($subjectData['grades']['PH1'] ?? null) }}</td>
                    <td style="text-align: center;">{{ $fmt($subjectData['grades']['PH2'] ?? null) }}</td>
                    <td style="text-align: center;">{{ $fmt($subjectData['grades']['PH3'] ?? null) }}</td>
                    <td style="text-align: center;">{{ $fmt($subjectData['grades']['PH4'] ?? null) }}</td>
                    <td style="text-align: center;">{{ $fmt($subjectData['grades']['TUGAS1'] ?? null) }}</td>
                    <td style="text-align: center;">{{ $fmt($subjectData['grades']['TUGAS2'] ?? null) }}</td>
                    <td style="text-align: center;">{{ $fmt($subjectData['grades']['TUGAS3'] ?? null) }}</td>
                    <td style="text-align: center;">{{ $fmt($subjectData['grades']['TUGAS4'] ?? null) }}</td>
                    <td style="text-align: center;">{{ $fmt($subjectData['grades']['ATS'] ?? null) }}</td>
                    <td style="text-align: center;">{{ $fmt($subjectData['grades']['SAS'] ?? null) }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ $fmt($subjectData['grades']['RAPOR'] ?? null) }}</td>
                    <td style="font-size: 7.5pt;">{{ $subjectData['teacher_name'] ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" style="text-align: center;">Belum ada data nilai</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('rapor.partials._footer_resmi')

</div>
