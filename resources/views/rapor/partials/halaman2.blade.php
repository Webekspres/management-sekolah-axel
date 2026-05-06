{{-- Halaman 2: Nilai Sikap + Pengetahuan & Keterampilan + Absensi + Kepribadian --}}
<div class="page">

    @include('rapor.partials._header')

    {{-- Sub-judul --}}
    <div class="sub-header-center">LAPORAN HASIL BELAJAR  SISWA</div>

    {{-- Identitas 1 kolom kiri --}}
    <table style="border: none; width: 55%; margin-bottom: 4px;">
        <tr>
            <td style="border: none;" class="identitas-label">Nama Siswa</td>
            <td style="border: none;" class="identitas-sep">:</td>
            <td style="border: none;" class="identitas-val">{{ $student->user?->name ?? '' }}</td>
        </tr>
        <tr>
            <td style="border: none;" class="identitas-label">NIS/NISN</td>
            <td style="border: none;" class="identitas-sep">:</td>
            <td style="border: none;" class="identitas-val">{{ $student->nis ?? '' }} / {{ $student->nisn ?? '' }}</td>
        </tr>
        <tr>
            <td style="border: none;" class="identitas-label">Kelas</td>
            <td style="border: none;" class="identitas-sep">:</td>
            <td style="border: none;" class="identitas-val">{{ $schoolClass->name ?? '' }}</td>
        </tr>
        <tr>
            <td style="border: none;" class="identitas-label">Semester</td>
            <td style="border: none;" class="identitas-sep">:</td>
            <td style="border: none;" class="identitas-val">{{ $academicYear->semester ?? '' }}</td>
        </tr>
        <tr>
            <td style="border: none;" class="identitas-label">Tahun Pelajaran</td>
            <td style="border: none;" class="identitas-sep">:</td>
            <td style="border: none;" class="identitas-val">{{ $academicYear->year ?? '' }}</td>
        </tr>
        <tr>
            <td style="border: none;" class="identitas-label">Program</td>
            <td style="border: none;" class="identitas-sep">:</td>
            <td style="border: none;" class="identitas-val">{{ $student->program ?? '' }}</td>
        </tr>
    </table>

    {{-- A. NILAI SIKAP --}}
    <div class="section-title">A. NILAI SIKAP</div>

    @php
        $avgSikap = $attitudeScores->isNotEmpty() ? round($attitudeScores->avg('score'), 2) : 0;
        $predikatSikap = match(true) {
            $avgSikap >= 86 => 'A',
            $avgSikap >= 73 => 'B',
            $avgSikap >= 60 => 'C',
            default         => 'D',
        };
        $labelSikap = match($predikatSikap) {
            'A' => 'SANGAT BAIK',
            'B' => 'BAIK',
            'C' => 'CUKUP',
            default => 'KURANG',
        };
    @endphp

    <table>
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th style="width: 28%;">Aspek Penilaian</th>
                <th>Deskripsi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attitudeScores as $i => $score)
                <tr style="height: 18px;">
                    <td style="text-align: center;">{{ $i + 1 }}</td>
                    <td>{{ $score->aspect }}</td>
                    <td class="sikap-desk">{{ $score->description }}</td>
                </tr>
            @empty
                <tr style="height: 18px;">
                    <td style="text-align: center;">1</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr style="height: 18px;">
                    <td style="text-align: center;">2</td>
                    <td></td>
                    <td></td>
                </tr>
            @endforelse
            <tr>
                <td colspan="2" style="font-weight: bold;">Rata-Rata Nilai Sikap</td>
                <td style="text-align: center; font-weight: bold;">{{ $attitudeScores->isNotEmpty() ? $labelSikap : '' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- B. NILAI PENGETAHUAN DAN KETERAMPILAN --}}
    <div class="section-title" style="margin-top: 5px;">B.&nbsp;&nbsp; NILAI PENGETAHUAN DAN KETERAMPILAN</div>

    <table style="font-size: 8.5pt;">
        <thead>
            <tr>
                <th rowspan="2" style="width: 5%; vertical-align: middle;">No</th>
                <th rowspan="2" style="width: 18%; vertical-align: middle;">Mata Pelajaran</th>
                <th rowspan="2" style="width: 5%; vertical-align: middle;">KKM</th>
                <th colspan="3" style="text-align: center;">Pengetahuan</th>
                <th colspan="3" style="text-align: center;">Keterampilan</th>
            </tr>
            <tr>
                <th style="width: 5%;">Nilai</th>
                <th style="width: 7%;">Predikat</th>
                <th>Deskripsi</th>
                <th style="width: 5%;">Nilai</th>
                <th style="width: 7%;">Predikat</th>
                <th>Deskripsi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($knowledgeSkillScores as $i => $ks)
                <tr style="height: 22px;">
                    <td style="text-align: center;">{{ $i + 1 }}</td>
                    <td>{{ $ks->subject->name ?? '' }}</td>
                    <td style="text-align: center;">{{ $ks->kkm ?? '' }}</td>
                    <td style="text-align: center; {{ ($ks->knowledge_score !== null && $ks->kkm !== null && $ks->knowledge_score < $ks->kkm) ? 'color:#cc0000;font-weight:bold;' : '' }}">
                        {{ $ks->knowledge_score ?? '' }}
                    </td>
                    <td style="text-align: center;">{{ $ks->knowledge_predicate ?? '' }}</td>
                    <td style="font-family:'Times New Roman',serif; font-size:8.5pt;">{{ $ks->knowledge_description ?? '' }}</td>
                    <td style="text-align: center; {{ ($ks->skill_score !== null && $ks->kkm !== null && $ks->skill_score < $ks->kkm) ? 'color:#cc0000;font-weight:bold;' : '' }}">
                        {{ $ks->skill_score ?? '' }}
                    </td>
                    <td style="text-align: center;">{{ $ks->skill_predicate ?? '' }}</td>
                    <td style="font-family:'Times New Roman',serif; font-size:8.5pt;">{{ $ks->skill_description ?? '' }}</td>
                </tr>
            @empty
                <tr style="height: 22px;">
                    <td style="text-align: center;">1</td>
                    <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
            @endforelse
            <tr>
                <td colspan="3" style="font-weight: bold;">Total Nilai</td>
                <td></td><td></td><td></td><td></td><td></td><td></td>
            </tr>
            <tr>
                <td colspan="3" style="font-weight: bold;">Nilai Rata- Rata</td>
                <td></td><td></td><td></td><td></td><td></td><td></td>
            </tr>
        </tbody>
    </table>

    {{-- Absensi + Kepribadian berdampingan --}}
    <table style="border: none; margin-top: 5px;">
        <tr>
            {{-- Absensi kiri --}}
            <td style="border: none; width: 30%; vertical-align: top; padding: 0 6px 0 0;">
                <table>
                    <thead>
                        <tr>
                            <th colspan="2">Absensi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="width: 60%;">Sakit</td>
                            <td style="text-align: center;">{{ $overallAttendance['sakit'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td>Izin</td>
                            <td style="text-align: center;">{{ $overallAttendance['izin'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td>Alpa</td>
                            <td style="text-align: center;">{{ $overallAttendance['alpa'] ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>

            {{-- Kepribadian kanan --}}
            <td style="border: none; width: 70%; vertical-align: top; padding: 0 0 0 6px;">
                @if ($personalityScore !== null)
                    @php
                        $opsi = ['A', 'B', 'C', 'D'];
                        $aspekKep = [
                            'Kedisiplinan' => $personalityScore->kedisiplinan ?? 'A',
                            'Kerapihan'    => $personalityScore->kerapihan ?? 'A',
                            'Kerajinan'    => $personalityScore->kerajinan ?? 'A',
                            'Kesopanan'    => $personalityScore->kesopanan ?? 'A',
                        ];
                    @endphp
                    <table>
                        <thead>
                            <tr>
                                <th colspan="2">Kepribadian</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($aspekKep as $aspek => $aktif)
                                <tr>
                                    <td style="width: 35%;">{{ $aspek }}</td>
                                    <td>
                                        @foreach ($opsi as $o)
                                            @if ($o === $aktif)
                                                <span class="nilai-aktif">{{ $o }}</span>
                                            @else
                                                <span class="nilai-coret">{{ $o }}</span>
                                            @endif
                                            @if (!$loop->last) / @endif
                                        @endforeach
                                        <span style="font-size:7pt;"> *)</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <table>
                        <thead><tr><th colspan="2">Kepribadian</th></tr></thead>
                        <tbody>
                            @foreach (['Kedisiplinan','Kerapihan','Kerajinan','Kesopanan'] as $aspek)
                                <tr>
                                    <td style="width: 35%;">{{ $aspek }}</td>
                                    <td>A / B / C / D <span style="font-size:7pt;"> *)</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </td>
        </tr>
    </table>

    @include('rapor.partials._footer_ortu')

</div>
