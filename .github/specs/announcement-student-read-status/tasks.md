# Implementation Plan

- [x] 1. Write bug condition exploration test
  - **Property 1: Bug Condition** - Unauthorized Actions & Missing Read Status
  - **CRITICAL**: This test MUST FAIL on unfixed code — failure confirms the bug exists
  - **DO NOT attempt to fix the test or the code when it fails**
  - **NOTE**: This test encodes the expected behavior — it will validate the fix when it passes after implementation
  - **GOAL**: Surface counterexamples that demonstrate the bugs exist
  - **Scoped PBT Approach**: Scope the property to the concrete failing cases (siswa_ortu role, student panel)
  - Create test file: `tests/Feature/Filament/Student/AnnouncementBugConditionTest.php`
  - Test 1 — `DeleteBulkAction` ada di tabel siswa: render `ListAnnouncements` sebagai siswa, assert `DeleteBulkAction` **ada** (harus LULUS pada unfixed code)
  - Test 2 — `EditAction` ada di baris tabel siswa: render `ListAnnouncements` sebagai siswa, assert `EditAction` ada di record actions (harus LULUS pada unfixed code)
  - Test 3 — `CreateAction` ada di header `ListAnnouncements`: render `ListAnnouncements` sebagai siswa, assert `CreateAction` ada di header (harus LULUS pada unfixed code)
  - Test 4 — `EditAction` ada di header `ViewAnnouncement`: render `ViewAnnouncement` sebagai siswa, assert `EditAction` ada di header (harus LULUS pada unfixed code)
  - Test 5 — Status baca tidak tercatat: buka `ViewAnnouncement` sebagai siswa, assert tidak ada record di `announcement_reads` (harus LULUS pada unfixed code)
  - Run test on UNFIXED code — **EXPECTED OUTCOME**: All tests PASS (confirms bugs exist)
  - Document counterexamples found (e.g., "DeleteBulkAction ter-render di panel siswa", "tidak ada AnnouncementRead setelah mount()")
  - Mark task complete when tests are written, run, and all pass on unfixed code
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [x] 2. Write preservation property tests (BEFORE implementing fix)
  - **Property 2: Preservation** - Admin/Guru Actions & Per-User Read Status Independence
  - **IMPORTANT**: Follow observation-first methodology
  - Observe: admin membuka `ListAnnouncements` (admin panel) → `EditAction` dan `DeleteBulkAction` ada
  - Observe: siswa hanya melihat pengumuman dengan `target_role` yang sesuai
  - Observe: dua siswa berbeda membuka pengumuman yang sama → masing-masing memiliki `AnnouncementRead` sendiri
  - Observe: siswa membuka pengumuman yang sama dua kali → hanya ada satu record di `announcement_reads`
  - Create test file: `tests/Feature/Filament/Student/AnnouncementPreservationTest.php`
  - Test 1 — Admin tetap bisa edit dan hapus: render `ListAnnouncements` (admin panel) sebagai admin, assert `EditAction` dan `DeleteBulkAction` ada
  - Test 2 — Filter `target_role` tetap berjalan: buat pengumuman untuk `guru`, login sebagai siswa, assert pengumuman tidak muncul di tabel siswa
  - Test 3 — Navigasi ke detail tetap berfungsi: siswa klik baris pengumuman, assert halaman `ViewAnnouncement` terbuka dengan konten lengkap
  - Test 4 — Status baca per-user independen: dua siswa berbeda membuka pengumuman yang sama, assert masing-masing memiliki record `AnnouncementRead` sendiri (property-based: generate random pairs of users)
  - Test 5 — Membuka ulang tidak menduplikasi record: siswa membuka pengumuman yang sama dua kali, assert hanya ada satu record di `announcement_reads`
  - Run tests on UNFIXED code (Tests 1–3 harus PASS; Tests 4–5 dijalankan setelah migration dibuat di task 3.1)
  - **EXPECTED OUTCOME**: Tests PASS (confirms baseline behavior to preserve)
  - Mark task complete when tests are written, run, and passing on unfixed code
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [-] 3. Fix announcement student panel bugs

  - [x] 3.1 Buat migration `announcement_reads` dan model `AnnouncementRead`
    - Jalankan `php artisan make:migration create_announcement_reads_table --no-interaction`
    - Definisikan tabel: `id` (ULID, PK), `announcement_id` (char 26, FK → `announcements.id`, cascade delete), `user_id` (char 26, FK → `users.id`, cascade delete), `read_at` (timestamp, nullable, default `CURRENT_TIMESTAMP`)
    - Tambah unique constraint pada `(announcement_id, user_id)` untuk mencegah duplikasi
    - Jalankan `php artisan make:model AnnouncementRead --no-interaction`
    - Gunakan trait `HasUlid`, set `$keyType = 'string'`, `$incrementing = false`
    - `$fillable = ['announcement_id', 'user_id', 'read_at']`, cast `read_at` ke `datetime`
    - Tambah relasi `announcement()` → `BelongsTo(Announcement::class)` dan `user()` → `BelongsTo(User::class)`
    - Jalankan `php artisan migrate --no-interaction`
    - _Bug_Condition: isBugCondition(X) where X.action = 'view_announcement' AND X.panel = 'student'_
    - _Expected_Behavior: AnnouncementRead::firstOrCreate(['announcement_id' => ..., 'user_id' => ...]) dipanggil saat mount()_
    - _Preservation: unique constraint memastikan tidak ada duplikasi; cascade delete menjaga integritas referensial_
    - _Requirements: 2.4, 3.4, 3.5_

  - [x] 3.2 Update model `Announcement` — tambah relasi dan method `isRead()`
    - Tambah relasi `reads()` → `HasMany(AnnouncementRead::class)`
    - Tambah method `isRead(?int $userId = null): bool` yang mengecek keberadaan record di `announcement_reads` untuk `user_id` yang diberikan (default: `auth()->id()`)
    - _Bug_Condition: isBugCondition(X) where X.action = 'view_announcement' AND X.panel = 'student'_
    - _Expected_Behavior: $announcement->isRead() returns true setelah AnnouncementRead dibuat_
    - _Preservation: method isRead() menggunakan user_id spesifik — tidak mempengaruhi user lain_
    - _Requirements: 2.4, 3.4, 3.5_

  - [x] 3.3 Hapus aksi tidak sah dari `AnnouncementsTable` (student) dan tambah kolom status baca
    - Hapus `use Filament\Actions\DeleteBulkAction`, `use Filament\Actions\EditAction`
    - Hapus `EditAction::make()` dari `recordActions()`
    - Hapus `BulkActionGroup::make([DeleteBulkAction::make()])` dari `toolbarActions()`
    - Tambah `TextColumn` untuk status baca menggunakan `state()` dengan closure yang memanggil `$record->isRead()`; tampilkan badge "Sudah Dibaca" (warna success) / "Belum Dibaca" (warna warning)
    - _Bug_Condition: isBugCondition(X) where X.action IN ['delete', 'edit'] AND X.panel = 'student'_
    - _Expected_Behavior: NOT contains(result, DeleteBulkAction) AND NOT contains(result, EditAction)_
    - _Preservation: ViewAction tetap ada; filter target_role di getEloquentQuery() tidak diubah_
    - _Requirements: 2.1, 2.2, 2.5_

  - [x] 3.4 Hapus `EditAction` dari `ViewAnnouncement` (student) dan tambah pencatatan status baca
    - Hapus `use Filament\Actions\EditAction`
    - Kembalikan array kosong `[]` dari `getHeaderActions()`
    - Override method `mount(string|int $record): void` — panggil `parent::mount($record)`, lalu `AnnouncementRead::firstOrCreate(['announcement_id' => $this->record->id, 'user_id' => auth()->id()])`
    - _Bug_Condition: isBugCondition(X) where X.action = 'view_announcement' AND X.panel = 'student'_
    - _Expected_Behavior: EditAction tidak muncul di header; AnnouncementRead dibuat/ditemukan saat mount()_
    - _Preservation: Konten pengumuman tetap ditampilkan lengkap; firstOrCreate mencegah duplikasi_
    - _Requirements: 2.3, 2.4, 3.3, 3.4_

  - [x] 3.5 Hapus `CreateAction` dari `ListAnnouncements` (student)
    - Hapus `use Filament\Actions\CreateAction`
    - Kembalikan array kosong `[]` dari `getHeaderActions()`
    - _Bug_Condition: isBugCondition(X) where X.action = 'create' AND X.panel = 'student'_
    - _Expected_Behavior: NOT contains(result, CreateAction) di header ListAnnouncements_
    - _Preservation: Navigasi ke ViewAnnouncement saat klik baris tetap berfungsi_
    - _Requirements: 2.1_

  - [ ] 3.6 Verify bug condition exploration test now passes
    - **Property 1: Expected Behavior** - Unauthorized Actions Removed & Read Status Recorded
    - **IMPORTANT**: Re-run the SAME tests from task 1 — do NOT write new tests
    - The tests from task 1 encode the expected behavior (aksi tidak ada, status baca tercatat)
    - When these tests pass, it confirms the expected behavior is satisfied
    - Run bug condition exploration tests from step 1
    - **EXPECTED OUTCOME**: All tests FAIL (confirms bugs are fixed — tests now assert absence of actions and presence of read records)
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [ ] 3.7 Verify preservation tests still pass
    - **Property 2: Preservation** - Admin/Guru Actions & Per-User Independence Unchanged
    - **IMPORTANT**: Re-run the SAME tests from task 2 — do NOT write new tests
    - Run preservation property tests from step 2
    - **EXPECTED OUTCOME**: All tests PASS (confirms no regressions)
    - Confirm admin/guru aksi tetap ada, filter target_role tetap berjalan, status baca per-user tetap independen

- [ ] 4. Checkpoint — Ensure all tests pass
  - Jalankan seluruh test suite: `php artisan test --compact`
  - Pastikan semua test di `AnnouncementBugConditionTest` dan `AnnouncementPreservationTest` lulus
  - Pastikan tidak ada regresi di test lain yang sudah ada
  - Tanya user jika ada pertanyaan atau ambiguitas yang muncul selama implementasi
