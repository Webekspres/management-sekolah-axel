# Implementation Plan: RPP Materi Upload

## Overview

Memperluas modul RPP yang sudah ada dengan menambahkan kemampuan upload multiple file materi pembelajaran. Implementasi mencakup: model baru `LessonPlanMaterial`, migrasi database, update panel Guru (Repeater upload), update panel Kepsek (RepeatableEntry read-only), resource baru di panel Siswa, cascade delete dengan penghapusan file fisik, dan access control per role.

## Tasks

- [x] 1. Buat migration dan model `LessonPlanMaterial`
  - Jalankan `php artisan make:migration create_lesson_plan_materials_table --no-interaction` untuk membuat file migrasi
  - Definisikan tabel `lesson_plan_materials` dengan kolom: `id` (ULID CHAR(26) PK), `lesson_plan_id` (CHAR(26) FK → `lesson_plans.id` ON DELETE CASCADE), `file_path` (VARCHAR(255) NOT NULL), `original_filename` (VARCHAR(255) NOT NULL)
  - Tambahkan index pada kolom `lesson_plan_id`
  - Jalankan `php artisan make:model LessonPlanMaterial --factory --no-interaction` untuk membuat model dan factory
  - Implementasikan model: `HasUlid`, `$timestamps = false`, `$fillable`, relasi `lessonPlan(): BelongsTo`, dan model event `deleting` yang menghapus file fisik dari `Storage::disk('public')` dengan `try/catch (\Throwable)` agar tidak throw exception jika file tidak ada
  - Implementasikan factory `LessonPlanMaterialFactory` dengan state `withFakeFile()` yang membuat file dummy di storage untuk keperluan testing
  - _Requirements: 1.3, 1.7, 5.1, 5.3_

- [x] 2. Update model `LessonPlan` — relasi dan cascade delete
  - Tambahkan relasi `materials(): HasMany` ke `LessonPlanMaterial` pada model `LessonPlan`
  - Tambahkan atau update method `booted()` pada `LessonPlan` untuk cascade delete: saat `LessonPlan` dihapus, iterasi `$lessonPlan->materials->each->delete()` agar model event `LessonPlanMaterial::deleting` terpanggil untuk setiap material (sehingga file fisik ikut terhapus)
  - _Requirements: 5.2_

- [x] 3. Checkpoint — Pastikan migrasi dan model berjalan
  - Jalankan `php artisan migrate --no-interaction` dan pastikan tidak ada error
  - Pastikan semua tests pass, tanyakan ke user jika ada pertanyaan

- [x] 4. Update panel Guru — tambah Repeater materi di `LessonPlanForm`
  - Buka `app/Filament/Guru/Resources/LessonPlans/Schemas/LessonPlanForm.php`
  - Tambahkan `Section` baru "Materi Pembelajaran" setelah section utama RPP
  - Di dalam section, tambahkan `Repeater::make('materials')->relationship()` dengan `FileUpload::make('file_path')` yang dikonfigurasi: `acceptedFileTypes()` untuk PDF/PPTX/XLSX/DOCX, `disk('public')`, `directory('lesson-plan-materials')`, `visibility('public')`, `preserveFilenames()`, `storeFileNamesIn('original_filename')`, `downloadable()`, `openable()`, `getDownloadableFileUrlUsing()` dan `getOpenableFileUrlUsing()` menggunakan `PublicStorageUrl::fromPublicDiskPath()`
  - Tambahkan helper private static `isMaterialLocked(?LessonPlan $record): bool` yang mengembalikan `true` jika status RPP adalah `PENDING` atau `APPROVED`
  - Terapkan `->disabled(fn (?LessonPlan $record) => self::isMaterialLocked($record))` pada Repeater
  - Terapkan `->deleteAction(fn (Action $action, ?LessonPlan $record) => $action->hidden(fn () => self::isMaterialLocked($record)))` pada Repeater untuk menyembunyikan tombol hapus saat terkunci
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 2.1, 2.2, 2.3, 2.4_

  - [ ]* 4.1 Tulis property test untuk validasi format file (Property 1)
    - **Property 1: Format file yang valid diterima, format tidak valid ditolak**
    - **Validates: Requirements 1.1, 1.5**
    - Buat `tests/Feature/Filament/Guru/LessonPlanMaterialUploadTest.php` dengan `php artisan make:test --pest LessonPlanMaterialUploadTest --no-interaction`
    - Tulis test yang menjalankan 100 iterasi: untuk setiap iterasi, pilih format acak dari `['pdf', 'pptx', 'xlsx', 'docx', 'jpg', 'png', 'txt', 'zip']`, upload ke RPP DRAFT, verifikasi diterima (format valid) atau ditolak dengan form error (format tidak valid)
    - Tag: `// Feature: rpp-materi-upload, Property 1: Format file valid diterima, tidak valid ditolak`

  - [ ]* 4.2 Tulis property test untuk multiple materi per RPP (Property 2)
    - **Property 2: Multiple materi tersimpan per RPP**
    - **Validates: Requirements 1.2, 1.7**
    - Dalam file test yang sama, tulis test 100 iterasi: untuk setiap iterasi, pilih N acak (1–5), upload N file ke RPP DRAFT, verifikasi tepat N record `LessonPlanMaterial` tersimpan di database berelasi ke RPP tersebut
    - Tag: `// Feature: rpp-materi-upload, Property 2: Multiple materi tersimpan per RPP`

  - [ ]* 4.3 Tulis property test untuk path dan nama file asli (Property 3)
    - **Property 3: File materi tersimpan di direktori yang benar dengan nama asli**
    - **Validates: Requirements 1.3, 1.4, 1.7**
    - Tulis test 100 iterasi: untuk setiap iterasi, upload file dengan nama acak ke RPP DRAFT, verifikasi `file_path` mengandung `lesson-plan-materials/` dan `original_filename` sama dengan nama file asli
    - Tag: `// Feature: rpp-materi-upload, Property 3: File tersimpan di direktori benar dengan nama asli`

  - [ ]* 4.4 Tulis property test untuk lock materi saat PENDING/APPROVED (Property 4)
    - **Property 4: Lock materi saat RPP PENDING atau APPROVED**
    - **Validates: Requirements 1.6, 2.3, 2.4**
    - Tulis test yang memverifikasi Repeater disabled dan tombol hapus tidak muncul saat RPP berstatus PENDING dan APPROVED; verifikasi sebaliknya saat DRAFT dan REVISED
    - Tag: `// Feature: rpp-materi-upload, Property 4: Lock materi saat RPP PENDING atau APPROVED`

