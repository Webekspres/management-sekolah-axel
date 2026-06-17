# Implementation Plan: Modul Kehadiran (Attendance)

## Overview

Implementasi modul kehadiran pada empat panel Filament yang sudah ada (Admin, Guru, Kepsek, Student). Tabel `attendances` dan model `Attendance` sudah tersedia — tidak ada migration baru. Setiap task dirancang agar dapat dieksekusi secara independen oleh AI agent dengan mengikuti pola `KbmResource` yang sudah ada.

## Tasks

- [x] 1. Buat `AttendanceSummaryService` dan unit tests-nya
  - Buat file `app/Services/AttendanceSummaryService.php` dengan method:
    - `calculateStats(Collection $attendances): array` — mengembalikan `{total, hadir, sakit, izin, alpa, percentage}`
    - `calculatePercentage(int $hadirCount, int $totalCount): float` — formula `round((H/N)*100, 1)`, return `0.0` jika N=0
    - `isBelowWarningThreshold(float $percentage): bool` — true jika `$percentage < 75.0`
    - `getClassSummary(SchoolClass $class, ?Carbon $from, ?Carbon $to): Collection`
    - `getStudentSummary(Student $student, ?Carbon $from, ?Carbon $to): array`
  - Definisikan konstanta `ATTENDANCE_WARNING_THRESHOLD = 75.0`
  - Gunakan `php artisan make:class app/Services/AttendanceSummaryService`
  - _Requirements: 5.4, 6.1, 8.3, 13.6, 13.8_

  - [x] 1.1 Tulis unit tests untuk `AttendanceSummaryService`
    - Buat `tests/Unit/Services/AttendanceSummaryServiceTest.php` dengan `php artisan make:test --pest --unit Services/AttendanceSummaryServiceTest`
    - Test: `calculatePercentage` mengembalikan nilai benar, return 0.0 saat total=0, pembulatan 1 desimal
    - Test: `isBelowWarningThreshold` return true di bawah 75, false tepat di 75
    - Test: `calculateStats` mengembalikan count yang benar per status
    - _Requirements: 5.4, 13.6_

  - [x] 1.2 Tulis property-based tests untuk `AttendanceSummaryService`
    - Buat `tests/Unit/Services/AttendanceSummaryServicePropertyTest.php`
    - **Property 3: Persentase kehadiran mengikuti formula yang benar**
    - Gunakan Pest dataset dengan 100+ iterasi random `(hadir, total)` pairs
    - Validasi: `calculatePercentage($hadir, $total) === round(($hadir / $total) * 100, 1)`
    - **Property 4: Warning threshold konsisten dengan persentase**
    - Validasi: `isBelowWarningThreshold($p) === ($p < 75.0)` untuk semua float P
    - _Requirements: 5.3, 5.4, 13.8_

- [x] 2. Buat Admin Panel `AttendanceResource` (CRUD penuh)
  - Buat struktur direktori `app/Filament/Clusters/Academic/Resources/Attendances/` mengikuti pola `Kbms/`
  - Buat `AttendanceResource.php` dengan:
    - `$model = Attendance::class`, `$navigationGroup = 'Akademik'`, `$label = 'Absensi'`
    - `canAccess()` hanya untuk `super_admin`
    - `getEloquentQuery()` dengan eager load `kbm.schedule.schoolClass`, `kbm.schedule.subjectForDisplay`, `kbm.schedule.teacher.user`, `student.user`
    - `getPages()` dengan `index`, `create`, `edit`
  - Gunakan `php artisan make:filament-resource --panel=admin` atau buat manual mengikuti pola `KbmResource`
  - _Requirements: 3.1, 3.2, 3.3, 11.1_

  - [x] 2.1 Buat `AttendanceForm` untuk Admin panel
    - Buat `app/Filament/Clusters/Academic/Resources/Attendances/Schemas/AttendanceForm.php`
    - Field: `Select::make('kbm_id')` searchable dengan label tanggal + kelas + mata pelajaran
    - Field: `Select::make('student_id')` searchable dengan label nama siswa + kelas
    - Field: `Select::make('status')` dengan options HADIR/SAKIT/IZIN/ALPA
    - Tambahkan `Rule::unique('attendances')->where(...)` untuk validasi duplikasi `(kbm_id, student_id)`
    - _Requirements: 3.1, 3.5, 10.1, 10.4_

  - [x] 2.2 Buat `AttendancesTable` untuk Admin panel
    - Buat `app/Filament/Clusters/Academic/Resources/Attendances/Tables/AttendancesTable.php`
    - Kolom: Tanggal KBM, Kelas, Mata Pelajaran, Nama Siswa, Status (badge dengan warna), Nama Guru
    - Filter: date range (`DateRangeFilter`), SchoolClass (`SelectFilter`), Status (`SelectFilter`)
    - Default sort: tanggal descending, paginasi 25 records
    - _Requirements: 3.4, 7.1, 12.1, 12.4_

  - [x] 2.3 Buat halaman `ListAttendances`, `CreateAttendance`, `EditAttendance` untuk Admin panel
    - Buat `app/Filament/Clusters/Academic/Resources/Attendances/Pages/` dengan tiga file halaman
    - `ListAttendances` extends `ListRecords` dengan `CreateAction` di header
    - `CreateAttendance` extends `CreateRecord`
    - `EditAttendance` extends `EditRecord` dengan `DeleteAction` di header
    - _Requirements: 3.1, 3.2, 3.3_

