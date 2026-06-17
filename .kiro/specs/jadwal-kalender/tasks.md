# Implementation Plan: Jadwal Kalender

## Overview

Implementasi widget kalender interaktif (`JadwalKalenderWidget`) yang menampilkan jadwal pelajaran dalam format bulanan menggunakan library `guava/calendar`. Widget di-inject ke halaman index `ScheduleResource` dan mendukung scoping berdasarkan role pengguna.

## Tasks

- [x] 1. Install dan konfigurasi library `guava/calendar`
  - [x] 1.1 Install package via Composer dan konfigurasi CSS
    - Jalankan `composer require guava/calendar` dan pastikan versi kompatibel dengan Filament v5
    - Tambahkan direktif `@source '../../../../vendor/guava/calendar/resources/**/*'` dan `@import '../../../../vendor/guava/calendar/resources/css/theme.css'` ke `resources/css/app.css`
    - Jalankan `php artisan filament:assets` untuk mempublikasikan aset JavaScript
    - _Requirements: 1.1, 1.2, 1.3_

- [x] 2. Buat kelas `JadwalKalenderWidget` dengan struktur dasar
  - [x] 2.1 Buat file widget dan definisikan properti dasar
    - Buat file `app/Filament/Clusters/Academic/Resources/Schedules/Widgets/JadwalKalenderWidget.php`
    - Extend `Guava\Calendar\Filament\CalendarWidget`
    - Set `$calendarView = CalendarViewType::DayGridMonth` sebagai tampilan default
    - Set `$dateClickEnabled = true` dan `$heading = 'Kalender Jadwal Pelajaran'`
    - Deklarasikan stub kosong untuk `getEvents()`, `onDateClick()`, `buildScopedQuery()`, `resolveConcreteDate()`, dan `buildEventTitle()`
    - _Requirements: 2.1, 2.2_

- [x] 3. Implementasi `getEvents()` — query, eager load, dan konversi ke `CalendarEvent`
  - [x] 3.1 Implementasi `buildScopedQuery()` untuk query dasar dengan eager loading
    - Query `Schedule::query()` dengan eager load `schoolClass`, `subject`, `teacher.user`
    - Kembalikan `Builder` tanpa filter role (role scoping ditangani di task 5)
    - _Requirements: 4.1, 4.2_

  - [x] 3.2 Implementasi `resolveConcreteDate()` untuk konversi `day_of_week` ke tanggal konkret
    - Terima parameter `int $dayOfWeek` dan `CarbonPeriod $period`
    - Iterasi `$period`, kembalikan `Carbon` pertama yang `dayOfWeek`-nya cocok, atau `null` jika tidak ada
    - Validasi `$dayOfWeek` berada dalam rentang 0–6; skip jika tidak valid
    - _Requirements: 4.3_

  - [x] 3.3 Implementasi `getEvents(FetchInfo $info)` — iterasi rentang dan bangun `CalendarEvent`
    - Buat `CarbonPeriod` dari `$info->start` hingga `$info->end`
    - Untuk setiap hari dalam periode, filter jadwal yang `day_of_week`-nya cocok
    - Panggil `resolveConcreteDate()` untuk mendapatkan tanggal konkret
    - Kembalikan collection `CalendarEvent` (logika penggabungan ditambahkan di task 4)
    - _Requirements: 4.1, 4.3, 4.4_

  - [ ]* 3.4 Tulis property test untuk Property 2: Konsistensi Tanggal Event
    - **Property 2: Konsistensi Tanggal Event dengan `day_of_week`**
    - **Validates: Requirements 4.3, 4.B**
    - Generate jadwal acak dengan `day_of_week` acak (0–6) dan rentang tanggal yang mencakup hari tersebut
    - Assert: `event->start->dayOfWeek === schedule->day_of_week` untuk setiap event yang dihasilkan
    - Minimum 100 iterasi via Pest dataset

- [x] 4. Implementasi logika penggabungan event
  - [x] 4.1 Implementasi `buildEventTitle()` dan logika groupBy dalam `getEvents()`
    - Implementasi `buildEventTitle(string $startTime, string $subjectName, array $classNames): string`
    - Format: `HH:MM: [Nama Mapel] - [Kelas A], [Kelas B]` (urutkan `$classNames` secara alfabetis sebelum join)
    - Dalam `getEvents()`, kelompokkan jadwal berdasarkan key `"{subject_id}_{day_of_week}_{start_time}_{end_time}"`
    - Untuk setiap grup, kumpulkan nama kelas, urutkan alfabetis, lalu buat satu `CalendarEvent`
    - Skip jadwal dengan relasi `schoolClass` atau `subject` null (data korup)
    - _Requirements: 5.1, 5.2, 5.3, 5.4_

  - [ ]* 4.2 Tulis property test untuk Property 1: Kelengkapan Event dalam Rentang Tanggal
    - **Property 1: Kelengkapan Event dalam Rentang Tanggal**
    - **Validates: Requirements 4.1, 5.1, 5.B**
    - Generate rentang tanggal acak (1–4 minggu) dan jadwal acak dengan `day_of_week` acak
    - Assert: `count(events) === count(unique subject+day+start+end combinations in range)`
    - Minimum 100 iterasi via Pest dataset

  - [ ]* 4.3 Tulis property test untuk Property 3: Format Judul Event
    - **Property 3: Format Judul Event**
    - **Validates: Requirements 5.2, 5.3**
    - Generate grup jadwal acak (1–5 kelas per grup) dengan nama mata pelajaran dan kelas acak
    - Assert: judul event cocok dengan pola `/^\d{2}:\d{2}: .+ - .+$/`
    - Minimum 100 iterasi via Pest dataset

  - [ ]* 4.4 Tulis property test untuk Property 4: Urutan Alfabetis Nama Kelas
    - **Property 4: Urutan Alfabetis Nama Kelas**
    - **Validates: Requirements 5.4, 5.A**
    - Generate grup jadwal acak dengan 2–5 kelas dalam urutan acak
    - Assert: nama kelas dalam judul event terurut secara alfabetis ascending
    - Minimum 100 iterasi via Pest dataset

