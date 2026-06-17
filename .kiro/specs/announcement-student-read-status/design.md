# Announcement Student Read Status — Bugfix Design

## Overview

Panel siswa (`app/Filament/Student/`) memiliki tiga bug terkait resource pengumuman:

1. **Aksi tidak sah** — `DeleteBulkAction`, `EditAction` (di tabel dan di header `ViewAnnouncement`), serta `CreateAction` (di header `ListAnnouncements`) muncul di panel siswa, padahal siswa hanya boleh membaca pengumuman.
2. **Status baca tidak diimplementasikan** — Tidak ada tabel `announcement_reads`, tidak ada relasi di model `Announcement`, dan `ViewAnnouncement` tidak mencatat status baca saat pengumuman dibuka.
3. **Tidak ada indikator visual** — Semua baris pengumuman tampil identik; siswa tidak bisa membedakan yang sudah dan belum dibaca.

Strategi perbaikan: hapus aksi yang tidak sah, buat tabel pivot `announcement_reads`, tambah relasi dan method `isRead()` di model `Announcement`, catat status baca di `ViewAnnouncement::mount()`, dan tambah kolom badge di tabel siswa.

---

## Glossary

- **Bug_Condition (C)**: Kondisi yang memicu bug — aksi hapus/edit/create muncul di panel siswa, atau membuka detail pengumuman tidak mencatat status baca.
- **Property (P)**: Perilaku yang diharapkan setelah fix — aksi tidak sah tidak muncul, status baca tercatat, dan indikator visual tampil.
- **Preservation**: Perilaku yang tidak boleh berubah — aksi admin/guru tetap berjalan, filter `target_role` tetap aktif, navigasi ke detail tetap berfungsi, status baca bersifat per-user.
- **AnnouncementsTable** (student): `app/Filament/Student/Resources/Announcements/Tables/AnnouncementsTable.php` — konfigurasi kolom, aksi baris, dan toolbar actions tabel pengumuman di panel siswa.
- **ViewAnnouncement** (student): `app/Filament/Student/Resources/Announcements/Pages/ViewAnnouncement.php` — halaman detail pengumuman di panel siswa; tempat pencatatan status baca akan ditambahkan.
- **ListAnnouncements** (student): `app/Filament/Student/Resources/Announcements/Pages/ListAnnouncements.php` — halaman daftar pengumuman di panel siswa.
- **AnnouncementRead**: Model pivot baru yang merepresentasikan satu catatan baca (satu siswa membaca satu pengumuman).
- **isRead()**: Method di model `Announcement` yang mengembalikan `true` jika user yang sedang login sudah membaca pengumuman tersebut.
- **announcement_reads**: Tabel pivot baru dengan kolom `id` (ULID), `announcement_id`, `user_id`, `read_at`.

---

## Bug Details

### Bug Condition

Bug terjadi dalam dua skenario berbeda di panel siswa:

- **Skenario A** — Siswa mengakses halaman daftar atau detail pengumuman dan melihat aksi yang tidak seharusnya ada (`DeleteBulkAction`, `EditAction`, `CreateAction`).
- **Skenario B** — Siswa membuka halaman detail pengumuman (`ViewAnnouncement`) tetapi sistem tidak mencatat status baca, sehingga `isRead()` selalu `false` dan tidak ada indikator visual.

**Formal Specification:**

```
FUNCTION isBugCondition(X)
  INPUT: X of type {action: string, panel: string, role: string}
  OUTPUT: boolean

  // Bug 1: Aksi tidak sah muncul di panel siswa
  IF X.panel = 'student'
     AND X.role = 'siswa_ortu'
     AND X.action IN ['delete', 'edit', 'create']
    RETURN true
  END IF

  // Bug 2 & 3: Membuka detail tidak mencatat status baca
  IF X.panel = 'student'
     AND X.role = 'siswa_ortu'
     AND X.action = 'view_announcement'
    RETURN true
  END IF

  RETURN false
END FUNCTION
```

### Examples