- [x] 3. Checkpoint — Verifikasi Admin Resource
  - Pastikan semua tests pass, tanyakan ke user jika ada pertanyaan.

- [x] 4. Tambahkan `InputAbsensiAction` ke KBM table Guru panel
  - Modifikasi `app/Filament/Guru/Resources/Kbms/Tables/KbmsTable.php`
  - Tambahkan `Action::make('input_absensi')` ke `recordActions()` mengikuti pola `Action::make('detail')` yang sudah ada
  - `modalHeading` dinamis: `"Input Absensi — {$record->schedule->schoolClass->name}"`
  - `fillForm()`: load existing attendances untuk KBM tersebut, pre-populate status per siswa
  - `form()`: generate dynamic form dengan `Select` status per siswa menggunakan `Placeholder` untuk nama siswa
  - `action()`: jalankan `DB::transaction()` dengan `Attendance::upsert()` pada `['kbm_id', 'student_id']`
  - Tampilkan success notification dengan jumlah records yang disimpan
  - Tangani exception dengan rollback dan danger notification (lihat pola error handling di design)
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.3, 2.4, 2.5, 4.1, 4.2_

  - [x] 4.1 Tulis property-based tests untuk bulk upsert
    - Buat `tests/Feature/Attendance/BulkAttendancePropertyTest.php`
    - **Property 1: Bulk input menghasilkan tepat satu record per siswa**
    - Gunakan dataset 50+ iterasi dengan class size bervariasi (1–40 siswa)
    - Validasi: `Attendance::where('kbm_id', $kbm->id)->count() === $studentCount`
    - **Property 2: Upsert idempoten — tidak ada duplikasi**
    - Submit form yang sama 2–5 kali, validasi count tetap sama
    - **Property 8: Pre-populate form mencerminkan data tersimpan**
    - Buat attendance records, buka form, validasi `fillForm()` mengembalikan status yang benar
    - _Requirements: 1.2, 1.3, 1.4, 2.2, 2.3_

- [x] 5. Tambahkan kolom status absensi ke KBM table Guru panel
  - Modifikasi `app/Filament/Guru/Resources/Kbms/Tables/KbmsTable.php`
  - Tambahkan `TextColumn::make('attendance_status')` dengan `state()` closure
  - Logic: hitung `attendances()->count()` vs `schedule->schoolClass->students()->count()`
  - Tampilkan "N/M diabsen" atau badge "Lengkap ✓" jika semua siswa sudah diabsen
  - Gunakan `->badge()` dengan warna `success` jika lengkap, `warning` jika belum
  - _Requirements: 4.3, 4.4_

- [x] 6. Buat Guru Panel `AttendanceResource` (view + edit)
  - Buat struktur direktori `app/Filament/Guru/Resources/Attendances/` mengikuti pola `Kbms/`
  - Buat `AttendanceResource.php` dengan:
    - `$cluster = AcademicCluster::class`
    - `getEloquentQuery()` filter `whereHas('kbm.schedule', fn ($q) => $q->where('teacher_id', auth()->user()?->teacher?->id))`
    - `canDelete()` return `false`
    - `getPages()` dengan `index` dan `edit` saja (tidak ada `create`)
  - Buat `AttendancesTable` dengan kolom: Tanggal KBM, Kelas, Mata Pelajaran, Nama Siswa, Status (badge)
  - Buat `AttendanceForm` untuk edit status saja
  - Buat halaman `ListAttendances` dan `EditAttendance`
  - _Requirements: 1.5, 11.2, 11.3_

  - [x] 6.1 Tulis feature tests untuk Guru attendance resource
    - Buat `tests/Feature/Guru/Resources/Attendances/GuruAttendanceTest.php`
    - Test: guru dapat melihat action "Input Absensi" di KBM table
    - Test: guru dapat submit bulk attendance untuk KBM miliknya
    - Test: guru tidak dapat mengakses attendance KBM guru lain
    - Test: bulk attendance menampilkan success notification dengan jumlah records
    - Test: bulk attendance rollback saat terjadi error
    - Test: kolom status absensi menampilkan progress yang benar
    - Test: kelas kosong menampilkan pesan informatif
    - _Requirements: 1.1, 1.2, 1.5, 2.1, 2.4, 2.5, 4.3, 11.2_

  - [x] 6.2 Tulis property-based test untuk otorisasi Guru
    - Tambahkan ke `tests/Feature/Attendance/BulkAttendancePropertyTest.php` atau buat `tests/Feature/Attendance/AuthorizationPropertyTest.php`
    - **Property 5: Guru hanya bisa akses absensi KBM miliknya**
    - Gunakan dataset 50+ iterasi dengan kombinasi guru dan KBM acak
    - Validasi: query `getEloquentQuery()` tidak mengembalikan records KBM guru lain
    - _Requirements: 1.5, 11.2_

