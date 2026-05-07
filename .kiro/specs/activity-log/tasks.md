# Implementation Plan: Activity Log (Audit Trail)

## Overview

Extend sistem `activity_logs` yang sudah ada — tambah kolom `log_name` dan `properties`, buat trait `LogsActivity`, listener auth, dan halaman Filament. Tidak ada dependency baru. Semua tulis ke tabel yang sama via model `ActivityLog` yang sudah ada.

## Tasks

- [x] 1. Migration: extend tabel `activity_logs`
  - Buat migration baru untuk menambah kolom `log_name` (varchar 100, nullable) dan `properties` (json, nullable) ke tabel `activity_logs`
  - Ubah kolom `user_id` dari `NOT NULL` menjadi nullable (drop foreign key constraint, ubah kolom, re-add foreign key dengan `nullOnDelete()`)
  - Tambah index pada `log_name`, `action`, dan `created_at` untuk performa filter dan sort
  - _Requirements: 2.6, 8.1, 8.2_

- [x] 2. Update model `ActivityLog`
  - Tambah `log_name` dan `properties` ke `$fillable`
  - Tambah cast `'properties' => 'array'` di method `casts()`
  - Update `ActivityLogFactory`: tambah `log_name` dan `properties` ke `definition()`, ubah `user_id` agar bisa null
  - _Requirements: 2.1, 2.2, 2.6_

- [x] 3. Buat trait `LogsActivity`
  - Buat file `app/Models/Traits/LogsActivity.php`
  - Implementasi `bootLogsActivity()` yang mendaftarkan Eloquent model events `created`, `updated`, `deleted`
  - Implementasi `writeActivityLog()` dengan try/catch silent — jika gagal, log ke Laravel logger tapi jangan propagate exception
  - Untuk `updated`: isi `properties` dengan `['old' => $model->getOriginal(), 'new' => $model->getDirty()]`
  - Tambah method `getActivityLogName(): string` yang mengembalikan `'general'` sebagai default (di-override per model)
  - _Requirements: 2.1, 2.2, 2.3, 2.6_

  - [ ]* 3.1 Tulis property test: causer invariant (Property 1)
    - **Property 1: Causer invariant** — untuk setiap user yang authenticated melakukan operasi model, `ActivityLog.user_id` harus sama dengan `user.id`
    - Gunakan Pest dataset dengan beberapa model yang pakai trait
    - **Validates: Requirements 2.3, 9.6**

  - [ ]* 3.2 Tulis property test: model event completeness (Property 2)
    - **Property 2: Model event logging completeness** — setiap operasi `created`/`updated`/`deleted` pada model dengan trait menghasilkan tepat satu `ActivityLog` dengan `action` dan `entity_type` yang benar
    - **Validates: Requirements 2.1**

  - [ ]* 3.3 Tulis property test: update properties round-trip (Property 3)
    - **Property 3: Update properties round-trip** — setelah update, `properties['old'][field] !== properties['new'][field]` untuk setiap field yang berubah
    - **Validates: Requirements 2.2**

- [x] 4. Tambahkan trait `LogsActivity` ke model-model yang relevan
  - Tambah `use LogsActivity;` dan override `getActivityLogName()` pada: `Attendance` (`'absensi'`), `Payment` (`'spp'`), `Invoice` (`'spp'`), `Schedule` (`'jadwal'`), `User` (`'user'`), `Student` (`'siswa'`), `Teacher` (`'guru'`)
  - Cek apakah model `Rapor` / `Grade` ada — jika ada, tambahkan dengan `log_name = 'rapor'`
  - Refactor `LessonPlan` dan `Kbm`: tambah trait (untuk CRUD otomatis) + override `getActivityLogName()` (`'rpp'` dan `'kbm'`), pertahankan `recordActivity()` manual untuk domain events (submit, approve, revise)
  - _Requirements: 2.1, 2.4, 4.3, 4.4, 4.5_

- [x] 5. Buat `AuthActivityListener`
  - Buat file `app/Listeners/AuthActivityListener.php`
  - Implementasi `handleLogin(Login $event)` — tulis `ActivityLog` dengan `action='login'`, `log_name='auth'`, `entity_type=User::class`, `entity_id=$event->user->id`
  - Implementasi `handleLogout(Logout $event)` — guard null check pada `$event->user`, tulis `ActivityLog` dengan `action='logout'`, `log_name='auth'`
  - Daftarkan listener di `AppServiceProvider::boot()` menggunakan `Event::listen()` (pola sama dengan `registerGlobalDeletionValidation()`)
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

  - [ ]* 5.1 Tulis property test: auth event logging (Property 4)
    - **Property 4: Auth event logging** — login berhasil menghasilkan `ActivityLog` dengan `action='login'`, `log_name='auth'`, `user_id=user.id`; logout menghasilkan `action='logout'`
    - **Validates: Requirements 3.1, 3.2**

  - [ ]* 5.2 Tulis property test: failed login produces no log (Property 5)
    - **Property 5: Failed login produces no log** — login dengan kredensial salah tidak membuat `ActivityLog` apapun
    - **Validates: Requirements 3.4**

- [x] 6. Checkpoint — pastikan semua test pass
  - Jalankan `php artisan test --compact --filter=ActivityLog`
  - Pastikan migration berjalan bersih: `php artisan migrate`
  - Tanyakan ke user jika ada pertanyaan sebelum lanjut

