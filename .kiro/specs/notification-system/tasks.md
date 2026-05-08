# Implementation Plan: Notification System

## Overview

Implementasi sistem notifikasi global menggunakan Laravel Model Observer + Service pattern + Livewire widget. Pendekatan incremental: mulai dari data layer (composite index), lalu service, observer, widget, dan diakhiri dengan registrasi serta wiring ke panel Filament.

## Tasks

- [x] 1. Tambahkan composite index pada tabel `notifications`
  - Buat migration baru untuk menambahkan index `idx_user_created` pada kolom `(user_id, created_at)` di tabel `notifications`
  - Jalankan migration
  - _Requirements: 6.3, 6.4_

- [x] 2. Implementasi `NotificationService`
  - [x] 2.1 Buat class `app/Services/NotificationService.php`
    - Implementasikan 6 method publik: `createForLessonPlanPending()`, `createForLessonPlanApproved()`, `createForLessonPlanRevised()`, `createForKbmPending()`, `createForKbmApproved()`, `createForKbmRevised()`
    - Setiap method membuat record `Notification` dengan format judul dan pesan sesuai tabel di design
    - Method untuk PENDING mengirim ke semua user `kepala_sekolah` aktif; method APPROVED/REVISED mengirim ke user guru pemilik model
    - Gunakan relasi `$lessonPlan->teacher->user` dan `$kbm->schedule->teacher->user` untuk resolve penerima
    - Jika relasi tidak valid (null), log error dan return early tanpa membuat notifikasi
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.3, 2.4, 2.5, 3.1, 3.2, 3.3, 3.4, 4.1, 4.2, 4.3, 4.4, 4.5, 5.1, 5.2, 5.3, 5.4_

  - [ ]* 2.2 Tulis feature test untuk `NotificationService` — skenario spesifik
    - Test setiap method membuat notifikasi dengan konten yang benar (judul dan pesan sesuai format)
    - Edge case: tidak ada kepsek aktif → tidak ada notifikasi, tidak ada exception
    - Edge case: `LessonPlan` tanpa relasi `teacher` → tidak ada notifikasi, error di-log
    - Edge case: `Kbm` tanpa relasi `schedule`/`teacher` → tidak ada notifikasi, error di-log
    - _Requirements: 1.4, 2.5, 4.5_

  - [ ]* 2.3 Tulis property test: **Property 1** — Notifikasi ke kepsek saat submission
    - Generator: jumlah kepsek aktif (0–10), satu `LessonPlan`/`Kbm` dengan status PENDING
    - Assertion: `count(notifications created) === count(active kepsek users)`
    - **Property 1: Notifikasi ke kepsek saat submission**
    - **Validates: Requirements 1.1, 3.1**

  - [ ]* 2.4 Tulis property test: **Property 2** — Konten notifikasi submission ke kepsek
    - Generator: nama guru acak, nama mapel acak, nama kelas acak, tanggal acak
    - Assertion: judul mengandung nama guru + nama mapel; pesan mengandung nama kelas + tanggal
    - **Property 2: Konten notifikasi submission ke kepsek**
    - **Validates: Requirements 1.2, 1.3, 3.2, 3.3**

  - [ ]* 2.5 Tulis property test: **Property 3** — Notifikasi ke guru saat review
    - Generator: `LessonPlan`/`Kbm` dengan teacher acak, status APPROVED atau REVISED
    - Assertion: tepat satu notifikasi dibuat untuk `teacher->user->id` yang benar
    - **Property 3: Notifikasi ke guru saat review**
    - **Validates: Requirements 2.1, 2.2, 4.1, 4.2**

  - [ ]* 2.6 Tulis property test: **Property 4** — Konten notifikasi REVISED mengandung `revision_note`
    - Generator: string `revision_note` acak (termasuk karakter khusus, unicode)
    - Assertion: `str_contains($notification->message, $revisionNote)`
    - **Property 4: Konten notifikasi hasil review mengandung revision_note**
    - **Validates: Requirements 2.4, 4.4**

  - [ ]* 2.7 Tulis property test: **Property 5** — Persistensi notifikasi dengan nilai awal yang benar
    - Generator: data notifikasi acak (user_id, title, message)
    - Assertion: `is_read === false`, `created_at !== null`, `user_id` valid, `title` dan `message` tidak kosong
    - **Property 5: Persistensi notifikasi dengan nilai awal yang benar**
    - **Validates: Requirements 5.1, 5.2, 5.3, 5.4**

