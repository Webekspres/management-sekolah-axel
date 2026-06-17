# Design Document — erapor-pdf-v2

## Overview

Fitur ini meng-upgrade tampilan PDF eRapor dari v1 ke v2 dengan membuat set file Blade baru tanpa menyentuh backend, query, atau kalkulasi nilai. Satu-satunya perubahan di sisi PHP adalah mengganti nama view dari `rapor.pdf` menjadi `rapor.pdf_v2` di `RaporService::generatePdf()`.

Template v2 menggunakan struktur partial yang lebih modular, font kustom via `@font-face`, layout multi-kolom berbasis `display: table` (bukan flexbox/grid karena keterbatasan DomPDF), dan tampilan yang lebih formal sesuai format rapor resmi.

**Scope perubahan:**

- Buat `resources/views/rapor/pdf_v2.blade.php` (entry point)
- Buat direktori `resources/views/rapor/partials/` dengan 6 partial
- Rename `pdf.blade.php` → `pdf_v1_backup.blade.php` (backup, tidak dihapus)
- Ubah satu baris di `RaporService::generatePdf()`: `'rapor.pdf'` → `'rapor.pdf_v2'`

---

## Architecture

```
RaporService::generatePdf()
    └── Pdf::loadView('rapor.pdf_v2', $data)
            └── resources/views/rapor/pdf_v2.blade.php
                    ├── @include('rapor.partials._header')          ← shared
                    ├── @include('rapor.partials.halaman1')
                    │       ├── @include('rapor.partials._header')
                    │       └── @include('rapor.partials._footer_resmi')
                    ├── @include('rapor.partials.halaman2')
                    │       ├── @include('rapor.partials._header')
                    │       └── @include('rapor.partials._footer_ortu')
                    └── @include('rapor.partials.halaman3')
                            ├── @include('rapor.partials._header')
                            └── @include('rapor.partials._footer_resmi')
```

Setiap halaman dibungkus dalam `<div class="page">`. CSS di-embed langsung di `<style>` dalam `pdf_v2.blade.php` — tidak ada file CSS eksternal.

---

## Components and Interfaces

### Entry Point: `pdf_v2.blade.php`

Bertanggung jawab untuk:

1. Mendefinisikan `<!DOCTYPE html>`, `<head>`, dan semua `<style>` (termasuk `@font-face`)
2. Meng-include tiga halaman secara berurutan

Tidak ada logika PHP di file ini — semua logika presentasi ada di partial masing-masing.

### Partial: `_header.blade.php`

Dipakai di ketiga halaman. Menampilkan:

- Baris 1: Nama institusi (font Courgette)
- Baris 2: Nama sekolah
- Baris 3: NPSN (kiri) + "TERAKREDITASI : A" (kanan) via `display: table`
- Garis bawah `border-bottom: 2px solid #000`

Variabel yang diakses: `$schoolClass`, `$academicYear` (dari scope parent).

### Partial: `halaman1.blade.php`

Konten:

1. `@include('rapor.partials._header')`
2. Identitas siswa — layout 2 kolom (`display: table`)
3. Tabel Absensi per Mapel — header 2 level (rowspan/colspan)
4. Tabel Daftar Nilai — header 2 level (rowspan/colspan)
5. `@include('rapor.partials._footer_resmi')`

### Partial: `halaman2.blade.php`

Konten:

1. `@include('rapor.partials._header')`
2. Sub-judul "LAPORAN HASIL BELAJAR SISWA" (center, bold)
3. Identitas siswa — 1 kolom kiri
4. Tabel Nilai Sikap + baris rata-rata + predikat
5. Tabel Pengetahuan & Keterampilan — header 2 level
6. Rekap Absensi — tabel 2 kolom vertikal
7. Tabel Kepribadian — semua opsi A/B/C/D dengan coret
8. `@include('rapor.partials._footer_ortu')`

### Partial: `halaman3.blade.php`

Konten:

1. `@include('rapor.partials._header')`
2. Tabel Capaian Pembelajaran — header 2 level
3. Tabel Keterangan Predikat (4 baris statis)
4. `@include('rapor.partials._footer_resmi')`

### Partial: `_footer_resmi.blade.php`

Dipakai di halaman 1 dan 3. Menampilkan:

- Titimangsa (center)
- 2 kolom tanda tangan: "Mengetahui Ketua Litbang HS-TKB" (kiri) + "Dibuat oleh Wali Kelas SMP" (kanan)
- Garis TTD + angka "0" sebagai placeholder nomor urut

### Partial: `_footer_ortu.blade.php`

Dipakai di halaman 2. Menampilkan:

- Titimangsa (center)
- 2 kolom tanda tangan: "Mengetahui Orang Tua/Wali" (kiri) + "Wali Kelas" (kanan)
- Garis TTD + nama wali kelas

---

## Data Models

