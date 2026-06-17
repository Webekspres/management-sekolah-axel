# Implementation Plan: eRapor PDF v2

## Overview

Upgrade tampilan PDF eRapor dari v1 ke v2 dengan membuat set file Blade baru yang modular. Perubahan hanya pada layer presentasi (Blade + CSS) — backend, query, dan kalkulasi nilai tidak disentuh. Satu-satunya perubahan PHP adalah mengganti nama view di `RaporService::generatePdf()`.

## Tasks

- [x] 1. Backup template v1 dan siapkan struktur file v2
  - Rename `resources/views/rapor/pdf.blade.php` → `resources/views/rapor/pdf_v1_backup.blade.php`
  - Buat direktori `resources/views/rapor/partials/`
  - Buat file kosong (placeholder) untuk semua partial: `_header.blade.php`, `_footer_resmi.blade.php`, `_footer_ortu.blade.php`, `halaman1.blade.php`, `halaman2.blade.php`, `halaman3.blade.php`
  - Pastikan font tersedia di `public/fonts/`: `Courgette-Regular.ttf`, `calibri.ttf`, `calibrib.ttf`, `times.ttf`
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [x] 2. Buat entry point `pdf_v2.blade.php` dengan CSS lengkap
  - [x] 2.1 Buat `resources/views/rapor/pdf_v2.blade.php` dengan struktur `<!DOCTYPE html>`, `<head>`, dan `<body>`
    - Embed semua CSS di dalam tag `<style>` (tidak ada file CSS eksternal)
    - Definisikan `@font-face` untuk Courgette, Calibri regular, Calibri bold, dan Times New Roman menggunakan `public_path('fonts/...')`
    - Definisikan base styles: `body`, `.page`, `.page:last-child`
    - Definisikan table styles: `table`, `td`, `th`
    - Definisikan utility classes: `.below-kkm`, `.nilai-aktif`, `.nilai-coret`, `.ttd-line`, `.text-center`, `.text-left`, `.font-bold`
    - Definisikan layout classes: `.table-row`, `.table-cell`
    - Tambahkan `@include` untuk `halaman1`, `halaman2`, `halaman3`
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9, 2.10, 17.1, 17.2_

- [x] 3. Implementasi partial `_header.blade.php`
  - Buat `resources/views/rapor/partials/_header.blade.php`
  - Baris 1: nama institusi dengan font Courgette
  - Baris 2: nama sekolah
  - Baris 3: NPSN (kiri) + "TERAKREDITASI : A" (kanan) menggunakan `display: table` dengan dua `display: table-cell`
  - Tambahkan `border-bottom: 2px solid #000` sebagai pemisah
  - Gunakan variabel `$schoolClass` dan `$academicYear` dari scope parent
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [x] 4. Implementasi partial `_footer_resmi.blade.php`
  - Buat `resources/views/rapor/partials/_footer_resmi.blade.php`
  - Baca titimangsa dari `Setting::where('key', 'titimangsa')->value('value')` dan format dengan Carbon locale `id`
  - Tampilkan titimangsa di tengah (`text-align: center`)
  - Layout 2 kolom menggunakan `display: table`: "Mengetahui Ketua Litbang HS-TKB" (kiri) + "Dibuat oleh Wali Kelas SMP" (kanan)
  - Tambahkan `.ttd-line` dan angka "0" sebagai placeholder nomor urut di bawah garis
  - Gunakan `$waliKelasName` dengan fallback `'( __________________ )'` jika null
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 20.1, 20.2, 20.3, 20.4_

- [x] 5. Implementasi partial `_footer_ortu.blade.php`
  - Buat `resources/views/rapor/partials/_footer_ortu.blade.php`
  - Baca titimangsa dari sumber yang sama dengan `_footer_resmi.blade.php`
  - Tampilkan titimangsa di tengah
  - Layout 2 kolom: "Mengetahui Orang Tua/Wali" (kiri) + "Wali Kelas" (kanan)
  - Tambahkan `.ttd-line` dan nama wali kelas di bawah garis
  - Gunakan `$waliKelasName` dari scope parent
  - _Requirements: 14.1, 14.2, 14.3, 14.4, 14.5_

