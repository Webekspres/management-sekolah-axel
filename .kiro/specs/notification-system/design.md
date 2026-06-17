# Design Document: Notification System

## Overview

Sistem Notifikasi Global adalah subsistem yang secara otomatis mengirimkan notifikasi kepada semua role user yang relevan ketika terjadi event pada model domain. Cakupan diperluas dari versi awal (hanya RPP/KBM untuk guru & kepsek) menjadi global — mencakup rapor untuk siswa, pengumuman untuk semua role, dan materi baru untuk siswa di kelas terkait.

Notifikasi disimpan secara persisten di tabel `notifications` dan ditampilkan melalui bell icon di navbar **semua panel** Filament (`guru`, `kepsek`, `student`, `admin`). UI diperbarui otomatis via `wire:poll.15s` tanpa WebSocket.

### Alur Notifikasi

```mermaid
sequenceDiagram
    participant Actor as Any User
    participant Model as Domain Model
    participant Observer as Observer (5 jenis)
    participant Service as NotificationService
    participant DB as notifications table
    participant Widget as NotificationWidget (Livewire)

    Actor->>Model: create/update model
    Model->>Observer: created() / updated() event
    Observer->>Observer: validasi kondisi (status changed, dll)
    Observer->>Service: createFor*()
    Service->>DB: INSERT INTO notifications (...)
    Note over Widget: wire:poll.15s berjalan di background
    Widget->>DB: SELECT WHERE user_id = auth()->id()
    DB-->>Widget: data notifikasi terbaru
    Widget-->>Actor: badge & daftar notifikasi diperbarui
```

---

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│  Presentation Layer (semua panel Filament)                  │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  NotificationWidget (Livewire Component)             │   │
│  │  - Ditampilkan via PanelsRenderHook::USER_MENU_BEFORE│   │
│  │  - Di semua panel: guru, kepsek, student, admin      │   │
│  │  - wire:poll.15s untuk auto-refresh                  │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
         │ query / action
┌─────────────────────────────────────────────────────────────┐
│  Application Layer                                          │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  NotificationService (9 method publik)               │   │
│  │  RPP/KBM: createForLessonPlan*() / createForKbm*()   │   │
│  │  Rapor:   createForRaporApproved()                   │   │
│  │  Pengumuman: createForAnnouncement()                 │   │
│  │  Materi:  createForLessonPlanMaterial()              │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
         │ dipanggil oleh
┌─────────────────────────────────────────────────────────────┐
│  Observer Layer (5 observer)                                │
│  LessonPlanObserver · KbmObserver · RaporObserver           │
│  AnnouncementObserver · LessonPlanMaterialObserver          │
└─────────────────────────────────────────────────────────────┘
         │ mendengarkan event Eloquent
┌─────────────────────────────────────────────────────────────┐
│  Domain Layer (Models)                                      │
│  LessonPlan · Kbm · Rapor · Announcement · LessonPlanMaterial│
│  User · Teacher · Student · Schedule · SchoolClass          │
└─────────────────────────────────────────────────────────────┘
         │ persists to