- [x] 5. Update panel Kepsek — tambah `RepeatableEntry` materi di `LessonPlanDetailInfolist`
  - Buka `app/Filament/Kepsek/Resources/LessonPlans/Schemas/LessonPlanDetailInfolist.php`
  - Tambahkan `Section` baru "Materi Pembelajaran" yang berisi `RepeatableEntry::make('materials')` dengan `TextEntry::make('original_filename')` yang dikonfigurasi sebagai link menggunakan `->url()` dengan `PublicStorageUrl::fromPublicDiskPath($record->file_path)` dan `shouldOpenInNewTab: true`
  - Tambahkan `->emptyLabel('Tidak ada materi yang dilampirkan.')` pada `RepeatableEntry`
  - Pastikan tidak ada tombol tambah/hapus (infolist bersifat read-only by design)
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

  - [ ]* 5.1 Tulis feature test untuk tampilan materi di panel Kepsek (Property 5)
    - **Property 5: Semua materi tampil di panel Guru dan Kepsek**
    - **Validates: Requirements 2.1, 2.2, 3.1, 3.2**
    - Buat `tests/Feature/Filament/Kepsek/LessonPlanMaterialKepsekTest.php`
    - Test: Kepsek dapat melihat daftar materi di halaman detail RPP; setiap materi menampilkan link yang membuka di tab baru; RPP tanpa materi menampilkan teks "Tidak ada materi yang dilampirkan."; panel Kepsek tidak menampilkan tombol tambah/hapus materi

- [x] 6. Buat `LessonPlanMaterialResource` di panel Siswa
  - Jalankan `php artisan make:filament-resource LessonPlanMaterial --no-interaction` atau buat file secara manual di `app/Filament/Student/Resources/LessonPlanMaterials/LessonPlanMaterialResource.php` mengikuti struktur resource yang sudah ada di panel Student
  - Implementasikan `canCreate(): bool { return false; }`, `canEdit(): bool { return false; }`, `canDelete(): bool { return false; }`
  - Implementasikan `getEloquentQuery()` yang memfilter: hanya `LessonPlanMaterial` dari RPP berstatus `APPROVED` dengan `class_id` sama dengan `class_id` siswa yang login; jika `auth()->user()->student` adalah null, kembalikan `whereRaw('1 = 0')` (konsisten dengan pola `AttendanceResource`)
  - Eager load `lessonPlan.subjectForDisplay` dan `lessonPlan.schoolClass`
  - Definisikan tabel dengan kolom: Mata Pelajaran (`lessonPlan.subjectForDisplay.name`), Topik (`lessonPlan.topic`), Kelas (`lessonPlan.schoolClass.name`), Nama File (`original_filename`), dan Action unduh menggunakan `PublicStorageUrl::fromPublicDiskPath()`
  - Pastikan tidak ada kolom atau link yang mengekspos `file_path` dari tabel `lesson_plans` (dokumen RPP)
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 6.3_

  - [ ]* 6.1 Tulis property test untuk akses siswa (Property 6)
    - **Property 6: Siswa hanya melihat materi dari RPP APPROVED kelas sendiri**
    - **Validates: Requirements 4.1, 4.4, 4.6**
    - Buat `tests/Feature/Filament/Student/LessonPlanMaterialStudentTest.php`
    - Tulis test 100 iterasi: untuk setiap iterasi, pilih status RPP acak (`DRAFT`, `PENDING`, `REVISED`, `APPROVED`) dan pilih acak apakah RPP dari kelas siswa atau kelas lain; verifikasi materi hanya muncul jika status `APPROVED` DAN kelas sama
    - Tag: `// Feature: rpp-materi-upload, Property 6: Siswa hanya melihat materi RPP APPROVED kelas sendiri`

  - [ ]* 6.2 Tulis property test untuk isolasi data RPP dari panel siswa (Property 7)
    - **Property 7: Panel siswa tidak menampilkan metadata dokumen RPP**
    - **Validates: Requirements 4.2, 4.5**
    - Tulis test yang memverifikasi halaman materi siswa tidak menampilkan link atau konten dari `file_path` tabel `lesson_plans`; siswa tidak dapat membuat/edit/hapus materi; siswa tanpa profil mendapat halaman kosong
    - Tag: `// Feature: rpp-materi-upload, Property 7: Panel siswa tidak menampilkan metadata dokumen RPP`