- [x] 7. Checkpoint — Verifikasi Guru Panel
  - Pastikan semua tests pass, tanyakan ke user jika ada pertanyaan.

- [x] 8. Buat Kepsek Panel `AttendanceResource` (view-only)
  - Buat struktur direktori `app/Filament/Kepsek/Resources/Attendances/` mengikuti pola `Kbms/`
  - Buat `AttendanceResource.php` dengan:
    - `$cluster = AcademicCluster::class`
    - `canCreate()`, `canEdit()`, `canDelete()` semua return `false`
    - `getPages()` hanya `index`
    - Eager load termasuk `kbm.schedule.teacher.user`
  - Buat `AttendancesTable` dengan kolom tambahan: Nama Guru (dari `kbm.schedule.teacher.user.name`)
  - Filter: date range, SchoolClass, Status
  - Buat halaman `ListAttendances` extends `ListRecords` tanpa `CreateAction` di header (ikuti pola `Kepsek/Kbms/Pages/ListKbms.php`)
  - _Requirements: 9.1, 9.2, 9.4, 11.4_

  - [x] 8.1 Tulis feature tests untuk Kepsek attendance resource
    - Buat `tests/Feature/Filament/Kepsek/Resources/Attendances/KepsekAttendanceTest.php`
    - Test: kepsek dapat melihat semua attendance records
    - Test: kepsek tidak dapat membuat attendance record
    - Test: kepsek tidak dapat mengedit attendance record
    - Test: kepsek tidak dapat menghapus attendance record
    - Test: kepsek melihat nama guru di attendance list
    - Test: kepsek dapat filter berdasarkan date range dan kelas
    - _Requirements: 9.1, 9.2, 9.4, 11.4_

- [x] 9. Buat Student Panel `AttendanceResource` (view-only, data diri sendiri)
  - Buat struktur direktori `app/Filament/Student/Resources/Attendances/` mengikuti pola `Announcements/`
  - Buat `AttendanceResource.php` dengan:
    - `getEloquentQuery()` filter `where('student_id', auth()->user()?->student?->id)`, return empty query jika student null
    - `canCreate()`, `canEdit()`, `canDelete()` semua return `false`
    - `getPages()` hanya `index`
  - Buat `AttendancesTable` dengan kolom: Tanggal KBM, Mata Pelajaran, Kelas, Status (badge)
  - Filter: date range, Status
  - Buat halaman `ListAttendances` extends `ListRecords`
  - Tampilkan empty state dengan pesan "Akun Anda belum terhubung ke data siswa." jika student null
  - _Requirements: 8.1, 8.2, 8.4, 8.5, 11.5_

  - [x] 9.1 Tulis feature tests untuk Student attendance resource
    - Buat `tests/Feature/Student/Resources/Attendances/StudentAttendanceTest.php`
    - Test: siswa dapat melihat attendance records miliknya sendiri
    - Test: siswa tidak dapat melihat attendance records siswa lain
    - Test: siswa tanpa profil student melihat pesan informatif
    - Test: siswa dapat filter berdasarkan date range dan status
    - _Requirements: 8.1, 8.4, 8.5, 11.5_

  - [x] 9.2 Tulis property-based test untuk isolasi data siswa
    - Buat atau tambahkan ke `tests/Feature/Attendance/AuthorizationPropertyTest.php`
    - **Property 6: Siswa hanya melihat absensi dirinya sendiri**
    - Gunakan dataset 50+ iterasi dengan banyak siswa dan attendance records acak
    - Validasi: semua records yang dikembalikan memiliki `student_id === $student->id`
    - _Requirements: 8.1, 11.5_