- Siswa membuka `/student/pengumuman` → melihat checkbox dan tombol "Hapus yang dipilih" (**bug**; seharusnya tidak ada)
- Siswa membuka `/student/pengumuman` → melihat tombol "Edit" di setiap baris (**bug**; seharusnya tidak ada)
- Siswa membuka `/student/pengumuman` → melihat tombol "Buat" di header (**bug**; seharusnya tidak ada)
- Siswa membuka `/student/pengumuman/{id}` → melihat tombol "Edit" di header (**bug**; seharusnya tidak ada)
- Siswa membuka `/student/pengumuman/{id}` → `AnnouncementRead` tidak dibuat, `isRead()` tetap `false` (**bug**; seharusnya `true`)
- Siswa melihat daftar pengumuman → semua baris tampil identik tanpa badge baca/belum (**bug**; seharusnya ada indikator visual)
- Admin membuka panel admin → semua aksi tetap tersedia (**bukan bug**; harus dipertahankan)

---

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- Admin dan guru tetap dapat membuat, mengedit, dan menghapus pengumuman di panel masing-masing.
- Filter `target_role` di `getEloquentQuery()` tetap berjalan — siswa hanya melihat pengumuman yang ditujukan untuk `siswa_ortu`.
- Klik baris pengumuman di panel siswa tetap membuka halaman detail (`ViewAnnouncement`).
- Status baca bersifat per-user — jika siswa A membaca pengumuman X, status baca siswa B untuk pengumuman X tidak berubah.
- Membuka pengumuman yang sudah pernah dibaca tidak menduplikasi catatan di `announcement_reads` (gunakan `firstOrCreate`).

**Scope:**
Semua input yang tidak memenuhi `isBugCondition` harus sepenuhnya tidak terpengaruh oleh fix ini, termasuk:
- Semua aksi di panel admin, guru, dan kepala sekolah.
- Navigasi dan filter di panel siswa selain aksi hapus/edit/create.
- Logika `effectiveRole()` dan `canAccessPanel()` di model `User`.

---

## Hypothesized Root Cause

1. **Copy-paste dari resource admin tanpa penyesuaian** — `AnnouncementsTable` dan halaman-halaman di panel siswa kemungkinan dibuat dengan menyalin struktur dari panel admin (`app/Filament/Resources/Announcements/`) tanpa menghapus aksi yang tidak relevan untuk siswa. Ini menjelaskan mengapa `DeleteBulkAction`, `EditAction`, dan `CreateAction` masih ada.

2. **Fitur read-status belum diimplementasikan sama sekali** — Tabel `announcement_reads` tidak ada di database (dikonfirmasi dari schema), model `Announcement` tidak memiliki relasi ke tabel tersebut, dan `ViewAnnouncement` tidak memiliki logika pencatatan. Ini bukan regresi, melainkan fitur yang belum pernah dibangun.

3. **Tidak ada kolom indikator di tabel siswa** — `AnnouncementsTable` di panel siswa hanya mendefinisikan kolom `title` dan `created_at`, tanpa kolom status baca. Ini konsekuensi langsung dari poin 2.

---

## Correctness Properties

Property 1: Bug Condition — Aksi Tidak Sah Tidak Muncul di Panel Siswa

_For any_ request di panel siswa dengan role `siswa_ortu` yang merender halaman daftar atau detail pengumuman, kode yang sudah diperbaiki SHALL tidak merender `DeleteBulkAction`, `EditAction` (baris maupun header), maupun `CreateAction`.

**Validates: Requirements 2.1, 2.2, 2.3**

Property 2: Bug Condition — Status Baca Tercatat Saat ViewAnnouncement Dibuka

_For any_ request di panel siswa dengan role `siswa_ortu` yang membuka halaman detail pengumuman (`ViewAnnouncement`), kode yang sudah diperbaiki SHALL membuat atau menemukan record di tabel `announcement_reads` untuk pasangan `(user_id, announcement_id)` tersebut, sehingga `isRead()` mengembalikan `true`.

**Validates: Requirements 2.4**

Property 3: Bug Condition — Indikator Visual Status Baca Tampil di Tabel

_For any_ pengumuman yang ditampilkan di tabel panel siswa, kode yang sudah diperbaiki SHALL menampilkan indikator visual (badge) yang membedakan status `isRead = true` dari `isRead = false`.

**Validates: Requirements 2.5**

Property 4: Preservation — Aksi Admin/Guru Tidak Berubah

_For any_ request di panel admin atau guru, kode yang sudah diperbaiki SHALL menghasilkan perilaku yang identik dengan kode sebelum fix — semua aksi create, edit, delete tetap tersedia.

**Validates: Requirements 3.1**

Property 5: Preservation — Status Baca Bersifat Per-User

_For any_ dua user berbeda yang membuka pengumuman yang sama, kode yang sudah diperbaiki SHALL melacak status baca secara independen — status baca user A tidak mempengaruhi status baca user B.