- [x] 3. Checkpoint — Pastikan semua test service lulus
  - Pastikan semua test lulus, tanyakan kepada user jika ada pertanyaan.

- [x] 4. Implementasi `LessonPlanObserver` dan `KbmObserver`
  - [x] 4.1 Buat `app/Observers/LessonPlanObserver.php`
    - Inject `NotificationService` via constructor
    - Implementasikan method `updated()`: cek `wasChanged('status')`, lalu dispatch ke method service yang sesuai via `match()`
    - Bungkus seluruh logika dalam `try-catch (Throwable $e)` dan log error tanpa melempar ulang exception
    - _Requirements: 10.1, 10.2, 10.3, 10.4_

  - [x] 4.2 Buat `app/Observers/KbmObserver.php`
    - Struktur identik dengan `LessonPlanObserver`, mendelegasikan ke method `createForKbm*`
    - _Requirements: 10.1, 10.2, 10.3, 10.4_

  - [ ]* 4.3 Tulis feature test untuk `LessonPlanObserver`
    - Observer dipanggil dan membuat notifikasi saat kolom `status` berubah
    - Observer tidak membuat notifikasi saat kolom non-status berubah (topic, revision_note, dll.)
    - Exception dalam service tidak menggagalkan update model (status tetap tersimpan di DB)
    - _Requirements: 10.2, 10.3, 10.4_

  - [ ]* 4.4 Tulis feature test untuk `KbmObserver`
    - Struktur identik dengan test `LessonPlanObserver`
    - _Requirements: 10.2, 10.3, 10.4_

  - [ ]* 4.5 Tulis property test: **Property 9** — Observer hanya bereaksi saat status berubah
    - Generator: field non-status acak (`topic`, `revision_note`, `file_path`, dll.) dengan nilai acak
    - Assertion: tidak ada notifikasi baru dibuat setelah update field non-status
    - **Property 9: Observer hanya bereaksi saat status berubah**
    - **Validates: Requirements 10.3**

  - [ ]* 4.6 Tulis property test: **Property 10** — Kegagalan notifikasi tidak menggagalkan update model
    - Setup: mock `NotificationService` untuk throw exception
    - Assertion: model berhasil diupdate di database, error tercatat di log
    - **Property 10: Kegagalan notifikasi tidak menggagalkan update model**
    - **Validates: Requirements 10.4**

- [x] 5. Registrasi Observer di `AppServiceProvider`
  - Tambahkan `LessonPlan::observe(LessonPlanObserver::class)` dan `Kbm::observe(KbmObserver::class)` di method `boot()` pada `app/Providers/AppServiceProvider.php`
  - _Requirements: 10.1, 10.2_

- [x] 6. Checkpoint — Pastikan semua test observer lulus
  - Pastikan semua test lulus, tanyakan kepada user jika ada pertanyaan.