┌─────────────────────────────────────────────────────────────┐
│  Data Layer                                                 │
│  notifications table (sudah ada, schema sudah sesuai)       │
└─────────────────────────────────────────────────────────────┘
```

---

## Components and Interfaces

### 1. Observer Layer

**`LessonPlanObserver`** & **`KbmObserver`** — tidak berubah dari implementasi sebelumnya. Event `updated`, guard `wasChanged('status')`.

**`RaporObserver`** — `app/Observers/RaporObserver.php`

- Event `updated`, guard `wasChanged('status')`
- Hanya bereaksi saat status → `APPROVED`
- Mendelegasikan ke `NotificationService::createForRaporApproved()`

**`AnnouncementObserver`** — `app/Observers/AnnouncementObserver.php`

- Event `created`
- Mendelegasikan ke `NotificationService::createForAnnouncement()`

**`LessonPlanMaterialObserver`** — `app/Observers/LessonPlanMaterialObserver.php`

- Event `created`
- Mendelegasikan ke `NotificationService::createForLessonPlanMaterial()`

Semua observer membungkus logika dalam `try-catch(\Throwable $e)` dan log error tanpa melempar ulang.

### 2. NotificationService — Method Baru

Interface publik lengkap (termasuk yang sudah ada):

| Method | Penerima | Trigger |
|--------|----------|---------|
| `createForLessonPlanPending(LessonPlan $lp)` | Semua `kepala_sekolah` aktif | RPP diajukan |
| `createForLessonPlanApproved(LessonPlan $lp)` | Guru pemilik RPP | RPP disetujui |
| `createForLessonPlanRevised(LessonPlan $lp)` | Guru pemilik RPP | RPP diminta revisi |
| `createForKbmPending(Kbm $kbm)` | Semua `kepala_sekolah` aktif | KBM diajukan |
| `createForKbmApproved(Kbm $kbm)` | Guru pemilik KBM | KBM disetujui |
| `createForKbmRevised(Kbm $kbm)` | Guru pemilik KBM | KBM diminta revisi |
| `createForRaporApproved(Rapor $rapor)` | Siswa pemilik rapor | Rapor disetujui |
| `createForAnnouncement(Announcement $ann)` | Semua user aktif sesuai `target_role` | Pengumuman dibuat |
| `createForLessonPlanMaterial(LessonPlanMaterial $m)` | Semua siswa aktif di kelas RPP | Materi baru diupload |

**Format konten notifikasi baru:**

| Event | Judul | Pesan |
|-------|-------|-------|
| Rapor APPROVED | `"Rapor [Nama Tahun Ajaran] Anda telah tersedia"` | `"Rapor Anda telah disetujui. Silakan akses untuk melihat hasil belajar Anda."` |
| Announcement created | `"Pengumuman: [Judul Pengumuman]"` | `"[100 karakter pertama konten]..."` |
| LessonPlanMaterial created | `"Materi baru: [Nama File] ([Nama Mapel])"` | `"Kelas [Nama Kelas] · Diunggah oleh [Nama Guru]"` |

**Relasi yang digunakan untuk method baru:**

```php
// Rapor → Student → User
$rapor->student->user

// Announcement → target_role (array) → User::whereIn('role', $targetRoles)
User::where('is_active', true)->whereIn('role', $announcement->target_role)->get()

