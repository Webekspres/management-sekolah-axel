# Implementation Plan: Student Grades Report UI

## Overview

Redesain UI halaman `MyGradesPage` dan `MyRaporPage` pada Student Panel menggunakan komponen Filament v5 native. Implementasi dilakukan secara bertahap: mulai dari widget statistik, lalu modifikasi halaman, lalu update blade view, dan diakhiri dengan pengujian.

## Tasks

- [x] 1. Buat `GradeStatsWidget` untuk ringkasan statistik nilai
  - [x] 1.1 Buat file `app/Filament/Student/Widgets/GradeStatsWidget.php`
    - Extends `Filament\Widgets\StatsOverviewWidget`
    - Tambah method `getStats(): array` yang menghitung: jumlah mata pelajaran, rata-rata nilai RAPOR, dan jumlah mata pelajaran di bawah KKM
    - Query data dari `auth()->user()->student` → `Grade::where(student_id, academic_year_id)->where(grade_type, 'RAPOR')`
    - Gunakan `SubjectKkm::getKkm($subjectId, $levelId)` dengan fallback 70.0 untuk KKM lookup
    - Gunakan `Filament\Widgets\StatsOverviewWidget\Stat` untuk setiap stat
    - Stat "Di Bawah KKM" menggunakan `->color('danger')` jika count > 0, `->color('success')` jika 0
    - Widget hanya aktif ketika student profile ada (guard dengan early return jika `student === null`)
    - _Requirements: 1.1_

  - [x]* 1.2 Tulis property test untuk komputasi statistik nilai
    - **Property 1: Statistik nilai dihitung dengan benar dari koleksi grade**
    - **Validates: Requirements 1.1**
    - Buat `tests/Unit/GradeStatsComputationTest.php`
    - Ekstrak logika komputasi ke helper method yang dapat diuji secara terisolasi
    - Jalankan 100 iterasi dengan data acak: variasikan jumlah mata pelajaran (1–10), score RAPOR (0–100), dan nilai KKM
    - Assert: `subject_count` = jumlah distinct subject, `avg_rapor` = mean aritmetika score RAPOR, `below_kkm` = count subject dengan RAPOR < KKM

- [x] 2. Buat `RaporStatsWidget` untuk ringkasan statistik rapor
  - [x] 2.1 Buat file `app/Filament/Student/Widgets/RaporStatsWidget.php`
    - Extends `Filament\Widgets\StatsOverviewWidget`
    - Tambah method `getStats(): array` yang menghitung: total rapor, jumlah APPROVED, jumlah DRAFT+FINALIZED
    - Query data dari `auth()->user()->student` → `Rapor::where(student_id)->get()`
    - Gunakan `Filament\Widgets\StatsOverviewWidget\Stat` untuk setiap stat
    - Widget hanya aktif ketika student profile ada
    - _Requirements: 5.1, 5.2_

  - [x]* 2.2 Tulis property test untuk komputasi statistik rapor
    - **Property 5: Statistik rapor konsisten dengan data koleksi**
    - **Validates: Requirements 5.1**
    - Buat `tests/Unit/RaporStatsComputationTest.php`
    - Jalankan 100 iterasi dengan kombinasi status rapor acak (DRAFT, FINALIZED, APPROVED)
    - Assert: `total` = count semua rapor, `approved` = count APPROVED, `not_ready` = count DRAFT+FINALIZED
    - Assert invariant: `total === approved + not_ready` untuk setiap iterasi

- [x] 3. Checkpoint — Pastikan semua unit test lulus
  - Pastikan semua unit test lulus, tanyakan kepada user jika ada pertanyaan.