- [~] 7. Implementasi `NotificationWidget` (Livewire Component)
  - [x] 7.1 Buat Livewire component `app/Livewire/NotificationWidget.php`
    - Definisikan public properties: `$notifications` (koleksi) dan `$unreadCount` (int)
    - Implementasikan `render()`: query 10 notifikasi terbaru milik `auth()->id()` diurutkan `created_at DESC`, hitung `unreadCount`
    - Implementasikan `markAsRead(string $notificationId)`: update `is_read = true` hanya untuk notifikasi dengan `user_id = auth()->id()`
    - Implementasikan `markAllAsRead()`: update semua notifikasi milik user menjadi `is_read = true`
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 7.1, 7.2, 7.3, 7.4, 9.1, 9.2, 9.3_

  - [x] 7.2 Buat Blade view `resources/views/livewire/notification-widget.blade.php`
    - Tambahkan `wire:poll.15s` pada root element
    - Tampilkan bell icon dengan badge numerik `unreadCount` (hanya tampil jika > 0)
    - Tampilkan dropdown daftar notifikasi: judul, pesan ringkas, waktu relatif (`diffForHumans()`), dan indikator visual untuk notifikasi belum dibaca
    - Tambahkan tombol "Tandai Semua Dibaca" yang memanggil `wire:click="markAllAsRead"`
    - Setiap item notifikasi memanggil `wire:click="markAsRead('{{ $notification->id }}')"` saat diklik
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 7.1, 7.2, 7.3, 8.1, 8.2, 8.3, 8.4_

  - [ ]* 7.3 Tulis feature test untuk `NotificationWidget`
    - Widget ter-render di panel guru dan kepsek
    - `markAsRead()` mengubah `is_read` menjadi true untuk notifikasi milik user sendiri
    - `markAllAsRead()` mengubah semua notifikasi user menjadi dibaca
    - User tidak bisa mark-as-read notifikasi milik user lain
    - Widget hanya menampilkan notifikasi milik user yang sedang login
    - _Requirements: 6.1, 7.1, 7.2, 7.4, 9.1, 9.2_

  - [ ]* 7.4 Tulis property test: **Property 6** — Isolasi notifikasi per user
    - Generator: dua user berbeda, N notifikasi untuk masing-masing
    - Assertion: query dalam konteks user A tidak mengembalikan notifikasi user B
    - **Property 6: Isolasi notifikasi per user**
    - **Validates: Requirements 9.1, 9.2, 9.3**

  - [ ]* 7.5 Tulis property test: **Property 7** — Otorisasi mark-as-read
    - Generator: dua user berbeda, notifikasi milik user B
    - Assertion: setelah user A memanggil `markAsRead` dengan ID notifikasi user B, `is_read` notifikasi user B tetap false
    - **Property 7: Otorisasi mark-as-read**
    - **Validates: Requirements 7.4**

  - [ ]* 7.6 Tulis property test: **Property 8** — Mark-all-as-read mengubah semua notifikasi user
    - Generator: user dengan N notifikasi belum dibaca (N: 1–20)
    - Assertion: setelah `markAllAsRead()`, semua `is_read === true` dan `unreadCount === 0`
    - **Property 8: Mark-all-as-read mengubah semua notifikasi user**
    - **Validates: Requirements 7.2**

  - [ ]* 7.7 Tulis property test: **Property 11** — Badge menampilkan unread count yang akurat
    - Generator: user dengan N notifikasi (campuran read/unread acak)
    - Assertion: `widget->unreadCount === notifications->where('is_read', false)->count()`
    - **Property 11: Badge menampilkan unread count yang akurat**
    - **Validates: Requirements 6.2**

  - [ ]* 7.8 Tulis property test: **Property 12** — Widget hanya menampilkan maksimal 10 notifikasi terbaru
    - Generator: user dengan N notifikasi (N: 11–30), dengan `created_at` acak
    - Assertion: widget mengembalikan tepat 10 notifikasi, dan kesemuanya adalah 10 dengan `created_at` terbesar
    - **Property 12: Widget hanya menampilkan maksimal 10 notifikasi terbaru**
    - **Validates: Requirements 6.4**

- [x] 8. Registrasi `NotificationWidget` di panel Filament
  - Tambahkan `renderHook()` di `GuruPanelProvider` untuk merender `@livewire('notification-widget')` pada hook `PanelsRenderHook::USER_MENU_BEFORE`
  - Tambahkan `renderHook()` yang sama di `KepsekPanelProvider`
  - _Requirements: 6.1, 8.1, 8.4_

- [x] 10. Tambahkan `NotificationWidget` ke panel `student` dan `admin`
  - Tambahkan `renderHook()` di `StudentPanelProvider` untuk merender `@livewire('notification-widget')` pada hook `PanelsRenderHook::USER_MENU_BEFORE`
  - Tambahkan `renderHook()` yang sama di `AdminPanelProvider`
  - _Requirements: 6.1_