// LessonPlanMaterial → LessonPlan → SchoolClass → Students → Users
$material->lessonPlan->schoolClass->students()->with('user')->get()
```

### 3. NotificationWidget — Tidak Berubah

Widget sudah bersifat generic (query by `auth()->id()`), tidak perlu diubah.

### 4. Registrasi Widget di Semua Panel

Tambahkan `renderHook()` di panel yang belum memilikinya:

- `StudentPanelProvider` — baru
- `AdminPanelProvider` — baru
- `GuruPanelProvider` — sudah ada
- `KepsekPanelProvider` — sudah ada

### 5. Registrasi Observer Baru di AppServiceProvider

```php
Rapor::observe(RaporObserver::class);
Announcement::observe(AnnouncementObserver::class);
LessonPlanMaterial::observe(LessonPlanMaterialObserver::class);
```

---

## Data Models

### Tabel `notifications` — Tidak Berubah

Schema sudah sesuai, tidak diperlukan migrasi baru.

### Relasi Baru yang Digunakan

```
Rapor ──belongs_to──> Student ──belongs_to──> User
Announcement ──(target_role array)──> User (whereIn role)
LessonPlanMaterial ──belongs_to──> LessonPlan ──belongs_to──> SchoolClass
SchoolClass ──has_many──> Student ──belongs_to──> User
```

---

## Correctness Properties

*(Properties 1–12 dari versi sebelumnya tetap berlaku. Berikut properties tambahan.)*

### Property 13: Notifikasi rapor ke siswa yang benar

*For any* `Rapor` yang statusnya berubah menjadi `APPROVED`, tepat satu `Notification` harus dibuat untuk user yang terhubung dengan `Student` pemilik rapor tersebut — bukan untuk siswa lain.

Validates: Requirements 11.1

### Property 14: Notifikasi pengumuman sesuai target_role

*For any* `Announcement` yang dibuat dengan `target_role = ['guru']` dan N guru aktif, tepat N `Notification` harus dibuat — satu per guru aktif. User dengan role lain tidak boleh menerima notifikasi.

Validates: Requirements 12.1, 12.5

### Property 15: Notifikasi materi ke siswa di kelas yang benar

*For any* `LessonPlanMaterial` yang dibuat untuk `LessonPlan` dengan `class_id` tertentu, notifikasi hanya boleh dikirim ke user yang terhubung dengan `Student` di kelas tersebut — bukan siswa di kelas lain.

Validates: Requirements 13.1

### Property 16: Konten notifikasi pengumuman mengandung judul dan ringkasan

*For any* `Announcement` dengan judul dan konten acak, `Notification` yang dibuat harus mengandung judul pengumuman di field `title` dan ringkasan konten (≤ 100 karakter) di field `message`.

Validates: Requirements 12.2, 12.3

---

## Error Handling

Tambahan skenario error untuk observer baru:

| Skenario | Penanganan |
|----------|------------|
| `Rapor` tidak memiliki relasi `student` atau `student->user` | Log error, return early |
| `Rapor` tidak memiliki relasi `academicYear` | Log error, return early |
| `LessonPlanMaterial` tidak memiliki relasi `lessonPlan` | Log error, return early |
| `LessonPlan` tidak memiliki relasi `schoolClass` | Log error, return early |
| `SchoolClass` tidak memiliki siswa aktif | Kondisi valid, tidak ada notifikasi, tidak ada error |
| `Announcement.target_role` kosong | Kondisi valid, tidak ada notifikasi, tidak ada error |
| Tidak ada user aktif yang cocok dengan `target_role` | Kondisi valid, tidak ada notifikasi, tidak ada error |

Pendekatan desain mengutamakan **decoupling** — logika pengiriman notifikasi sepenuhnya dipisahkan dari controller dan action Filament melalui Laravel Model Observer. UI diperbarui secara otomatis menggunakan mekanisme polling bawaan Livewire (`wire:poll`) setiap 15 detik, tanpa memerlukan WebSocket.

### Alur Notifikasi

```mermaid
sequenceDiagram
    participant Actor as Guru/Kepsek
    participant Model as LessonPlan / Kbm
    participant Observer as LessonPlanObserver / KbmObserver
    participant Service as NotificationService
    participant DB as notifications table
    participant Widget as NotificationWidget (Livewire)

    Actor->>Model: update status (PENDING/APPROVED/REVISED)
    Model->>Observer: updated() event (otomatis oleh Eloquent)
    Observer->>Observer: cek apakah kolom status berubah
    Observer->>Service: createForLessonPlan() / createForKbm()
    Service->>DB: INSERT INTO notifications (...)
    Note over Widget: wire:poll.15s berjalan di background
    Widget->>DB: SELECT notifications WHERE user_id = auth()->id()
    DB-->>Widget: data notifikasi terbaru
    Widget-->>Actor: badge & daftar notifikasi diperbarui
```

---

## Architecture

Sistem ini mengikuti arsitektur berlapis yang sudah ada di aplikasi:

```
┌─────────────────────────────────────────────────────────────┐
│  Presentation Layer (Filament / Livewire)                   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  NotificationWidget (Livewire Component)             │   │
│  │  - Ditampilkan via PanelsRenderHook::USER_MENU_BEFORE│   │
│  │  - wire:poll.15s untuk auto-refresh                  │   │
│  │  - Blade view dengan dropdown notifikasi             │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
         │ query / action
┌─────────────────────────────────────────────────────────────┐
│  Application Layer                                          │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  NotificationService                                 │   │
│  │  - createForLessonPlanPending()                      │   │
│  │  - createForLessonPlanApproved()                     │   │
│  │  - createForLessonPlanRevised()                      │   │
│  │  - createForKbmPending()                             │   │
│  │  - createForKbmApproved()                            │   │
│  │  - createForKbmRevised()                             │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
         │ dipanggil oleh
┌─────────────────────────────────────────────────────────────┐
│  Observer Layer                                             │
│  ┌─────────────────────┐  ┌─────────────────────────────┐  │
│  │  LessonPlanObserver │  │  KbmObserver                │  │
│  │  - updated()        │  │  - updated()                │  │
│  └─────────────────────┘  └─────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
         │ mendengarkan event Eloquent
┌─────────────────────────────────────────────────────────────┐
│  Domain Layer (Models)                                      │
│  LessonPlan  ·  Kbm  ·  User  ·  Teacher  ·  Schedule      │
└─────────────────────────────────────────────────────────────┘
         │ persists to