- [x] 4. Modifikasi `MyGradesPage` untuk menggunakan widget dan komponen Filament
  - [x] 4.1 Update `app/Filament/Student/Pages/MyGradesPage.php`
    - Tambah method `getHeaderWidgets(): array` yang mengembalikan `[GradeStatsWidget::class]`
    - Perluas array `$gradesBySubject` di `mount()` untuk menyertakan `subject_id` dan `level_id` agar KKM dapat di-lookup di widget
    - Pertahankan semua logika `mount()` yang sudah ada
    - _Requirements: 1.1, 1.2, 6.1, 6.3_

  - [x] 4.2 Update `resources/views/filament/student/pages/my-grades-page.blade.php`
    - Ganti tabel HTML manual dengan komponen Filament
    - State "tidak ada profil siswa": gunakan `<x-filament::section>` dengan ikon `heroicon-o-information-circle`
    - State "tidak ada nilai": gunakan `<x-filament::section>` dengan ikon `heroicon-o-clipboard-document-list`
    - Per-subject loop: gunakan `<x-filament::section :heading="$item['subject_name']">` untuk setiap mata pelajaran
    - Di dalam setiap section, gunakan grid 3 kolom (Tailwind `grid grid-cols-1 sm:grid-cols-3`) untuk mengelompokkan: PH Group (PH1–PH4), Tugas Group (TUGAS1–TUGAS4), dan ATS/SAS/RAPOR
    - Tampilkan setiap nilai dengan label grade type dan nilai numerik `number_format((float) $score, 2)` atau "—" jika tidak ada
    - Nilai RAPOR diberi penekanan visual (warna primary, font semibold)
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 3.1, 3.2, 3.3, 6.1, 6.3, 6.5_

  - [x]* 4.3 Tulis property test untuk tampilan nilai
    - **Property 2: Nilai yang tidak tersedia selalu ditampilkan sebagai placeholder**
    - **Property 3: Semua 11 grade type selalu ditampilkan untuk setiap mata pelajaran**
    - **Property 4: Nilai numerik selalu diformat dengan dua angka desimal**
    - **Validates: Requirements 2.3, 2.4, 2.5**
    - Buat `tests/Feature/GradeDisplayPropertyTest.php`
    - Jalankan 100 iterasi: buat student dengan grade acak (1–8 grade, grade_type acak)
    - Assert Property 3: semua 11 label `Grade::GRADE_TYPES` muncul di response untuk setiap mata pelajaran
    - Assert Property 4: setiap score numerik yang ada di database muncul dalam format `number_format((float) $score, 2)`
    - Assert Property 2: untuk grade_type yang tidak ada di database, tampilan menampilkan "—"

  - [x]* 4.4 Tulis feature test untuk `MyGradesPage`
    - Buat `tests/Feature/MyGradesPageTest.php`
    - Test: menampilkan pesan ketika tidak ada profil siswa (`assertSee('Profil siswa tidak ditemukan')`)
    - Test: menampilkan empty state ketika tidak ada nilai
    - Test: menampilkan nama tahun akademik aktif
    - Test: menampilkan semua mata pelajaran dengan data nilai
    - Test: nilai RAPOR ditampilkan dengan format dua desimal
    - _Requirements: 1.2, 1.3, 1.4, 2.3, 2.4, 2.5_

- [x] 5. Modifikasi `MyRaporPage` untuk menggunakan Filament Table dan widget
  - [x] 5.1 Update `app/Filament/Student/Pages/MyRaporPage.php`
    - Implement `Filament\Tables\Contracts\HasTable` dan gunakan `Filament\Tables\Concerns\InteractsWithTable`
    - Tambah method `table(Table $table): Table` dengan kolom:
      - `TextColumn::make('academicYear.name')` — label "Tahun Akademik"
      - `TextColumn::make('academicYear.semester')` — label "Semester"
      - `TextColumn::make('status')->badge()` dengan warna: `APPROVED` → `success`, `FINALIZED` → `warning`, `DRAFT` → `gray`
      - `TextColumn::make('approved_at')->dateTime()->placeholder('—')` — label "Tanggal Disetujui"
      - `Action::make('download')` dari `Filament\Actions\Action` — hanya visible ketika `isApproved() && file_path !== null`, memanggil `downloadRapor($record->id)`
    - Tambah method `getHeaderWidgets(): array` yang mengembalikan `[RaporStatsWidget::class]`
    - Tambah `->emptyStateHeading('Belum ada rapor')` dan `->emptyStateIcon(Heroicon::OutlinedDocumentText)` pada table
    - Pertahankan method `downloadRapor()` yang sudah ada
    - Hapus property `public Collection $rapors` karena data sekarang dikelola oleh Filament Table
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 5.2, 6.2, 6.4_

  - [x] 5.2 Update `resources/views/filament/student/pages/my-rapor-page.blade.php`
    - Ganti seluruh konten tabel HTML manual dengan `{{ $this->table }}`
    - State "tidak ada profil siswa" tetap ditampilkan di atas table menggunakan `<x-filament::section>`
    - _Requirements: 4.5, 4.6, 6.2_

  - [x]* 5.3 Tulis feature test untuk `MyRaporPage`
    - Buat `tests/Feature/MyRaporPageTest.php`
    - Test: menampilkan pesan ketika tidak ada profil siswa
    - Test: menampilkan empty state ketika tidak ada rapor
    - Test: menampilkan badge status dengan warna yang benar (APPROVED=success, FINALIZED=warning, DRAFT=gray)
    - Test: menampilkan tombol download untuk rapor APPROVED dengan file
    - Test: tidak menampilkan tombol download untuk rapor DRAFT
    - Test: menampilkan kolom Tahun Akademik, Semester, Status, Tanggal Disetujui
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6_

- [x] 6. Checkpoint — Pastikan semua test lulus
  - Pastikan semua unit test dan feature test lulus, tanyakan kepada user jika ada pertanyaan.

## Notes

- Tasks bertanda `*` bersifat opsional dan dapat dilewati untuk implementasi MVP yang lebih cepat
- Setiap task mereferensikan requirements spesifik untuk traceability
- Widget (`GradeStatsWidget`, `RaporStatsWidget`) harus di-register via `getHeaderWidgets()` pada masing-masing Page — bukan di panel provider
- Namespace yang benar: `Filament\Schemas\Components\` untuk Section/Grid, `Filament\Infolists\Components\` untuk InfoList entries, `Filament\Tables\Columns\` untuk kolom tabel, `Filament\Actions\` untuk actions, `Filament\Widgets\StatsOverviewWidget\Stat` untuk stats
- Perubahan bersifat UI-only: tidak ada perubahan pada model, migrasi, atau logika bisnis inti