- [x] 11. Implementasi notifikasi Rapor, Pengumuman, dan Materi Baru
  - [x] 11.1 Tambahkan method `createForRaporApproved()` ke `NotificationService`
    - Resolve penerima via `$rapor->student->user`
    - Judul: `"Rapor [Nama Tahun Ajaran] Anda telah tersedia"`
    - Pesan: `"Rapor Anda telah disetujui. Silakan akses untuk melihat hasil belajar Anda."`
    - Jika relasi tidak valid, log error dan return early
    - _Requirements: 11.1, 11.2, 11.3, 11.4_

  - [x] 11.2 Tambahkan method `createForAnnouncement()` ke `NotificationService`
    - Resolve penerima via `User::where('is_active', true)->whereIn('role', $announcement->target_role)->get()`
    - Judul: `"Pengumuman: [Judul Pengumuman]"`
    - Pesan: 100 karakter pertama konten, diakhiri "..." jika terpotong
    - Jika `target_role` kosong atau tidak ada user yang cocok, tidak ada notifikasi, tidak ada exception
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5_

  - [x] 11.3 Tambahkan method `createForLessonPlanMaterial()` ke `NotificationService`
    - Resolve penerima via `$material->lessonPlan->schoolClass->students()->with('user')->active()->get()`
    - Judul: `"Materi baru: [original_filename] ([Nama Mapel])"`
    - Pesan: `"Kelas [Nama Kelas] · Diunggah oleh [Nama Guru]"`
    - Jika relasi tidak valid, log error dan return early
    - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.5_

  - [x] 11.4 Buat `app/Observers/RaporObserver.php`
    - Event `updated`, guard `wasChanged('status')`, hanya bereaksi saat status → `APPROVED`
    - Inject `NotificationService`, delegasikan ke `createForRaporApproved()`
    - Bungkus dalam `try-catch(\Throwable $e)` dengan `Log::error()`
    - _Requirements: 10.1, 10.3, 10.5_

  - [x] 11.5 Buat `app/Observers/AnnouncementObserver.php`
    - Event `created`
    - Inject `NotificationService`, delegasikan ke `createForAnnouncement()`
    - Bungkus dalam `try-catch(\Throwable $e)` dengan `Log::error()`
    - _Requirements: 10.1, 10.4, 10.5_

  - [x] 11.6 Buat `app/Observers/LessonPlanMaterialObserver.php`
    - Event `created`
    - Inject `NotificationService`, delegasikan ke `createForLessonPlanMaterial()`
    - Bungkus dalam `try-catch(\Throwable $e)` dengan `Log::error()`
    - _Requirements: 10.1, 10.4, 10.5_

  - [x] 11.7 Registrasi 3 observer baru di `AppServiceProvider`
    - Tambahkan `Rapor::observe(RaporObserver::class)`
    - Tambahkan `Announcement::observe(AnnouncementObserver::class)`
    - Tambahkan `LessonPlanMaterial::observe(LessonPlanMaterialObserver::class)`
    - _Requirements: 10.1, 10.2_

  - [x] 11.8 Tulis feature test untuk method-method baru di `NotificationService`
    - `createForRaporApproved`: notifikasi ke siswa pemilik rapor, konten benar, error handling
    - `createForAnnouncement`: notifikasi ke semua role yang cocok, tidak ke role lain, target_role kosong
    - `createForLessonPlanMaterial`: notifikasi ke siswa di kelas yang benar, tidak ke kelas lain, error handling
    - _Requirements: 11.1–11.4, 12.1–12.5, 13.1–13.5_

  - [x] 11.9 Tulis feature test untuk 3 observer baru
    - `RaporObserver`: bereaksi saat status → APPROVED, tidak bereaksi saat status lain berubah
    - `AnnouncementObserver`: bereaksi saat created
    - `LessonPlanMaterialObserver`: bereaksi saat created
    - _Requirements: 10.3, 10.4, 10.5_

- [ ] 9. Checkpoint final — Pastikan semua test lulus
  - Pastikan semua test lulus, tanyakan kepada user jika ada pertanyaan.

## Notes

- Task bertanda `*` bersifat opsional dan dapat dilewati untuk MVP yang lebih cepat
- Setiap task mereferensikan requirement spesifik untuk traceability
- Property test menggunakan library **eris/eris** dengan minimum 100 iterasi per property
- Unit test dan property test bersifat komplementer — keduanya direkomendasikan untuk coverage penuh
- Tabel `notifications` dan model `Notification` sudah ada; tidak diperlukan migrasi model baru
- Gunakan `php artisan make:` commands untuk membuat file baru sesuai konvensi Laravel
