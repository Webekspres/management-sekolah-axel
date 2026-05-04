# Requirements Document

## Introduction

Fitur ini meningkatkan halaman manajemen akses sementara pada aplikasi admin Filament. Terdapat tiga peningkatan utama:

1. **Ganti browser alert** — Tombol "Simpan" pada halaman `TemporaryAccessManagement` saat ini menggunakan `window.confirm` (browser native alert) untuk konfirmasi. Ini harus diganti dengan Filament modal/dialog agar konsisten dengan desain sistem.
2. **Bulk action cabut akses** — Tabel "Daftar Akses Sementara Aktif" (`ActiveTemporaryAccessList`) hanya memiliki tombol cabut per baris. Perlu ditambahkan bulk action agar admin dapat mencabut banyak akses sekaligus.
3. **Halaman Log Akses** — Perlu halaman baru yang menampilkan riwayat historis setiap kali admin memberikan akses sementara ke pengguna, bersifat view-only, sortable, dan searchable.

## Glossary

- **System**: Aplikasi manajemen sekolah berbasis Filament/Laravel.
- **Admin**: Pengguna dengan role `super_admin` yang memiliki akses ke halaman manajemen akses sementara.
- **TemporaryAccessManagement**: Halaman Filament di `app/Filament/Pages/TemporaryAccessManagement.php` tempat admin memberikan akses sementara ke pengguna.
- **ActiveTemporaryAccessList**: Halaman Filament di `app/Filament/Pages/ActiveTemporaryAccessList.php` yang menampilkan daftar akses sementara yang sedang aktif.
- **UserPolicyAbility**: Model Eloquent yang merepresentasikan satu ability yang diberikan ke seorang user pada suatu policy, dengan kolom `user_id`, `access_policy_id`, `ability`, `is_inherited`, `granted_by_user_id`, `expires_at`, `level_id`.
- **TemporaryPolicyGrant**: Model Eloquent yang merepresentasikan grant-level akses sementara per user per policy (digunakan untuk pengecekan legacy).
- **AccessPolicy**: Model Eloquent yang mendefinisikan sebuah policy beserta abilities yang didukungnya.
- **TemporaryAccessLog**: Model Eloquent baru yang mencatat riwayat setiap pemberian akses sementara.
- **TemporaryAccessManager**: Service class di `app/Support/TemporaryAccessManager.php` yang mengelola logika pemberian dan pencabutan akses sementara.
- **Ability**: Izin granular dalam sebuah policy, seperti `viewAny`, `view`, `create`, `update`, `delete`.
- **Bulk Action**: Aksi yang dapat diterapkan ke beberapa baris tabel sekaligus setelah admin mencentang checkbox.

---

## Requirements

### Requirement 1: Ganti Browser Alert dengan Filament Modal

**User Story:** Sebagai Admin, saya ingin konfirmasi penyimpanan akses sementara menggunakan modal Filament, agar pengalaman pengguna konsisten dengan desain sistem dan tidak bergantung pada dialog bawaan browser.

#### Acceptance Criteria

1. WHEN Admin mengklik tombol "Simpan" pada halaman TemporaryAccessManagement, THE System SHALL menampilkan Filament confirmation modal (bukan `window.confirm` browser native) sebelum mengeksekusi aksi simpan.
2. THE Filament modal SHALL menampilkan judul konfirmasi dan deskripsi yang menjelaskan aksi yang akan dilakukan.
3. WHEN Admin mengklik tombol konfirmasi di dalam modal, THE System SHALL mengeksekusi method `submit()` untuk menyimpan pemberian akses sementara.
4. WHEN Admin mengklik tombol batal atau menutup modal, THE System SHALL membatalkan aksi simpan dan tidak melakukan perubahan data apapun.
5. THE System SHALL menghapus penggunaan `x-on:click="if (confirm(...)) { ... }"` dan Alpine.js `x-data` dari tombol Simpan pada view `temporary-access-management.blade.php`.

---

### Requirement 2: Bulk Action Cabut Akses pada Tabel Akses Aktif