- [x] 7. Checkpoint — Pastikan semua tests pass
  - Jalankan `php artisan test --compact` dan pastikan semua tests pass
  - Tanyakan ke user jika ada pertanyaan

- [x] 8. Implementasikan unit tests untuk model events
  - Buat `tests/Unit/Models/LessonPlanMaterialTest.php` dengan `php artisan make:test --pest --unit LessonPlanMaterialTest --no-interaction`
  - [ ]* 8.1 Tulis property test untuk penghapusan file fisik (Property 8)
    - **Property 8: Penghapusan material menghapus file fisik**
    - **Validates: Requirements 5.1, 5.3**
    - Tulis test 100 iterasi: buat `LessonPlanMaterial` dengan file dummy di storage, hapus record, verifikasi file fisik tidak ada di `Storage::disk('public')`; juga verifikasi bahwa jika file tidak ada, penghapusan record tetap berhasil tanpa exception
    - Tag: `// Feature: rpp-materi-upload, Property 8: Penghapusan material menghapus file fisik`

  - [ ]* 8.2 Tulis property test untuk cascade delete LessonPlan (Property 9)
    - **Property 9: Penghapusan LessonPlan menghapus semua materialnya**
    - **Validates: Requirements 5.2**
    - Tulis test 100 iterasi: buat `LessonPlan` dengan N material (N acak 1–5) beserta file dummy, hapus `LessonPlan`, verifikasi semua N record `LessonPlanMaterial` terhapus dari database dan semua file fisiknya tidak ada di storage
    - Tag: `// Feature: rpp-materi-upload, Property 9: Penghapusan LessonPlan menghapus semua materialnya`

- [x] 9. Implementasikan access control tests
  - Buat `tests/Feature/Filament/AccessControlTest.php` (atau tambahkan ke file yang sudah ada jika ada)
  - [ ]* 9.1 Tulis property test untuk access control berbasis role (Property 10)
    - **Property 10: Akses kontrol berdasarkan role**
    - **Validates: Requirements 7.1, 7.2, 7.3, 7.4**
    - Tulis test yang memverifikasi: siswa mendapat 403 saat akses halaman RPP di panel Guru; siswa mendapat 403 saat akses halaman RPP di panel Kepsek; ortu mendapat 403 saat akses halaman RPP Guru, Kepsek, dan halaman materi siswa; guru hanya melihat RPP miliknya sendiri (tidak ada RPP guru lain yang muncul)
    - Tag: `// Feature: rpp-materi-upload, Property 10: Akses kontrol berdasarkan role`

- [x] 10. Final checkpoint — Pastikan semua tests pass dan integrasi lengkap
  - Jalankan `vendor/bin/pint --dirty --format agent` untuk memastikan code style konsisten
  - Jalankan `php artisan test --compact` dan pastikan semua tests pass
  - Verifikasi tidak ada file orphan atau kode yang tidak terintegrasi
  - Tanyakan ke user jika ada pertanyaan

## Notes

- Tasks bertanda `*` bersifat opsional dan dapat dilewati untuk MVP yang lebih cepat
- Setiap task mereferensikan requirements spesifik untuk traceability
- Property tests menjalankan minimum 100 iterasi sesuai strategi testing di design document
- Cascade delete dilakukan via Eloquent model events (bukan hanya `ON DELETE CASCADE` di DB) agar file fisik ikut terhapus
- URL file selalu menggunakan `PublicStorageUrl::fromPublicDiskPath()` — konsisten dengan pola yang sudah ada
- Panel Siswa hanya mengekspos `LessonPlanMaterial`, tidak pernah `file_path` dari tabel `lesson_plans`