Semua data sudah disiapkan oleh `RaporService::generatePdf()` dan diteruskan ke template. Tidak ada query baru di Blade.

### Variabel yang tersedia di template

| Variabel | Tipe | Dipakai di |
|---|---|---|
| `$rapor` | `Rapor` | — (metadata) |
| `$student` | `Student` (with `user`) | Semua halaman |
| `$academicYear` | `AcademicYear` | Semua halaman |
| `$schoolClass` | `SchoolClass` | Semua halaman |
| `$grades` | `Collection<Grade>` | — |
| `$gradesBySubject` | `array<subjectId, ['subject_name', 'grades', 'teacher_name']>` | Halaman 1, 3 |
| `$attitudeScores` | `Collection<AttitudeScore>` | Halaman 2 |
| `$knowledgeSkillScores` | `Collection<KnowledgeSkillScore>` (with `kkm`) | Halaman 2 |
| `$learningAchievements` | `Collection<LearningAchievement>` (with `subject`) | Halaman 3 |
| `$personalityScore` | `?PersonalityScore` | Halaman 2 |
| `$attendanceBySubject` | `array<subjectId, ['subject_name', 'months', 'total']>` | Halaman 1 |
| `$overallAttendance` | `array['sakit', 'izin', 'alpa', 'total']` | Halaman 2 |
| `$semesterMonths` | `array<int>` | Halaman 1 |
| `$monthNames` | `array<int, string>` | Halaman 1 |
| `$waliKelasName` | `?string` | Footer halaman 1, 2, 3 |

### Titimangsa

Dibaca langsung di Blade (bukan dari `$data`):

```blade
@php
    $titimangsa = \App\Models\Setting::where('key', 'titimangsa')->value('value');
    $titimangsaFormatted = $titimangsa
        ? \Carbon\Carbon::parse($titimangsa)->locale('id')->isoFormat('D MMMM YYYY')
        : '—';
@endphp
```

Format output contoh: `"Jakarta, 23 Desember 2025"` — prefix kota diambil dari nilai setting itu sendiri (format: `"Jakarta, 2025-12-23"`), atau jika setting hanya berisi tanggal, diformat sebagai tanggal saja.

### Struktur `$gradesBySubject`

```php
[
    'uuid-subject' => [
        'subject_name' => 'Matematika',
        'grades' => [
            'PH1' => '85.00',
            'PH2' => '90.00',
            // ... PH3, PH4, TUGAS1-4, ATS, SAS, RAPOR
        ],
        'teacher_name' => 'Budi Santoso',
    ],
]
```

### Struktur `$attendanceBySubject`

```php
[
    'uuid-subject' => [
        'subject_name' => 'Matematika',
        'months' => [
            7 => ['total' => 3],
            8 => ['total' => 4],
            // ...
        ],
        'total' => 7,
    ],
]
```

### Logika Predikat Rata-Rata Sikap (di Blade)

```blade
@php
    $avgSikap = $attitudeScores->avg('score');
    $predikatSikap = match(true) {
        $avgSikap >= 86 => 'A',
        $avgSikap >= 73 => 'B',
        $avgSikap >= 60 => 'C',
        default         => 'D',
    };
@endphp
```

### Logika Kepribadian (di Blade)

```blade
@foreach (['A', 'B', 'C', 'D'] as $opsi)
    @if ($personalityScore->kedisiplinan === $opsi)
        <span class="nilai-aktif">{{ $opsi }}</span>
    @else
        <span class="nilai-coret">{{ $opsi }}</span>
    @endif
    @if (!$loop->last) / @endif
@endforeach
```

---

## CSS Architecture

Semua CSS di-embed dalam `<style>` di `pdf_v2.blade.php`. DomPDF tidak mendukung flexbox atau CSS grid — semua layout multi-kolom menggunakan `display: table` / `display: table-cell`.

### Font Declarations

```css
@font-face {
    font-family: 'Courgette';
    src: url('{{ public_path('fonts/Courgette-Regular.ttf') }}') format('truetype');
    font-weight: normal;
}
@font-face {
    font-family: 'Calibri';
    src: url('{{ public_path('fonts/calibri.ttf') }}') format('truetype');
    font-weight: normal;
}
@font-face {
    font-family: 'Calibri';
    src: url('{{ public_path('fonts/calibrib.ttf') }}') format('truetype');
    font-weight: bold;
}
@font-face {
    font-family: 'Times New Roman';
    src: url('{{ public_path('fonts/times.ttf') }}') format('truetype');
    font-weight: normal;
}
```

> **Catatan:** DomPDF memerlukan path absolut untuk font. Gunakan `public_path()` bukan URL relatif.

### Base Styles

```css
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: Calibri, sans-serif; font-size: 9pt; color: #000; }

.page {
    width: 210mm;
    padding: 10mm 12mm 8mm 12mm;
    page-break-after: always;
}
.page:last-child { page-break-after: auto; }

table {
    border-collapse: collapse;
    table-layout: fixed;
    width: 100%;
}
td, th {
    border: 1px solid #000;
    padding: 2px 4px;
    word-wrap: break-word;
    overflow: hidden;
}
```

