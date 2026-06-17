# Implementation Tasks

## Feature: temporary-access-enhancements

---

- [x] 1. Ganti browser alert dengan Filament confirmation modal
  - [x] 1.1 Tambah header Action `save` dengan `requiresConfirmation()` di `TemporaryAccessManagement.php`
    - Tambah `Action::make('save')` di `getHeaderActions()` dengan `requiresConfirmation()`, `modalHeading`, `modalDescription`, `modalSubmitActionLabel`, `disabled()`, dan `action(fn () => $this->submit())`
    - Hapus method `getHeaderActions()` yang lama (hanya berisi action `assign`) dan gabungkan action `assign` ke dalam array yang sama
    - File: `app/Filament/Pages/TemporaryAccessManagement.php`
  - [x] 1.2 Bersihkan blade view dari Alpine.js confirm
    - Hapus `x-data`, `x-on:click`, dan `window.confirm` dari tombol Simpan
    - Hapus tombol Simpan dari blade (sudah dipindah ke header action)
    - Ubah `wire:submit.prevent="submit"` menjadi `wire:submit.prevent` (atau hapus submit handler dari form tag)
    - File: `resources/views/filament/pages/temporary-access-management.blade.php`
  - [x] 1.3 Tulis test untuk konfirmasi modal dan submit
    - Test: klik action `save` → modal muncul → konfirmasi → `submit()` dieksekusi → notifikasi sukses → data tersimpan di database
    - Test: klik action `save` → modal muncul → batal → tidak ada perubahan data
    - File: `tests/Feature/Filament/Pages/TemporaryAccessManagementTest.php`

- [x] 2. Tambah bulk action cabut akses pada tabel akses aktif
  - [x] 2.1 Tambah `BulkAction` pada tabel di `ActiveTemporaryAccessList.php`
    - Tambah `->toolbarActions([BulkAction::make('revokeSelected')...])` dengan `requiresConfirmation()`, `modalDescription` dinamis (menyebut jumlah records), dan `deselectRecordsAfterCompletion()`
    - File: `app/Filament/Pages/ActiveTemporaryAccessList.php`
  - [x] 2.2 Implementasi method `revokeSelectedAbilities(Collection $abilities)`
    - Loop setiap record, panggil `$this->revokeAbility($record)` yang sudah ada
    - Tangkap exception per-record, hitung `$revokedCount` dan `$failedCount`
    - Kirim success notification dengan jumlah yang berhasil dicabut
    - Kirim warning notification jika ada yang gagal
    - File: `app/Filament/Pages/ActiveTemporaryAccessList.php`
  - [x] 2.3 Tulis test untuk bulk action
    - Test: pilih 3 records → konfirmasi bulk action → semua `UserPolicyAbility` terhapus dari database
    - Test: setelah bulk revoke semua abilities untuk satu user+policy → `TemporaryPolicyGrant` juga terhapus
    - Test: success notification muncul setelah bulk action selesai
    - File: `tests/Feature/Filament/Pages/ActiveTemporaryAccessListTest.php`