- [x] 10. Buat `AttendanceSummaryWidget` untuk Guru dan Kepsek panel
  - Buat `app/Filament/Widgets/AttendanceSummaryWidget.php` extends `StatsOverviewWidget`
  - Stats: "Total Absensi Hari Ini", "Total HADIR Hari Ini", "Siswa Kehadiran < 75%"
  - `canView()`: return true untuk role `guru` dan `kepala_sekolah`
  - Untuk Guru: filter query berdasarkan `teacher_id` milik user yang login
  - Untuk Kepsek: tampilkan data semua kelas
  - Daftarkan widget di panel Guru dan Kepsek (ikuti pola `GuruOverviewStats` dan `KepsekOverviewStats`)
  - _Requirements: 5.1, 5.3, 6.1_

  - [x] 10.1 Tulis feature tests untuk `AttendanceSummaryWidget`
    - Buat `tests/Feature/Widgets/AttendanceSummaryWidgetTest.php`
    - Test: widget menampilkan jumlah absensi hari ini yang benar
    - Test: widget menampilkan jumlah HADIR yang benar
    - Test: widget menampilkan siswa dengan kehadiran < 75% yang benar
    - Gunakan `Livewire::test(AttendanceSummaryWidget::class)` mengikuti pola `SiswaOrtuOverviewStatsTest`
    - _Requirements: 5.1, 5.3_

- [x] 11. Buat `StudentAttendanceSummaryWidget` untuk Student panel
  - Buat `app/Filament/Student/Widgets/StudentAttendanceSummaryWidget.php` extends `StatsOverviewWidget`
  - Stats: Total HADIR, SAKIT, IZIN, ALPA, dan Persentase Kehadiran
  - Gunakan `AttendanceSummaryService::calculateStats()` dan `calculatePercentage()`
  - `canView()`: return true untuk role `siswa_ortu`
  - Daftarkan widget di Student panel (ikuti pola `SiswaOrtuOverviewStats`)
  - Tampilkan pesan "Akun belum terhubung ke data siswa" jika student null
  - _Requirements: 8.3, 5.4_

  - [x] 11.1 Tulis feature tests untuk `StudentAttendanceSummaryWidget`
    - Buat `tests/Feature/Student/Widgets/StudentAttendanceSummaryWidgetTest.php`
    - Test: widget menampilkan jumlah HADIR/SAKIT/IZIN/ALPA yang benar untuk siswa yang login
    - Test: widget menampilkan persentase kehadiran yang benar
    - Test: siswa tanpa profil melihat pesan informatif
    - Gunakan `Livewire::test(StudentAttendanceSummaryWidget::class)` mengikuti pola yang ada
    - _Requirements: 8.3, 5.4_

- [x] 12. Checkpoint — Verifikasi semua panel dan widgets
  - Pastikan semua tests pass, tanyakan ke user jika ada pertanyaan.

- [x] 13. Validasi otorisasi dan property tests lintas panel
  - Buat `tests/Feature/Attendance/AuthorizationTest.php`
  - Test: user tidak terautentikasi diredirect ke halaman login saat akses attendance
  - Test: guru tidak dapat mengakses attendance KBM yang bukan miliknya (verifikasi `getEloquentQuery()`)
  - Test: siswa tidak dapat mengakses attendance siswa lain (verifikasi `getEloquentQuery()`)
  - _Requirements: 11.2, 11.5, 11.6_

  - [x] 13.1 Tulis property-based test untuk validasi status enum
    - Tambahkan ke `tests/Unit/Services/AttendanceSummaryServicePropertyTest.php`
    - **Property 7: Validasi status enum menolak nilai tidak valid**
    - Gunakan dataset 100+ iterasi dengan string acak yang bukan HADIR/SAKIT/IZIN/ALPA
    - Validasi: `Attendance::create(['status' => $invalidStatus, ...])` gagal validasi atau throw exception
    - _Requirements: 10.1_

- [x] 14. Final checkpoint — Jalankan seluruh test suite
  - Jalankan `php artisan test --compact` untuk memastikan semua tests pass
  - Jalankan `vendor/bin/pint --dirty --format agent` untuk memastikan code style konsisten
  - Pastikan tidak ada N+1 query dengan memeriksa eager loading di semua `getEloquentQuery()` dan `modifyQueryUsing()`
  - Tanyakan ke user jika ada pertanyaan sebelum dianggap selesai.

## Notes

- Task bertanda `*` bersifat opsional dan dapat dilewati untuk MVP yang lebih cepat
- Tabel `attendances` dan model `Attendance` sudah ada — tidak perlu migration baru
- Ikuti pola `KbmResource` yang sudah ada untuk semua resource baru
- Setiap resource menggunakan struktur direktori `Pages/`, `Schemas/`, `Tables/` yang konsisten
- Gunakan `Attendance::upsert()` dengan `uniqueBy: ['kbm_id', 'student_id']` untuk semua operasi simpan
- Property tests menggunakan Pest datasets dengan random generation (minimum 100 iterasi untuk pure functions, 50 iterasi untuk tests yang melibatkan database)
- Jalankan `vendor/bin/pint --dirty --format agent` setelah setiap task yang memodifikasi PHP files
