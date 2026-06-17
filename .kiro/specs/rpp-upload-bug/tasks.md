# Implementation Plan: RPP Upload Bug Fix

## Overview

Bugfix ini mengatasi tiga kesalahan logika yang saling berkaitan di fitur pengajuan RPP:
1. **`isContentLocked()` di `LessonPlanForm.php`** — mengunci form untuk status `REVISED` (seharusnya hanya `APPROVED`)
2. **`mutateFormDataBeforeSave()` di `EditLessonPlan.php`** — mengizinkan save untuk status `PENDING` (seharusnya hanya `DRAFT` dan `REVISED`)
3. **`LessonPlanPolicy::update()`** — menolak update untuk status `REVISED` secara diam-diam (seharusnya mengizinkan `DRAFT` dan `REVISED`)
4. **Bug B: RPP baru (create dari 0) tidak tersimpan** — root cause dikonfirmasi via exploratory test

Implementasi mengikuti metodologi bug condition: tulis test eksplorasi untuk membuktikan bug ada (pada kode unfixed), tulis test preservasi untuk baseline behavior, lalu implementasi fix dan verifikasi semua test lulus.

---

## Tasks

- [-] 1. Write bug condition exploration test (BEFORE implementing fix)
  - **Property 1: Bug Condition** - REVISED RPP Cannot Be Edited or Saved
  - **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bug exists
  - **DO NOT attempt to fix the test or the code when it fails**
  - **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
  - **GOAL**: Surface counterexamples that demonstrate the bug exists
  - **Scoped PBT Approach**: Scope the property to concrete failing cases (REVISED status) to ensure reproducibility
  - Buat `tests/Feature/Filament/Guru/LessonPlanBugConditionTest.php`
  - Test 1.1: Form field `file_path` disabled ketika status `REVISED` (bug: seharusnya enabled)
  - Test 1.2: Save RPP berstatus `REVISED` melempar ValidationException (bug: seharusnya berhasil)
  - Test 1.3: Save RPP berstatus `PENDING` berhasil tanpa exception (bug: seharusnya ditolak)
  - Test 1.4: Create RPP baru tidak tersimpan ke database (bug: seharusnya tersimpan)
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Test FAILS (this is correct - it proves the bug exists)
  - Document counterexamples found to understand root cause
  - Mark task complete when test is written, run, and failure is documented
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.3_