**Validates: Requirements 3.4, 3.5**

---

## Fix Implementation

### Changes Required

#### 1. Migration baru: `announcement_reads`

**File**: `database/migrations/{timestamp}_create_announcement_reads_table.php`

**Spesifikasi tabel:**
- `id` — ULID, primary key
- `announcement_id` — `char(26)`, foreign key ke `announcements.id`, `onDelete('cascade')`
- `user_id` — `char(26)`, foreign key ke `users.id`, `onDelete('cascade')`
- `read_at` — `timestamp`, nullable, default `CURRENT_TIMESTAMP`
- Unique constraint pada `(announcement_id, user_id)` untuk mencegah duplikasi

#### 2. Model baru: `AnnouncementRead`

**File**: `app/Models/AnnouncementRead.php`

**Spesifikasi:**
- Gunakan trait `HasUlid`
- `$keyType = 'string'`, `$incrementing = false`
- `$fillable = ['announcement_id', 'user_id', 'read_at']`
- Cast `read_at` ke `datetime`
- Relasi `announcement()` → `BelongsTo(Announcement::class)`
- Relasi `user()` → `BelongsTo(User::class)`

#### 3. Update model `Announcement`

**File**: `app/Models/Announcement.php`

**Perubahan:**
- Tambah relasi `reads()` → `HasMany(AnnouncementRead::class)`
- Tambah method `isRead(?int $userId = null): bool` yang mengecek apakah ada record di `announcement_reads` untuk `user_id` yang diberikan (default: `auth()->id()`)

#### 4. Update `AnnouncementsTable` (student)

**File**: `app/Filament/Student/Resources/Announcements/Tables/AnnouncementsTable.php`

**Perubahan:**
- Hapus `use Filament\Actions\DeleteBulkAction`
- Hapus `use Filament\Actions\EditAction`
- Hapus `EditAction::make()` dari `recordActions()`
- Hapus `BulkActionGroup::make([DeleteBulkAction::make()])` dari `toolbarActions()`
- Tambah kolom `TextColumn` atau `IconColumn` untuk status baca menggunakan `state()` dengan closure yang memanggil `$record->isRead()`; tampilkan badge "Sudah Dibaca" / "Belum Dibaca" dengan warna berbeda

#### 5. Update `ViewAnnouncement` (student)

**File**: `app/Filament/Student/Resources/Announcements/Pages/ViewAnnouncement.php`

**Perubahan:**
- Hapus `use Filament\Actions\EditAction`
- Hapus `EditAction::make()` dari `getHeaderActions()`
- Override method `mount(string|int $record): void` — panggil `parent::mount($record)`, lalu buat atau temukan record di `AnnouncementRead` menggunakan `firstOrCreate(['announcement_id' => $this->record->id, 'user_id' => auth()->id()])`

#### 6. Update `ListAnnouncements` (student)

**File**: `app/Filament/Student/Resources/Announcements/Pages/ListAnnouncements.php`

**Perubahan:**
- Hapus `use Filament\Actions\CreateAction`
- Kembalikan array kosong `[]` dari `getHeaderActions()`

---

## Testing Strategy

### Validation Approach

Strategi pengujian mengikuti dua fase: pertama, jalankan tes pada kode yang **belum diperbaiki** untuk mengonfirmasi bug (exploratory), lalu jalankan tes setelah fix untuk memverifikasi perbaikan dan memastikan tidak ada regresi.

### Exploratory Bug Condition Checking

**Goal**: Konfirmasi keberadaan bug sebelum implementasi fix. Jika tes ini lulus pada kode unfixed, berarti bug tidak ada atau sudah diperbaiki sebelumnya.

**Test Plan**: Gunakan `livewire()` helper dari Pest Livewire plugin untuk merender komponen Filament dan assert keberadaan/ketiadaan aksi.

**Test Cases:**

1. **DeleteBulkAction ada di tabel siswa** — render `ListAnnouncements` sebagai siswa, assert `DeleteBulkAction` ada (akan lulus pada unfixed code, harus gagal setelah fix)
2. **EditAction ada di baris tabel siswa** — render `ListAnnouncements` sebagai siswa, assert `EditAction` ada di record actions (akan lulus pada unfixed code, harus gagal setelah fix)
3. **EditAction ada di header ViewAnnouncement** — render `ViewAnnouncement` sebagai siswa, assert `EditAction` ada di header (akan lulus pada unfixed code, harus gagal setelah fix)
4. **CreateAction ada di header ListAnnouncements** — render `ListAnnouncements` sebagai siswa, assert `CreateAction` ada di header (akan lulus pada unfixed code, harus gagal setelah fix)
5. **Status baca tidak tercatat** — buka `ViewAnnouncement` sebagai siswa, assert tidak ada record di `announcement_reads` (akan lulus pada unfixed code, harus gagal setelah fix)