- [x] 6. Implementasi `halaman1.blade.php`
  - [x] 6.1 Buat `resources/views/rapor/partials/halaman1.blade.php` dengan struktur dasar
    - Bungkus konten dalam `<div class="page">`
    - Tambahkan `@include('rapor.partials._header')` di awal
    - Tambahkan `@include('rapor.partials._footer_resmi')` di akhir
    - _Requirements: 1.2, 17.1_

  - [x] 6.2 Implementasi identitas siswa 2 kolom
    - Layout 2 kolom menggunakan `display: table`
    - Kolom kiri: Nama Siswa, NIS/NISN, Program
    - Kolom kanan: Kelas, Semester, Tahun Pembelajaran
    - Gunakan `$student->user?->name ?? '—'` sebagai fallback
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [x] 6.3 Implementasi tabel absensi per mata pelajaran dengan header multi-level
    - Header baris 1: "Mata Pelajaran" (`rowspan="2"`), "Jumlah Sesi Pada Bulan" (`colspan` = jumlah bulan), "Total Sesi" (`rowspan="2"`)
    - Header baris 2: nama singkat bulan dari `$semesterMonths` menggunakan `$monthNames`
    - Data baris dari `$attendanceBySubject` dengan nilai `$subjectData['months'][$month]['total']`
    - Fallback baris "Tidak ada data absensi" jika `$attendanceBySubject` kosong
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [x] 6.4 Implementasi tabel daftar nilai dengan header multi-level
    - Header baris 1: "No" (`rowspan="2"`), "Mata Pelajaran" (`rowspan="2"`), "Penilaian Harian" (`colspan="4"`), "Tugas/PR" (`colspan="4"`), "ATS" (`rowspan="2"`), "SAS" (`rowspan="2"`), "Nilai Rapor" (`rowspan="2"`), "Guru Bidang Studi" (`rowspan="2"`)
    - Header baris 2: PH1, PH2, PH3, PH4, T1, T2, T3, T4
    - Data dari `$gradesBySubject` dengan nilai `$subjectData['grades'][$type] ?? '—'`
    - Kolom "Nilai Rapor" dengan `font-weight: bold`
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6_

- [x] 7. Implementasi `halaman2.blade.php`
  - [x] 7.1 Buat `resources/views/rapor/partials/halaman2.blade.php` dengan struktur dasar
    - Bungkus konten dalam `<div class="page">`
    - Tambahkan `@include('rapor.partials._header')` di awal
    - Tambahkan sub-judul "LAPORAN HASIL BELAJAR SISWA" (bold, center) setelah header
    - Tambahkan `@include('rapor.partials._footer_ortu')` di akhir
    - _Requirements: 8.1, 8.2_

  - [x] 7.2 Implementasi identitas siswa 1 kolom kiri
    - Format "Label : Nilai" dengan lebar label yang konsisten
    - Tampilkan: Nama Siswa, NIS/NISN, Kelas, Semester, Tahun Pelajaran, Program
    - _Requirements: 9.1, 9.2, 9.3_

  - [x] 7.3 Implementasi tabel nilai sikap dengan baris rata-rata
    - Kolom: Aspek, Nilai, Deskripsi
    - Hitung rata-rata dan predikat menggunakan `match(true)` (A≥86, B≥73, C≥60, D<60)
    - Tampilkan baris "Rata-Rata Nilai Sikap" (bold) dengan nilai rata-rata dan predikat di kolom Deskripsi
    - Fallback baris "Belum ada data" (colspan) jika `$attitudeScores` kosong
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

  - [x] 7.4 Implementasi tabel pengetahuan & keterampilan dengan header multi-level
    - Header baris 1: "Mata Pelajaran" (`rowspan="2"`), "KKM" (`rowspan="2"`), "Pengetahuan" (`colspan="3"`), "Keterampilan" (`colspan="3"`)
    - Header baris 2: Nilai, Predikat, Deskripsi (×2)
    - Nilai di bawah KKM ditampilkan dengan class `.below-kkm`
    - Kolom deskripsi menggunakan `word-wrap: break-word`
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5_

  - [x] 7.5 Implementasi rekap absensi format vertikal 2 kolom
    - Tabel 2 kolom: label (kiri) + nilai (kanan)
    - Baris: Sakit, Izin, Alpa, Total
    - Data dari `$overallAttendance` dengan fallback `'-'`
    - _Requirements: 12.1, 12.2, 12.3, 12.4_

  - [x] 7.6 Implementasi tabel kepribadian dengan semua opsi A/B/C/D
    - Kolom: Kedisiplinan, Kerapihan, Kerajinan, Kesopanan
    - Setiap sel menampilkan semua opsi A / B / C / D
    - Nilai aktif menggunakan class `.nilai-aktif` (bold), nilai tidak aktif menggunakan class `.nilai-coret` (line-through)
    - Fallback pesan "Belum ada data kepribadian." jika `$personalityScore` null
    - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.5_