- [x] 3. Buat model, migration, dan integrasi log akses sementara
  - [x] 3.1 Buat migration untuk tabel `temporary_access_logs`
    - Kolom: `id` (ULID PK), `user_id` (FK users), `access_policy_id` (FK access_policies), `ability` (string), `level_id` (nullable FK levels), `granted_by_user_id` (nullable FK users), `granted_at` (timestamp, useCurrent), `expires_at` (timestamp), `revoked_at` (nullable timestamp), `revoked_by_user_id` (nullable FK users)
    - Tambah index pada `user_id`, `granted_at`, `revoked_at`
    - Jalankan: `php artisan make:migration create_temporary_access_logs_table --no-interaction`
  - [x] 3.2 Buat model `TemporaryAccessLog`
    - Jalankan: `php artisan make:model TemporaryAccessLog --no-interaction`
    - Tambah `HasUlid` trait, `$timestamps = false`, fillable, casts, relasi (`user`, `accessPolicy`, `level`, `grantedBy`, `revokedBy`)
    - Tambah scopes: `scopeActive()`, `scopeRevoked()`, `scopeExpired()`
    - File: `app/Models/TemporaryAccessLog.php`
  - [x] 3.3 Integrasikan pencatatan log ke `TemporaryAccessManager::assignAbility()`
    - Setelah `UserPolicyAbility::updateOrCreate(...)` berhasil, buat `TemporaryAccessLog::create([...])`
    - File: `app/Support/TemporaryAccessManager.php`
  - [x] 3.4 Integrasikan update log saat akses dicabut di `ActiveTemporaryAccessList::revokeAbility()`
    - Setelah `$ability->delete()`, update `TemporaryAccessLog` yang sesuai: set `revoked_at = now()` dan `revoked_by_user_id = auth()->id()`
    - Cocokkan berdasarkan `user_id`, `access_policy_id`, `ability`, dan `revoked_at IS NULL`
    - File: `app/Filament/Pages/ActiveTemporaryAccessList.php`
  - [x] 3.5 Tulis test untuk pencatatan dan update log
    - Test: submit form akses sementara → `TemporaryAccessLog` dibuat dengan data yang benar
    - Test: cabut akses → `revoked_at` dan `revoked_by_user_id` terisi pada log yang sesuai
    - File: `tests/Feature/Support/TemporaryAccessManagerTest.php`

- [x] 4. Buat halaman Filament "Log Akses"
  - [x] 4.1 Buat halaman `TemporaryAccessLogList`
    - Jalankan: `php artisan make:filament-page TemporaryAccessLogList --no-interaction`
    - Implementasi `HasTable`, `InteractsWithTable`, `canAccess()` (hanya `super_admin`)
    - Set navigation: group "Manajemen Akses", label "Log Akses", slug `log-akses`, sort 3, icon `Heroicon::OutlinedClipboardDocumentCheck`
    - File: `app/Filament/Pages/TemporaryAccessLogList.php`
  - [x] 4.2 Implementasi method `table()` pada `TemporaryAccessLogList`
    - Query: `TemporaryAccessLog::query()->with([...])` dengan `latest('granted_at')`
    - Kolom: User (searchable + email description), Policy (searchable, sortable), Ability (badge berwarna), Jenjang (badge, placeholder), Diberikan Pada (sortable), Diberikan Oleh (searchable), Berakhir (sortable), Dicabut Pada (sortable, placeholder '-'), Dicabut Oleh (placeholder '-')
    - Filter: User, Policy, Ability, Status (Aktif/Dicabut/Kedaluwarsa menggunakan scopes)
    - `defaultSort('granted_at', 'desc')`, `striped()`, tidak ada `recordActions()`
    - File: `app/Filament/Pages/TemporaryAccessLogList.php`
  - [x] 4.3 Buat blade view untuk halaman log
    - File: `resources/views/filament/pages/temporary-access-log-list.blade.php`
    - Konten: `<x-filament-panels::page>{{ $this->table }}</x-filament-panels::page>`
  - [x] 4.4 Tulis test untuk halaman Log Akses
    - Test: user non-admin tidak dapat mengakses halaman (assertForbidden)
    - Test: tabel menampilkan log yang ada
    - Test: search berdasarkan nama user menampilkan hasil yang benar
    - Test: filter status "Dicabut" hanya menampilkan log dengan `revoked_at != null`
    - Test: default sort adalah `granted_at` descending
    - File: `tests/Feature/Filament/Pages/TemporaryAccessLogListTest.php`

- [x] 5. Jalankan pint dan verifikasi semua test
  - [x] 5.1 Jalankan `vendor/bin/pint --dirty --format agent` untuk memformat semua file PHP yang dimodifikasi
  - [x] 5.2 Jalankan `php artisan test --compact` untuk memastikan semua test baru dan yang sudah ada lulus