- [ ] 2. Write preservation property tests (BEFORE implementing fix)
  - **Property 2: Preservation** - Non-Buggy Status Behavior Unchanged
  - **IMPORTANT**: Follow observation-first methodology
  - Observe behavior on UNFIXED code for non-buggy inputs (DRAFT, APPROVED)
  - Write property-based tests capturing observed behavior patterns from Preservation Requirements
  - Property-based testing generates many test cases for stronger guarantees
  - Buat `tests/Feature/Filament/Guru/LessonPlanPreservationTest.php`
  - Test 2.1: Form DRAFT tetap aktif (semua field enabled) setelah fix
  - Test 2.2: Form APPROVED tetap terkunci (semua field disabled) setelah fix
  - Test 2.3: Save RPP berstatus DRAFT tetap berhasil setelah fix
  - Test 2.4: Save RPP berstatus APPROVED tetap ditolak setelah fix
  - Test 2.5: Alur submitForApproval (DRAFT/REVISED → PENDING) tetap berjalan
  - Test 2.6: Alur approve dan markAsRevised di sisi kepsek tetap berjalan
  - Run tests on UNFIXED code
  - **EXPECTED OUTCOME**: Tests PASS (this confirms baseline behavior to preserve)
  - Mark task complete when tests are written, run, and passing on unfixed code
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [ ] 3. Fix for RPP Upload Bug

  - [ ] 3.1 Implement the fix in LessonPlanForm.php
    - Ubah `isContentLocked()` di `app/Filament/Guru/Resources/LessonPlans/Schemas/LessonPlanForm.php`
    - Ganti `return in_array($record->status, ['REVISED', 'APPROVED'], true);`
    - Menjadi `return $record->status === 'APPROVED';`
    - _Bug_Condition: isBugCondition(input) where input.status = 'REVISED'_
    - _Expected_Behavior: isContentLocked(REVISED) returns false (form aktif)_
    - _Preservation: isContentLocked(DRAFT) returns false, isContentLocked(APPROVED) returns true_
    - _Requirements: 2.1, 3.1, 3.2_

  - [ ] 3.2 Implement the fix in EditLessonPlan.php
    - Ubah `mutateFormDataBeforeSave()` di `app/Filament/Guru/Resources/LessonPlans/Pages/EditLessonPlan.php`
    - Ganti `if (! in_array($this->record->status, ['DRAFT', 'PENDING'], true))`
    - Menjadi `if (! in_array($this->record->status, ['DRAFT', 'REVISED'], true))`
    - _Bug_Condition: isBugCondition(input) where input.status = 'REVISED'_
    - _Expected_Behavior: mutateFormDataBeforeSave(REVISED) tidak melempar exception_
    - _Preservation: mutateFormDataBeforeSave(DRAFT) tidak melempar exception, mutateFormDataBeforeSave(PENDING) melempar exception_
    - _Requirements: 2.2, 2.4, 3.1, 3.2_

  - [ ] 3.3 Implement the fix in LessonPlanPolicy.php
    - Ubah `update()` di `app/Policies/LessonPlanPolicy.php`
    - Ganti `return in_array($lessonPlan->status, ['DRAFT', 'PENDING'], true);`
    - Menjadi `return in_array($lessonPlan->status, ['DRAFT', 'REVISED'], true);`
    - _Bug_Condition: isBugCondition(input) where input.status = 'REVISED'_
    - _Expected_Behavior: policy update(REVISED) returns true (guru boleh update)_
    - _Preservation: policy update(DRAFT) returns true, policy update(APPROVED) returns false_
    - _Requirements: 2.2, 3.1, 3.2_

  - [ ] 3.4 Investigate and fix Bug B (create dari 0 tidak tersimpan)
    - Jalankan exploratory test 1.4 untuk mengkonfirmasi root cause
    - Periksa apakah ada masalah di `CreateLessonPlan::mutateFormDataBeforeCreate()`
    - Periksa apakah ada masalah di validasi form atau file upload handling
    - Implementasi fix berdasarkan root cause yang ditemukan
    - _Requirements: 2.3_

  - [ ] 3.5 Verify bug condition exploration test now passes
    - **Property 1: Expected Behavior** - REVISED RPP Can Be Edited and Saved + Create works
    - **IMPORTANT**: Re-run the SAME test from task 1 - do NOT write a new test
    - Run bug condition exploration test from step 1
    - **EXPECTED OUTCOME**: Test PASSES (confirms bug is fixed)
    - _Requirements: 2.1, 2.2, 2.3_

  - [ ] 3.6 Verify preservation tests still pass
    - **Property 2: Preservation** - Non-Buggy Status Behavior Unchanged
    - **IMPORTANT**: Re-run the SAME tests from task 2 - do NOT write new tests
    - Run preservation property tests from step 2
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)
    - Confirm all tests still pass after fix (no regressions)

- [ ] 4. Integration tests untuk alur lengkap RPP (AFTER fix)
  - Buat `tests/Feature/Filament/Guru/LessonPlanIntegrationTest.php`
  - Test 4.1: Alur lengkap — Kepsek request revisi → Guru buka form edit → Guru upload file baru → Guru save → Status tetap REVISED → Guru submit → Status menjadi PENDING
  - Test 4.2: Guru tidak dapat save RPP berstatus PENDING (sudah diajukan, menunggu keputusan kepsek)
  - Test 4.3: Guru tidak dapat save RPP berstatus APPROVED (sudah final)
  - Test 4.4: Guru dapat membuat RPP baru (status DRAFT) dan menyimpannya
  - Test 4.5: Guru dapat mengedit dan menyimpan RPP berstatus DRAFT
  - _Requirements: 1.1–1.6, 2.1–2.5, 3.1–3.5_

- [ ] 5. Checkpoint - Ensure all tests pass
  - Jalankan `php artisan test --compact tests/Feature/Filament/Guru/LessonPlan*`
  - Jalankan `vendor/bin/pint --dirty --format agent` untuk memastikan code style konsisten
  - Semua tests harus lulus sebelum dianggap selesai
  - Tanyakan ke user jika ada pertanyaan

---

## Notes

- Task 1 (Bug Condition Exploration) HARUS dijalankan pada kode yang BELUM diperbaiki — test akan GAGAL (expected)
- Task 2 (Preservation) HARUS dijalankan pada kode yang BELUM diperbaiki — test akan LULUS (expected)
- Task 3 (Implementation) mengubah tiga kondisi logika di tiga file + investigasi bug create
- Task 3.5 dan 3.6 menjalankan ulang test yang sama dari task 1 dan 2 — kali ini semua harus LULUS
- Task 4 (Integration) memverifikasi alur lengkap setelah fix diterapkan
- Gunakan `php artisan make:test --pest {name}` untuk membuat test baru
- Jalankan `vendor/bin/pint --dirty --format agent` setelah setiap sesi modifikasi PHP