┌─────────────────────────────────────────────────────────────┐
│  Data Layer                                                 │
│  notifications table (sudah ada, schema sudah sesuai)       │
└─────────────────────────────────────────────────────────────┘
```

### Keputusan Arsitektur

**Observer vs Event/Listener**: Dipilih Observer karena lebih sederhana untuk use case ini — satu model, satu observer, tanpa perlu mendefinisikan Event class terpisah. Observer juga lebih mudah di-test karena bisa di-mock atau di-disable dengan `Model::withoutObservers()`.

**NotificationService sebagai intermediary**: Observer tidak langsung membuat Notification, melainkan mendelegasikan ke `NotificationService`. Ini memisahkan logika "kapan notifikasi dibuat" (Observer) dari logika "bagaimana notifikasi dibuat" (Service), sehingga Service bisa di-test secara independen.

**RenderHook untuk Widget**: Widget ditampilkan via `PanelsRenderHook::USER_MENU_BEFORE` di kedua panel provider. Ini menempatkan bell icon tepat sebelum user menu di topbar, konsisten dengan pola UI notifikasi yang umum.

---

## Components and Interfaces

### 1. `LessonPlanObserver`

**Lokasi**: `app/Observers/LessonPlanObserver.php`

```php
class LessonPlanObserver
{
    public function __construct(private NotificationService $notificationService) {}