**User Story:** Sebagai Admin, saya ingin dapat memilih beberapa baris sekaligus pada tabel "Daftar Akses Sementara Aktif" dan mencabut semua akses yang dipilih dalam satu aksi, agar saya tidak perlu mengklik tombol "Cabut" satu per satu.

#### Acceptance Criteria

1. THE ActiveTemporaryAccessList SHALL menampilkan checkbox pada setiap baris tabel sehingga Admin dapat memilih satu atau lebih baris.
2. WHEN Admin memilih satu atau lebih baris dan mengklik bulk action "Cabut Akses Terpilih", THE System SHALL menampilkan Filament confirmation modal yang menyebutkan jumlah akses yang akan dicabut.
3. WHEN Admin mengkonfirmasi bulk action, THE System SHALL mencabut semua `UserPolicyAbility` yang dipilih dengan logika yang sama seperti pencabutan per baris (termasuk penghapusan `TemporaryPolicyGrant` jika tidak ada ability aktif yang tersisa untuk user dan policy tersebut).
4. WHEN bulk action selesai dieksekusi, THE System SHALL menampilkan Filament success notification yang menyebutkan jumlah akses yang berhasil dicabut.
5. WHEN Admin mengklik batal pada modal konfirmasi bulk action, THE System SHALL membatalkan aksi dan tidak melakukan perubahan data apapun.
6. IF terjadi error saat mencabut salah satu akses dalam bulk action, THEN THE System SHALL tetap melanjutkan pencabutan akses lainnya dan menampilkan warning notification yang menginformasikan jumlah akses yang gagal dicabut.

---

### Requirement 3: Halaman Log Akses Sementara

**User Story:** Sebagai Admin, saya ingin melihat riwayat historis semua pemberian akses sementara yang pernah dilakukan, agar saya dapat mengaudit siapa yang memberikan akses apa kepada siapa dan kapan.

#### Acceptance Criteria

1. THE System SHALL menyediakan tabel `temporary_access_logs` di database dengan kolom: `id` (ULID primary key), `user_id` (penerima akses), `access_policy_id`, `ability`, `level_id` (nullable), `granted_by_user_id`, `granted_at`, `expires_at`, `revoked_at` (nullable, diisi saat akses dicabut), `revoked_by_user_id` (nullable).
2. WHEN Admin berhasil menyimpan pemberian akses sementara melalui halaman TemporaryAccessManagement, THE System SHALL membuat satu entri log di tabel `temporary_access_logs` untuk setiap `UserPolicyAbility` yang berhasil dibuat atau diperbarui.
3. WHEN Admin mencabut akses sementara (baik per baris maupun via bulk action), THE System SHALL memperbarui kolom `revoked_at` dan `revoked_by_user_id` pada entri log yang sesuai di tabel `temporary_access_logs`.
4. THE System SHALL menyediakan halaman Filament baru bernama "Log Akses" dalam navigation group "Manajemen Akses" yang hanya dapat diakses oleh Admin (role `super_admin`).
5. THE Log Akses page SHALL menampilkan tabel dengan kolom: User (nama + email sebagai deskripsi), Policy, Ability (badge berwarna), Jenjang, Diberikan Pada, Diberikan Oleh, Berakhir, Dicabut Pada (nullable), Dicabut Oleh (nullable).
6. THE Log Akses page SHALL mendukung pencarian (searchable) berdasarkan nama user penerima, nama policy, dan nama pemberi akses.
7. THE Log Akses page SHALL mendukung pengurutan (sortable) pada kolom: Diberikan Pada, Berakhir, dan Dicabut Pada.
8. THE Log Akses page SHALL mendukung filter berdasarkan: User penerima, Policy, Ability, dan status (Aktif / Dicabut / Kedaluwarsa).
9. THE Log Akses page SHALL bersifat view-only; tidak ada aksi edit atau hapus yang tersedia untuk Admin.
10. WHERE Admin mengakses halaman Log Akses, THE System SHALL menampilkan data log diurutkan secara default berdasarkan `granted_at` descending (terbaru di atas).