### Utility Classes

```css
.below-kkm    { color: #cc0000; font-weight: bold; }
.nilai-aktif  { font-weight: bold; }
.nilai-coret  { text-decoration: line-through; }
.ttd-line     { margin-top: 40px; border-bottom: 1px solid #000; }
.text-center  { text-align: center; }
.text-left    { text-align: left; }
.font-bold    { font-weight: bold; }
```

### Multi-Column Layout Pattern

```css
/* Digunakan untuk header, footer TTD, identitas 2 kolom */
.table-row    { display: table; width: 100%; }
.table-cell   { display: table-cell; vertical-align: top; }
```

---

## Error Handling

### Template tidak ditemukan

Jika `rapor.pdf_v2` tidak ditemukan, DomPDF akan melempar exception. Ini sudah ditangkap oleh blok `catch (\Throwable $e)` yang ada di `RaporService::generatePdf()`:

```php
} catch (\Throwable $e) {
    Log::error('RaporService: gagal generate PDF', [...]);
    throw new \RuntimeException("Gagal generate PDF rapor: {$e->getMessage()}", 0, $e);
}
```

Tidak ada perubahan pada error handling — behavior yang ada sudah cukup.

### Titimangsa tidak tersedia

Jika `Setting::where('key', 'titimangsa')->value('value')` mengembalikan `null`, template menampilkan `'—'` sebagai fallback. Tidak ada exception yang dilempar.

### Data null/kosong

Setiap variabel yang bisa null sudah ditangani dengan operator null-safe (`?->`) dan fallback `'—'`:

| Kondisi | Fallback |
|---|---|
| `$student->user?->name` null | `'—'` |
| `$waliKelasName` null | `'( __________________ )'` |
| `$personalityScore` null | Pesan "Belum ada data kepribadian." |
| `$attitudeScores` kosong | Baris "Belum ada data" (colspan) |
| `$learningAchievements` kosong | Baris "Belum ada data capaian pembelajaran" (colspan) |
| Nilai grade tidak ada | `'—'` |

---

## Testing Strategy

Fitur ini adalah **UI rendering** (Blade template + CSS untuk PDF). Property-based testing tidak sesuai karena:

- Tidak ada fungsi murni dengan input/output yang bervariasi secara bermakna
- Yang diuji adalah struktur HTML/CSS yang dirender, bukan logika bisnis
- 100 iterasi tidak menemukan lebih banyak bug dibanding 2–3 iterasi

Strategi testing yang digunakan:

### 1. Feature Test (Snapshot / Structural)

Test utama memverifikasi bahwa `RaporService::generatePdf()` berhasil menghasilkan PDF menggunakan template v2, dengan data lengkap dari factory.

```php
// tests/Feature/RaporPdfV2Test.php
it('generates PDF using v2 template without errors', function () {
    $rapor = Rapor::factory()->withFullData()->create();
    $service = app(RaporService::class);

    $filePath = $service->generatePdf($rapor);

    expect($filePath)->toBeString();
    expect(Storage::exists($filePath))->toBeTrue();
    expect(Storage::size($filePath))->toBeGreaterThan(0);
});
```

### 2. Unit Test — Logika Presentasi di Blade

Beberapa logika presentasi yang ada di Blade dapat diekstrak dan diuji secara terpisah jika diperlukan:

- `assignPredicate()` sudah ada di `RaporService` dan sudah diuji di `RaporServiceTest`
- Logika predikat rata-rata sikap menggunakan logika yang sama (A≥86, B≥73, C≥60, D<60)
- Tidak perlu test baru untuk logika ini karena sudah tercakup

### 3. Integration Test — View Rendering

Memverifikasi bahwa view dapat di-render tanpa error dengan data minimal:

```php
it('renders pdf_v2 view without throwing exceptions', function () {
    $viewData = [/* minimal valid data */];
    $html = view('rapor.pdf_v2', $viewData)->render();

    expect($html)->toContain('LAPORAN HASIL BELAJAR SISWA');
    expect($html)->toContain('page-break-after');
});
```

### 4. Regression Test — Backup v1

Memverifikasi bahwa file v1 backup masih ada dan tidak dimodifikasi:

```php
it('v1 backup template still exists', function () {
    expect(file_exists(resource_path('views/rapor/pdf_v1_backup.blade.php')))->toBeTrue();
});
```

### Catatan

- Tidak ada visual regression test otomatis (memerlukan tooling tambahan seperti Browsershot)
- Verifikasi visual dilakukan secara manual dengan membuka PDF hasil generate di browser
- Test yang ada di `RaporServiceTest.php` dan `RaporServicePropertyTest.php` tidak perlu diubah karena backend tidak berubah