    public function updated(LessonPlan $lessonPlan): void
    {
        // Hanya bereaksi jika kolom status berubah
        if (! $lessonPlan->wasChanged('status')) {
            return;
        }

        try {
            match ($lessonPlan->status) {
                'PENDING'  => $this->notificationService->createForLessonPlanPending($lessonPlan),
                'APPROVED' => $this->notificationService->createForLessonPlanApproved($lessonPlan),
                'REVISED'  => $this->notificationService->createForLessonPlanRevised($lessonPlan),
                default    => null,
            };
        } catch (Throwable $e) {
            Log::error('Gagal membuat notifikasi RPP', [
                'lesson_plan_id' => $lessonPlan->id,
                'status' => $lessonPlan->status,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

### 2. `KbmObserver`

**Lokasi**: `app/Observers/KbmObserver.php`

Struktur identik dengan `LessonPlanObserver`, mendelegasikan ke method `createForKbm*` di `NotificationService`.

### 3. `NotificationService`

**Lokasi**: `app/Services/NotificationService.php`

Interface publik:

| Method | Penerima | Trigger |
|--------|----------|---------|
| `createForLessonPlanPending(LessonPlan $lp)` | Semua user `kepala_sekolah` aktif | RPP diajukan |
| `createForLessonPlanApproved(LessonPlan $lp)` | User guru pemilik RPP | RPP disetujui |
| `createForLessonPlanRevised(LessonPlan $lp)` | User guru pemilik RPP | RPP diminta revisi |
| `createForKbmPending(Kbm $kbm)` | Semua user `kepala_sekolah` aktif | KBM diajukan |
| `createForKbmApproved(Kbm $kbm)` | User guru pemilik KBM | KBM disetujui |
| `createForKbmRevised(Kbm $kbm)` | User guru pemilik KBM | KBM diminta revisi |

**Format konten notifikasi**:

| Event | Judul | Pesan |
|-------|-------|-------|
| RPP PENDING | `"[Nama Guru] mengajukan RPP [Nama Mapel]"` | `"Kelas [Nama Kelas] · Tanggal implementasi: [dd M Y]"` |
| RPP APPROVED | `"RPP [Nama Mapel] Anda telah disetujui"` | `"Kepala sekolah telah menyetujui RPP Anda."` |
| RPP REVISED | `"RPP [Nama Mapel] Anda perlu direvisi"` | `"Catatan: [revision_note]"` |
| KBM PENDING | `"[Nama Guru] mengajukan laporan KBM [dd M Y]"` | `"[Nama Mapel] · Kelas [Nama Kelas]"` |
| KBM APPROVED | `"Laporan KBM [dd M Y] Anda telah disetujui"` | `"Kepala sekolah telah menyetujui laporan KBM Anda."` |
| KBM REVISED | `"Laporan KBM [dd M Y] Anda perlu direvisi"` | `"Catatan: [revision_note]"` |

### 4. `NotificationWidget` (Livewire Component)

**Lokasi**: `app/Livewire/NotificationWidget.php`  
**View**: `resources/views/livewire/notification-widget.blade.php`

**Public properties**:

- `$notifications` — koleksi 10 notifikasi terbaru milik user yang login
- `$unreadCount` — jumlah notifikasi belum dibaca

**Methods**:

- `render()` — memuat ulang `$notifications` dan `$unreadCount` dari database
- `markAsRead(string $notificationId)` — tandai satu notifikasi sebagai dibaca (hanya milik user sendiri)
- `markAllAsRead()` — tandai semua notifikasi milik user sebagai dibaca

**Polling**: Template menggunakan `wire:poll.15s` pada root element sehingga seluruh komponen di-refresh setiap 15 detik.

**Registrasi di panel**: Ditambahkan via `renderHook()` di `GuruPanelProvider` dan `KepsekPanelProvider`:

```php
->renderHook(
    PanelsRenderHook::USER_MENU_BEFORE,
    fn (): string => Blade::render('@livewire(\'notification-widget\')'),
)
```

### 5. Registrasi Observer

**Lokasi**: `app/Providers/AppServiceProvider.php` (method `boot()`)

```php
LessonPlan::observe(LessonPlanObserver::class);
Kbm::observe(KbmObserver::class);
```

---

## Data Models

### Tabel `notifications` (sudah ada)

Schema sudah sesuai dengan requirements. Tidak diperlukan migrasi baru.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | `char(26)` | ULID, primary key |
| `user_id` | `char(26)` | FK ke `users.id`, CASCADE DELETE |
| `title` | `varchar(255)` | Judul notifikasi |
| `message` | `text` | Isi pesan notifikasi |
| `is_read` | `tinyint(1)` | Default `0` (false) |
| `created_at` | `timestamp` | Default `CURRENT_TIMESTAMP` |

**Catatan**: Model `Notification` sudah ada di `app/Models/Notification.php` dengan `HasUlid`, relasi `user()`, dan cast yang benar. Model `User` sudah memiliki relasi `schoolNotifications()` ke model ini.

### Index yang direkomendasikan

Untuk performa query widget (filter `user_id` + order `created_at DESC`), tambahkan composite index:

```sql
-- Ditambahkan via migrasi baru
ALTER TABLE notifications ADD INDEX idx_user_created (user_id, created_at DESC);
```

### Relasi yang digunakan

```
Notification ──belongs_to──> User
User ──has_one──> Teacher
Teacher ──has_many──> LessonPlan
Teacher ──has_many──> Schedule
Schedule ──has_many──> Kbm
```

Untuk mendapatkan user guru dari `LessonPlan`:

```php
$lessonPlan->teacher->user  // LessonPlan → Teacher → User
```

Untuk mendapatkan user guru dari `Kbm`:

```php
$kbm->schedule->teacher->user  // Kbm → Schedule → Teacher → User
```

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Notifikasi ke kepsek saat submission

*For any* `LessonPlan` atau `Kbm` yang statusnya berubah menjadi `PENDING`, dan untuk setiap user dengan role `kepala_sekolah` yang aktif, tepat satu `Notification` harus dibuat untuk setiap kepsek aktif tersebut — sehingga jumlah notifikasi yang dibuat sama dengan jumlah kepsek aktif.

**Validates: Requirements 1.1, 3.1**

---

### Property 2: Konten notifikasi submission ke kepsek

*For any* `LessonPlan` dengan teacher dan subject acak yang statusnya berubah menjadi `PENDING`, setiap `Notification` yang dibuat untuk kepsek harus mengandung nama guru pengaju di judul dan nama kelas serta tanggal implementasi di pesan. Demikian pula untuk `Kbm` — judul harus mengandung nama guru dan tanggal KBM, pesan harus mengandung nama mata pelajaran dan kelas.

**Validates: Requirements 1.2, 1.3, 3.2, 3.3**

---

### Property 3: Notifikasi ke guru saat review

*For any* `LessonPlan` atau `Kbm` yang statusnya berubah menjadi `APPROVED` atau `REVISED`, tepat satu `Notification` harus dibuat untuk user yang terhubung dengan guru pemilik model tersebut.

**Validates: Requirements 2.1, 2.2, 4.1, 4.2**

---

### Property 4: Konten notifikasi hasil review mengandung revision_note

*For any* `LessonPlan` atau `Kbm` yang statusnya berubah menjadi `REVISED` dengan `revision_note` acak, pesan `Notification` yang dibuat harus mengandung teks `revision_note` tersebut secara verbatim.

**Validates: Requirements 2.4, 4.4**

---

### Property 5: Persistensi notifikasi dengan nilai awal yang benar

*For any* `Notification` yang dibuat oleh `NotificationService`, record yang tersimpan di database harus memiliki: `is_read = false`, `created_at` yang valid (tidak null), `user_id` yang merujuk ke user aktif, serta `title` dan `message` yang tidak kosong.

**Validates: Requirements 5.1, 5.2, 5.3, 5.4**

---

### Property 6: Isolasi notifikasi per user

*For any* dua user berbeda A dan B, query notifikasi yang dieksekusi dalam konteks user A tidak boleh mengembalikan notifikasi milik user B — bahkan jika user B memiliki lebih banyak notifikasi.

**Validates: Requirements 9.1, 9.2, 9.3**

---

### Property 7: Otorisasi mark-as-read

*For any* user A yang mencoba menandai notifikasi milik user B sebagai dibaca, operasi tersebut harus gagal (tidak mengubah `is_read` notifikasi user B) tanpa melempar exception yang tidak tertangani.

**Validates: Requirements 7.4**

---

### Property 8: Mark-all-as-read mengubah semua notifikasi user

*For any* user dengan N notifikasi belum dibaca (N ≥ 1), setelah `markAllAsRead()` dipanggil, semua N notifikasi tersebut harus memiliki `is_read = true`, dan `unreadCount` harus menjadi 0.

**Validates: Requirements 7.2**

---

### Property 9: Observer hanya bereaksi saat status berubah

*For any* update pada `LessonPlan` atau `Kbm` yang tidak mengubah kolom `status` (misalnya update `topic`, `process_note`, dll.), tidak boleh ada `Notification` baru yang dibuat.

**Validates: Requirements 10.3**

---

### Property 10: Kegagalan notifikasi tidak menggagalkan update model

*For any* update status pada `LessonPlan` atau `Kbm` di mana pembuatan `Notification` gagal karena exception, perubahan status pada model harus tetap tersimpan di database, dan error harus tercatat di application log.

**Validates: Requirements 10.4**

---

### Property 11: Badge menampilkan unread count yang akurat

*For any* user dengan N notifikasi belum dibaca, `NotificationWidget` harus mengembalikan `unreadCount` yang sama persis dengan N.

**Validates: Requirements 6.2**

---

### Property 12: Widget hanya menampilkan maksimal 10 notifikasi terbaru

*For any* user dengan lebih dari 10 notifikasi, `NotificationWidget` harus mengembalikan tepat 10 notifikasi, dan 10 notifikasi tersebut harus merupakan yang paling baru berdasarkan `created_at`.

**Validates: Requirements 6.4**

---

## Error Handling

### Strategi Umum

Semua error dalam Observer dibungkus dengan `try-catch` dan dicatat ke application log menggunakan `Log::error()`. Ini memastikan kegagalan pembuatan notifikasi tidak pernah menggagalkan operasi utama (perubahan status model).

### Skenario Error Spesifik

| Skenario | Penanganan |
|----------|------------|
| `LessonPlan` tidak memiliki relasi `teacher` | Log error dengan `lesson_plan_id`, return early tanpa membuat notifikasi |
| `Teacher` tidak memiliki relasi `user` | Log error, return early |
| `Kbm` tidak memiliki relasi `schedule` | Log error dengan `kbm_id`, return early |
| `Schedule` tidak memiliki relasi `teacher` | Log error, return early |
| Tidak ada kepsek aktif | Tidak ada error — ini kondisi valid, tidak ada notifikasi dibuat |
| Database error saat INSERT notifikasi | Ditangkap oleh try-catch di Observer, di-log, model update tetap berhasil |
| User mencoba mark-as-read notifikasi orang lain | Query menggunakan `where('user_id', auth()->id())` — operasi tidak berpengaruh, tidak ada exception |

### Format Log Error

```php
Log::error('Gagal membuat notifikasi', [
    'context' => 'LessonPlanObserver',
    'lesson_plan_id' => $lessonPlan->id,
    'status' => $lessonPlan->status,
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
]);
```

---

## Testing Strategy

### Pendekatan Dual Testing

Fitur ini menggunakan dua lapisan pengujian yang saling melengkapi:

1. **Unit/Feature tests** — untuk skenario spesifik, edge case, dan error handling
2. **Property-based tests** — untuk memverifikasi properti universal di berbagai input acak

Library PBT yang digunakan: **[eris/eris](https://github.com/giorgiosironi/eris)** (PHP property-based testing library).

Setiap property test dikonfigurasi untuk minimum **100 iterasi**.

### Unit & Feature Tests

**`NotificationServiceTest`** (Feature Test):

- Verifikasi setiap method service membuat notifikasi dengan konten yang benar
- Edge case: tidak ada kepsek aktif → tidak ada notifikasi, tidak ada exception
- Edge case: LessonPlan tanpa teacher → tidak ada notifikasi, error di-log
- Edge case: Kbm tanpa schedule/teacher → tidak ada notifikasi, error di-log

**`LessonPlanObserverTest`** (Feature Test):

- Observer dipanggil saat status berubah
- Observer tidak dipanggil saat kolom non-status berubah
- Exception dalam service tidak menggagalkan update model

**`KbmObserverTest`** (Feature Test):

- Struktur identik dengan `LessonPlanObserverTest`

**`NotificationWidgetTest`** (Feature Test menggunakan Pest Livewire):

- Widget ter-render di panel guru dan kepsek
- `markAsRead()` mengubah `is_read` menjadi true
- `markAllAsRead()` mengubah semua notifikasi user menjadi dibaca
- User tidak bisa mark-as-read notifikasi user lain
- Widget hanya menampilkan notifikasi milik user yang login

### Property-Based Tests

Setiap property test menggunakan tag komentar untuk traceability:

```php
// Feature: notification-system, Property 1: Notifikasi ke kepsek saat submission
```

**Property 1** — `NotificationCountMatchesActiveKepsekTest`:

- Generator: jumlah kepsek aktif (0–10), satu LessonPlan/Kbm dengan status PENDING
- Assertion: `count(notifications) === count(active_kepsek_users)`

**Property 2** — `NotificationContentContainsActorInfoTest`:

- Generator: nama guru acak, nama mapel acak, nama kelas acak, tanggal acak
- Assertion: judul mengandung nama guru + nama mapel; pesan mengandung nama kelas + tanggal

**Property 3** — `ReviewNotificationGoesToCorrectGuruTest`:

- Generator: LessonPlan/Kbm dengan teacher acak, status APPROVED atau REVISED
- Assertion: notifikasi dibuat untuk `teacher->user->id` yang benar

**Property 4** — `RevisionNoteAppearsInNotificationMessageTest`:

- Generator: string `revision_note` acak (termasuk karakter khusus, unicode)
- Assertion: `str_contains($notification->message, $revisionNote)`

**Property 5** — `NewNotificationHasCorrectInitialStateTest`:

- Generator: data notifikasi acak (user_id, title, message)
- Assertion: `is_read === false`, `created_at !== null`, `user_id` valid

**Property 6** — `NotificationIsolationPerUserTest`:

- Generator: dua user berbeda, N notifikasi untuk masing-masing
- Assertion: query dengan konteks user A tidak mengembalikan notifikasi user B

**Property 7** — `CannotMarkOtherUsersNotificationAsReadTest`:

- Generator: dua user berbeda, notifikasi milik user B
- Assertion: setelah user A memanggil markAsRead dengan ID notifikasi user B, `is_read` notifikasi user B tetap false

**Property 8** — `MarkAllAsReadClearsAllUnreadTest`:

- Generator: user dengan N notifikasi belum dibaca (N: 1–20)
- Assertion: setelah `markAllAsRead()`, semua `is_read === true` dan `unreadCount === 0`

**Property 9** — `NonStatusUpdateDoesNotCreateNotificationTest`:

- Generator: field non-status acak (topic, revision_note, file_path, dll.) dengan nilai acak
- Assertion: tidak ada notifikasi baru dibuat setelah update field non-status

**Property 10** — `NotificationFailureDoesNotRollbackModelUpdateTest`:

- Generator: LessonPlan/Kbm dengan status acak
- Setup: mock NotificationService untuk throw exception
- Assertion: model berhasil diupdate di database, error tercatat di log

**Property 11** — `UnreadCountMatchesActualUnreadNotificationsTest`:

- Generator: user dengan N notifikasi (campuran read/unread acak)
- Assertion: `widget->unreadCount === notifications->where('is_read', false)->count()`

**Property 12** — `WidgetShowsAtMostTenLatestNotificationsTest`:

- Generator: user dengan N notifikasi (N: 11–30), dengan `created_at` acak
- Assertion: widget mengembalikan tepat 10 notifikasi, dan kesemuanya adalah 10 dengan `created_at` terbesar