**Expected Counterexamples:**
- Aksi `DeleteBulkAction`, `EditAction`, `CreateAction` ter-render di panel siswa
- Tidak ada record di `announcement_reads` setelah membuka detail pengumuman

### Fix Checking

**Goal**: Verifikasi bahwa untuk semua input di mana `isBugCondition(X) = true`, kode yang sudah diperbaiki menghasilkan perilaku yang benar.

**Pseudocode:**

```
FOR ALL X WHERE isBugCondition(X) DO
  result := renderStudentPanel_fixed(X)

  IF X.action IN ['delete', 'edit', 'create'] THEN
    ASSERT NOT contains(result, DeleteBulkAction)
    ASSERT NOT contains(result, EditAction)
    ASSERT NOT contains(result, CreateAction)
  END IF

  IF X.action = 'view_announcement' THEN
    ASSERT AnnouncementRead.exists(user_id=X.user_id, announcement_id=X.announcement_id)
    ASSERT Announcement.isRead() = true
    ASSERT contains(result, readStatusBadge)
  END IF
END FOR
```

### Preservation Checking

**Goal**: Verifikasi bahwa untuk semua input di mana `isBugCondition(X) = false`, kode yang sudah diperbaiki menghasilkan hasil yang sama dengan kode asli.

**Pseudocode:**

```
FOR ALL X WHERE NOT isBugCondition(X) DO
  ASSERT renderAdminPanel_original(X) = renderAdminPanel_fixed(X)
  ASSERT renderGuruPanel_original(X) = renderGuruPanel_fixed(X)
  ASSERT studentFilter_original(X) = studentFilter_fixed(X)
  ASSERT readStatus_userA != readStatus_userB  // per-user independence
END FOR
```

**Testing Approach**: Property-based testing direkomendasikan untuk preservation checking karena:
- Menghasilkan banyak kombinasi user dan pengumuman secara otomatis
- Menangkap edge case yang mungkin terlewat oleh unit test manual
- Memberikan jaminan kuat bahwa perilaku tidak berubah untuk input non-buggy

**Test Cases:**

1. **Admin tetap bisa edit dan hapus** — render `ListAnnouncements` (admin panel) sebagai admin, assert `EditAction` dan `DeleteBulkAction` ada
2. **Filter target_role tetap berjalan** — buat pengumuman untuk `guru`, login sebagai siswa, assert pengumuman tidak muncul di tabel siswa
3. **Status baca per-user independen** — dua siswa berbeda membuka pengumuman yang sama, assert masing-masing memiliki record `AnnouncementRead` sendiri
4. **Membuka ulang tidak menduplikasi record** — siswa membuka pengumuman yang sama dua kali, assert hanya ada satu record di `announcement_reads`

### Unit Tests

- Test `Announcement::isRead()` mengembalikan `false` jika belum ada record di `announcement_reads`
- Test `Announcement::isRead()` mengembalikan `true` setelah record dibuat
- Test `Announcement::isRead()` menggunakan `auth()->id()` sebagai default jika tidak ada argumen
- Test `AnnouncementRead` tidak menduplikasi record untuk pasangan `(announcement_id, user_id)` yang sama (unique constraint)

### Property-Based Tests

- Generate random set of users dan announcements, verifikasi `isRead()` hanya `true` untuk pasangan yang memiliki record di `announcement_reads`
- Generate random sequence of `mount()` calls dari user yang sama untuk announcement yang sama, verifikasi jumlah record di `announcement_reads` selalu 1
- Generate random users, verifikasi status baca satu user tidak mempengaruhi status baca user lain untuk announcement yang sama

### Integration Tests

- Siswa login → buka daftar pengumuman → tidak ada aksi hapus/edit/create → klik baris → halaman detail terbuka → tidak ada tombol edit → kembali ke daftar → badge "Sudah Dibaca" muncul di baris yang baru dibuka
- Admin login → buka daftar pengumuman → semua aksi tersedia → tidak ada perubahan perilaku
- Dua siswa berbeda membuka pengumuman yang sama → masing-masing memiliki status baca independen