- [x] 7. Buat Blade view untuk `ActivityLogPage`
  - Buat file `resources/views/filament/pages/activity-log-page.blade.php`
  - Ikuti pola `temporary-access-log-list.blade.php` yang sudah ada — render `{{ $this->table }}`
  - _Requirements: 5.1, 7.2_

- [x] 8. Buat `ActivityLogPage` Filament
  - Buat file `app/Filament/Pages/ActivityLogPage.php` menggunakan `php artisan make:filament-page ActivityLogPage --no-interaction`
  - Implementasi `Page implements HasTable` dengan `use InteractsWithTable`
  - Set properti: `$navigationIcon = Heroicon::OutlinedClipboardDocumentList`, `$navigationLabel = 'Activity Log'`, `$slug = 'activity-log'`, `$navigationGroup = 'Sistem'`, `$navigationSort = 100`
  - Implementasi `canAccess()`: return `auth()->user()?->role === 'super_admin'`
  - Implementasi `table(Table $table)`:
    - Query: `ActivityLog::query()->with('user')->latest('created_at')`
    - Kolom: `user.name` (dengan email sebagai description), `action` (badge dengan warna per event), `log_name` (badge), entity (`entity_type` + `entity_id` via computed state), `description` (wrap, limit 80 char), `created_at` (format `d M Y H:i` + since)
    - Badge colors: `created`→`success`, `updated`→`warning`, `deleted`→`danger`, `login`/`logout`→`info`, `downloaded`/`generated`→`primary`, `approved`→`success`
    - Filters: `SelectFilter` untuk `user_id` (searchable), `SelectFilter` untuk `action`, `SelectFilter` untuk `log_name`, custom `Filter` untuk date range pada `created_at`
    - Search: pada `description` dan `user.name`
    - Default sort: `created_at` desc, page size 25
  - _Requirements: 1.1, 1.4, 5.1–5.6, 6.1–6.6, 7.1–7.4, 8.2, 8.5_

  - [ ]* 8.1 Tulis property test: access control invariant (Property 6)
    - **Property 6: Access control invariant** — setiap role selain `super_admin` mendapat 403 saat akses `/admin/activity-log`
    - Gunakan Pest dataset dengan semua role non-super_admin yang ada di aplikasi
    - **Validates: Requirements 1.1, 1.2**

  - [ ]* 8.2 Tulis property test: filter AND logic (Property 7)
    - **Property 7: Filter AND logic** — setiap kombinasi filter aktif hanya mengembalikan record yang memenuhi SEMUA kondisi filter
    - Implementasi sebagai feature test dengan multiple filter combinations via Livewire testing helpers
    - **Validates: Requirements 6.1–6.5**

  - [ ]* 8.3 Tulis property test: table is read-only (Property 8)
    - **Property 8: Table is read-only** — tidak ada action create, edit, atau delete yang terekspos di tabel untuk user manapun termasuk super_admin
    - **Validates: Requirements 8.5**

- [x] 9. Tulis feature tests untuk access control dan halaman
  - Buat `tests/Feature/ActivityLog/ActivityLogAccessTest.php`
  - Test: super_admin bisa akses halaman, setiap role non-super_admin dapat 403, unauthenticated redirect ke login
  - Buat `tests/Feature/ActivityLog/ActivityLogPageTest.php`
  - Test: tabel menampilkan kolom yang benar, filter `user_id`/`action`/`log_name`/date-range bekerja, tidak ada action create/edit/delete, search pada description
  - _Requirements: 1.1, 1.2, 1.3, 5.1–5.6, 6.1–6.6, 8.5, 9.1, 9.4, 9.5_

- [x] 10. Tulis feature tests untuk model event logging dan auth logging
  - Buat `tests/Feature/ActivityLog/ModelActivityLoggingTest.php`
  - Test: create/update/delete model dengan trait → ActivityLog dibuat dengan action, entity_type, user_id yang benar; tanpa auth → user_id null
  - Buat `tests/Feature/ActivityLog/AuthActivityLoggingTest.php`
  - Test: login berhasil → ActivityLog dengan action='login'; logout → action='logout'; login gagal → tidak ada ActivityLog
  - _Requirements: 2.1–2.6, 3.1–3.4, 9.2, 9.3_

- [x] 11. Final checkpoint — semua test pass
  - Jalankan `php artisan test --compact --filter=ActivityLog`
  - Jalankan `vendor/bin/pint --dirty --format agent` untuk format semua file PHP yang diubah
  - Tanyakan ke user jika ada pertanyaan sebelum selesai

## Notes

- Task bertanda `*` bersifat opsional dan bisa dilewati untuk MVP yang lebih cepat
- `user_id` di tabel harus diubah ke nullable — perlu drop dan re-add foreign key constraint
- `LessonPlan` dan `Kbm` sudah punya `recordActivity()` manual — trait hanya menambah CRUD otomatis, jangan hapus method yang sudah ada
- `ActivityLogPage` mengikuti pola `TemporaryAccessLogList` — `Page implements HasTable` + `use InteractsWithTable`
- Semua penulisan ke `activity_logs` harus dibungkus try/catch silent agar tidak mengganggu operasi utama