- [x] 5. Checkpoint — Pastikan semua tests lulus
  - Pastikan semua tests lulus, tanyakan kepada user jika ada pertanyaan.

- [x] 6. Implementasi scoping berdasarkan role pengguna
  - [x] 6.1 Implementasi logika scoping role dalam `buildScopedQuery()`
    - Jika role `guru`: tambahkan `->where('teacher_id', $teacherId)` — jika user tidak punya relasi `teacher`, kembalikan query dengan `->whereRaw('1=0')`
    - Jika role `super_admin` atau `kepala_sekolah`: tidak ada filter tambahan (semua jadwal)
    - Tangani temporary policy grant dari `TemporaryAccessManager` sesuai level akses yang diberikan
    - _Requirements: 6.1, 6.2, 6.3, 6.4_

  - [ ]* 6.2 Tulis property test untuk Property 5: Scoping Jadwal untuk Role Guru
    - **Property 5: Scoping Jadwal untuk Role Guru**
    - **Validates: Requirements 6.1**
    - Generate N guru acak (2–5) dengan M jadwal per guru (1–10)
    - Assert: events untuk user dengan role `guru` hanya berisi jadwal milik teacher tersebut — tidak ada event dari guru lain
    - Minimum 100 iterasi via Pest dataset

  - [ ]* 6.3 Tulis property test untuk Property 6: Akses Penuh untuk Admin dan Kepala Sekolah
    - **Property 6: Akses Penuh untuk Admin dan Kepala Sekolah**
    - **Validates: Requirements 6.2**
    - Generate N guru acak (2–5) dengan M jadwal per guru (1–10)
    - Assert: events untuk user dengan role `super_admin` atau `kepala_sekolah` berisi semua jadwal dari semua guru
    - Minimum 100 iterasi via Pest dataset

- [x] 7. Implementasi `onDateClick()` — beralih ke tampilan harian
  - [x] 7.1 Implementasi method `onDateClick(DateClickInfo $info)`
    - Panggil `$this->setOption('view', 'timeGridDay')`
    - Panggil `$this->setOption('date', $info->date->toIso8601String())`
    - _Requirements: 3.1_

  - [ ]* 7.2 Tulis unit test untuk `onDateClick()`
    - Test bahwa `setOption` dipanggil dengan argumen yang benar saat tanggal diklik
    - Test navigasi hari sebelumnya/berikutnya tersedia di tampilan harian (via library)
    - _Requirements: 3.1, 3.3, 3.4_

- [x] 8. Daftarkan widget di `ListSchedules` dan verifikasi integrasi
  - [x] 8.1 Tambahkan `getHeaderWidgets()` ke `ListSchedules`
    - Buka `app/Filament/Clusters/Academic/Resources/Schedules/Pages/ListSchedules.php`
    - Tambahkan method `getHeaderWidgets(): array` yang mengembalikan `[JadwalKalenderWidget::class]`
    - _Requirements: 2.5, 7.1, 7.3_

  - [ ]* 8.2 Tulis integration test untuk widget terdaftar dan CRUD tetap berfungsi
    - Test bahwa `JadwalKalenderWidget` muncul di `ListSchedules::getHeaderWidgets()`
    - Test bahwa halaman `CreateSchedule` dan `EditSchedule` masih dapat diakses dan berfungsi
    - Test bahwa pengguna tanpa role yang diizinkan tidak dapat mengakses halaman jadwal
    - _Requirements: 7.1, 7.2, 7.3_

- [x] 9. Checkpoint akhir — Pastikan semua tests lulus
  - Pastikan semua tests lulus, tanyakan kepada user jika ada pertanyaan.

## Notes

- Tasks bertanda `*` bersifat opsional dan dapat dilewati untuk MVP yang lebih cepat
- Setiap task mereferensikan requirements spesifik untuk traceability
- Property tests menggunakan Pest datasets untuk mensimulasikan property-based testing (minimum 100 iterasi)
- Unit tests dan property tests bersifat komplementer — keduanya diperlukan untuk coverage yang baik
- Scoping role di widget (`buildScopedQuery`) mereplikasi logika dari `ScheduleResource::getEloquentQuery()` agar widget bersifat self-contained
- Jalankan `vendor/bin/pint --dirty --format agent` setelah setiap perubahan PHP

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["2.1"] },
    { "id": 2, "tasks": ["3.1", "3.2"] },
    { "id": 3, "tasks": ["3.3"] },
    { "id": 4, "tasks": ["3.4", "4.1"] },
    { "id": 5, "tasks": ["4.2", "4.3", "4.4", "6.1"] },
    { "id": 6, "tasks": ["6.2", "6.3", "7.1"] },
    { "id": 7, "tasks": ["7.2", "8.1"] },
    { "id": 8, "tasks": ["8.2"] }
  ]
}
```