- [x] 8. Implementasi `halaman3.blade.php`
  - [x] 8.1 Buat `resources/views/rapor/partials/halaman3.blade.php` dengan struktur dasar
    - Bungkus konten dalam `<div class="page">`
    - Tambahkan `@include('rapor.partials._header')` di awal
    - Tambahkan `@include('rapor.partials._footer_resmi')` di akhir
    - _Requirements: 1.2_

  - [x] 8.2 Implementasi tabel capaian pembelajaran dengan header multi-level
    - Header baris 1: "Mata Pelajaran" (`rowspan="2"`), "Pemaparan Materi" (`rowspan="2"`), "Hasil Pembelajaran" (`colspan="4"`)
    - Header baris 2: PH (Rata-rata), ATS, SAS, Keterangan
    - Data dari `$learningAchievements` dan `$gradesBySubject`
    - Fallback baris "Belum ada data capaian pembelajaran" (colspan) jika kosong
    - _Requirements: 15.1, 15.2, 15.3, 15.4, 15.5_

  - [x] 8.3 Implementasi tabel keterangan predikat
    - Tabel dengan kolom: Nilai (rentang angka), Huruf, Keterangan
    - 4 baris statis: A (86–100, Sangat Baik), B (73–85, Baik), C (60–72, Cukup), D (<60, Kurang)
    - Tempatkan setelah tabel capaian pembelajaran
    - _Requirements: 16.1, 16.2, 16.3_

- [x] 9. Update `RaporService::generatePdf()` untuk menggunakan template v2
  - Ganti `'rapor.pdf'` menjadi `'rapor.pdf_v2'` di pemanggilan `Pdf::loadView()`
  - Pastikan semua variabel yang diteruskan ke template tetap sama (tidak ada penambahan atau pengurangan)
  - Tidak ada perubahan lain pada logika query, kalkulasi, atau workflow
  - _Requirements: 19.1, 19.2, 19.3, 19.4_

- [x] 10. Checkpoint — Verifikasi render manual
  - Pastikan semua file Blade dapat di-render tanpa syntax error
  - Pastikan semua `@include` path sudah benar
  - Pastikan semua variabel yang diakses di template sudah tersedia dari `RaporService`
  - Tanyakan kepada user jika ada pertanyaan sebelum lanjut ke testing.

- [x] 11. Tulis test untuk fitur PDF v2
  - [x] 11.1 Buat feature test `RaporPdfV2Test` menggunakan `php artisan make:test --pest RaporPdfV2Test`
    - Test: `generates PDF using v2 template without errors` — panggil `RaporService::generatePdf()` dengan data lengkap dari factory, assert file PDF berhasil dibuat dan ukurannya > 0
    - _Requirements: 19.1_

  - [x]* 11.2 Tulis integration test untuk view rendering
    - Test: `renders pdf_v2 view without throwing exceptions` — render view `rapor.pdf_v2` dengan data minimal, assert HTML mengandung `'LAPORAN HASIL BELAJAR SISWA'`
    - _Requirements: 1.1, 8.1_

  - [x]* 11.3 Tulis regression test untuk backup v1
    - Test: `v1 backup template still exists` — assert `file_exists(resource_path('views/rapor/pdf_v1_backup.blade.php'))` bernilai `true`
    - _Requirements: 1.4_

- [x] 12. Final checkpoint — Pastikan semua test lulus
  - Jalankan `php artisan test --compact --filter=RaporPdfV2Test`
  - Pastikan semua test lulus, tanyakan kepada user jika ada pertanyaan.

## Notes

- Task yang ditandai `*` bersifat opsional dan dapat dilewati untuk MVP yang lebih cepat
- Setiap task mereferensikan requirement spesifik untuk traceability
- Tidak ada property-based test karena fitur ini adalah UI rendering (Blade + CSS), bukan logika bisnis — sesuai dengan Testing Strategy di design document
- Backend (`RaporService`) tidak boleh diubah kecuali satu baris penggantian nama view (Requirement 19)
- DomPDF tidak mendukung flexbox/grid — semua layout multi-kolom harus menggunakan `display: table` / `display: table-cell`
- Font harus menggunakan `public_path()` (path absolut), bukan URL relatif
