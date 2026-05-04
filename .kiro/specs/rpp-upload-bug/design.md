# RPP Upload Bug — Bugfix Design

## Overview

Bug ini terdiri dari dua kesalahan logika yang saling berkaitan di fitur pengajuan RPP (Rencana Pelaksanaan Pembelajaran) pada panel Guru:

1. **`isContentLocked()` di `LessonPlanForm.php`** — mengunci semua field form untuk status `REVISED` dan `APPROVED`. Seharusnya hanya `APPROVED` yang dikunci; status `REVISED` justru mengharuskan guru mengupload ulang file RPP yang telah direvisi.

2. **`mutateFormDataBeforeSave()` di `EditLessonPlan.php`** — hanya mengizinkan penyimpanan untuk status `DRAFT` dan `PENDING`. Seharusnya hanya `DRAFT` dan `REVISED` yang boleh disimpan; status `PENDING` tidak boleh diubah guru karena sedang menunggu keputusan kepsek.

Strategi perbaikan bersifat minimal dan terfokus: hanya mengubah dua kondisi logika di dua file tersebut, tanpa mengubah alur bisnis lainnya.

---

## Glossary

- **Bug_Condition (C)**: Kondisi yang memicu bug — RPP berstatus `REVISED` tidak dapat diedit atau disimpan oleh guru
- **Property (P)**: Perilaku yang diharapkan — RPP berstatus `REVISED` harus dapat diedit dan disimpan
- **Preservation**: Perilaku yang tidak boleh berubah akibat perbaikan — edit/save RPP berstatus `DRAFT`, penolakan edit RPP berstatus `PENDING` dan `APPROVED`
- **`isContentLocked()`**: Method di `LessonPlanForm.php` yang menentukan apakah semua field form dinonaktifkan (disabled)
- **`mutateFormDataBeforeSave()`**: Method di `EditLessonPlan.php` yang memvalidasi apakah status RPP saat ini mengizinkan penyimpanan
- **`submitForApproval()`**: Method di `LessonPlan` model yang mengubah status dari `DRAFT`/`REVISED` → `PENDING`
- **Status RPP**: Nilai string enum: `DRAFT` → `PENDING` → `APPROVED` atau `REVISED` → `PENDING` (ulang)

---

## Bug Details

### Bug Condition

Bug terpicu ketika guru mencoba mengedit RPP yang berstatus `REVISED`. Fungsi `isContentLocked()` secara keliru memasukkan `REVISED` ke dalam daftar status yang dikunci, dan `mutateFormDataBeforeSave()` secara keliru tidak memasukkan `REVISED` ke dalam daftar status yang diizinkan untuk disimpan.

**Formal Specification:**

```
FUNCTION isBugCondition(X)
  INPUT: X of type LessonPlan
  OUTPUT: boolean

  RETURN X.status = 'REVISED'
END FUNCTION
```

### Contoh Konkret

| Skenario | Status | Perilaku Saat Ini (Buggy) | Perilaku yang Benar |
|---|---|---|---|
| Guru membuka form edit RPP yang diminta revisi | `REVISED` | Semua field disabled, tidak bisa upload file | Semua field aktif, bisa upload file baru |
| Guru menekan tombol Save pada RPP yang diminta revisi | `REVISED` | ValidationException: "RPP dengan status saat ini tidak dapat diubah" | Data tersimpan, status tetap `REVISED` (siap diajukan ulang) |
| Guru membuka form edit RPP yang masih pending | `PENDING` | Form aktif (tidak dikunci) — **ini benar** | Form aktif — tidak berubah |
| Guru menekan tombol Save pada RPP yang masih pending | `PENDING` | Data tersimpan — **ini salah, seharusnya ditolak** | ValidationException: RPP sedang pending, tidak bisa diubah |
| Guru membuka form edit RPP yang sudah disetujui | `APPROVED` | Semua field disabled — **ini benar** | Semua field disabled — tidak berubah |

---

## Expected Behavior

### Preservation Requirements

**Perilaku yang tidak boleh berubah:**
- RPP berstatus `DRAFT` harus tetap dapat diedit dan disimpan oleh guru
- RPP berstatus `APPROVED` harus tetap dikunci (semua field disabled) dan tidak dapat disimpan
- Alur `submitForApproval()` (DRAFT/REVISED → PENDING) tidak boleh terpengaruh
- Alur `approve()` dan `markAsRevised()` di sisi kepsek tidak boleh terpengaruh
- Penghapusan RPP berstatus `APPROVED` harus tetap dicegah

**Scope:**
Semua input yang BUKAN RPP berstatus `REVISED` harus menghasilkan perilaku yang identik sebelum dan sesudah perbaikan. Ini mencakup:
- Pembuatan RPP baru (Create)
- Edit RPP berstatus `DRAFT`
- Akses form RPP berstatus `APPROVED` (tetap terkunci)
- Semua aksi kepsek (approve, request revision)

---

## Hypothesized Root Cause

Berdasarkan analisis kode di `LessonPlanForm.php` dan `EditLessonPlan.php`:

1. **Kesalahan logika di `isContentLocked()`** — Array `['REVISED', 'APPROVED']` seharusnya hanya `['APPROVED']`. Developer kemungkinan salah mengasumsikan bahwa `REVISED` berarti "sudah final" padahal `REVISED` berarti "perlu diperbaiki guru".

2. **Kesalahan logika di `mutateFormDataBeforeSave()`** — Array `['DRAFT', 'PENDING']` seharusnya `['DRAFT', 'REVISED']`. Developer kemungkinan salah mengasumsikan bahwa `PENDING` masih bisa diedit, padahal `PENDING` berarti "sedang menunggu keputusan kepsek" dan tidak boleh diubah guru.

3. **Inkonsistensi dengan domain model** — `LessonPlan::submitForApproval()` sudah benar mengizinkan pengajuan dari `DRAFT` atau `REVISED`, namun lapisan form tidak konsisten dengan logika domain ini.

---

## Correctness Properties

Property 1: Bug Condition — RPP Berstatus REVISED Dapat Diedit dan Disimpan

_For any_ RPP di mana `isBugCondition(X)` bernilai true (status = `REVISED`), fungsi `isContentLocked()` yang telah diperbaiki SHALL mengembalikan `false` (form aktif), dan `mutateFormDataBeforeSave()` yang telah diperbaiki SHALL mengizinkan penyimpanan tanpa melempar exception.

**Validates: Requirements 2.1, 2.2**

Property 2: Preservation — Perilaku Status Lain Tidak Berubah

_For any_ RPP di mana `isBugCondition(X)` bernilai false (status bukan `REVISED`), fungsi `isContentLocked()` dan `mutateFormDataBeforeSave()` yang telah diperbaiki SHALL menghasilkan perilaku yang identik dengan fungsi aslinya — form `DRAFT` tetap aktif, form `APPROVED` tetap terkunci, save `PENDING` tetap ditolak.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6**

---

## Fix Implementation

### Perubahan yang Diperlukan

**File 1:** `app/Filament/Guru/Resources/LessonPlans/Schemas/LessonPlanForm.php`

**Method:** `isContentLocked()`

**Perubahan:**
```php
// SEBELUM (buggy):
return in_array($record->status, ['REVISED', 'APPROVED'], true);

// SESUDAH (fixed):
return in_array($record->status, ['APPROVED'], true);
// atau lebih eksplisit:
return $record->status === 'APPROVED';
```

**Alasan:** Status `REVISED` berarti guru harus memperbaiki RPP, sehingga form harus aktif. Hanya `APPROVED` yang benar-benar final dan tidak boleh diubah.

---

**File 2:** `app/Filament/Guru/Resources/LessonPlans/Pages/EditLessonPlan.php`

**Method:** `mutateFormDataBeforeSave()`

**Perubahan:**
```php
// SEBELUM (buggy):
if (! in_array($this->record->status, ['DRAFT', 'PENDING'], true)) {

// SESUDAH (fixed):
if (! in_array($this->record->status, ['DRAFT', 'REVISED'], true)) {
```

**Alasan:** RPP berstatus `PENDING` sedang menunggu keputusan kepsek dan tidak boleh diubah guru. RPP berstatus `REVISED` justru harus bisa disimpan karena guru sedang memperbaikinya.

---

## Testing Strategy

### Validation Approach

Strategi pengujian mengikuti dua fase: pertama, jalankan test pada kode yang **belum diperbaiki** untuk membuktikan bug ada (exploratory/bug condition checking), kemudian jalankan test pada kode yang **sudah diperbaiki** untuk memverifikasi fix benar dan tidak ada regresi (fix checking + preservation checking).

---

### Exploratory Bug Condition Checking

**Goal:** Membuktikan bug ada sebelum fix diterapkan. Konfirmasi atau bantah hipotesis root cause.

**Test Plan:** Tulis test yang mensimulasikan guru membuka dan menyimpan RPP berstatus `REVISED`. Jalankan pada kode yang belum diperbaiki untuk mengamati kegagalan.

**Test Cases:**

1. **Form Locked Test** — Render form edit RPP berstatus `REVISED`, assert bahwa field `file_path` dalam keadaan disabled (akan gagal pada kode buggy karena `isContentLocked` mengembalikan `true`)
2. **Save Blocked Test** — Panggil save pada RPP berstatus `REVISED`, assert bahwa tidak ada ValidationException (akan gagal pada kode buggy karena `mutateFormDataBeforeSave` melempar exception)
3. **PENDING Save Allowed Test** — Panggil save pada RPP berstatus `PENDING`, assert bahwa ValidationException dilempar (akan gagal pada kode buggy karena `PENDING` masih diizinkan)

**Expected Counterexamples:**
- Field form disabled padahal status `REVISED`
- ValidationException dilempar saat save RPP berstatus `REVISED`
- Tidak ada ValidationException saat save RPP berstatus `PENDING` (seharusnya ada)

---

### Fix Checking

**Goal:** Verifikasi bahwa untuk semua input di mana bug condition berlaku, fungsi yang telah diperbaiki menghasilkan perilaku yang benar.

**Pseudocode:**
```
FOR ALL X WHERE isBugCondition(X) DO
  // isContentLocked check
  result_lock := isContentLocked_fixed(X)
  ASSERT result_lock = false  // form harus aktif

  // mutateFormDataBeforeSave check
  result_save := mutateFormDataBeforeSave_fixed(X, validData)
  ASSERT result_save.exception = null  // tidak boleh ada exception
END FOR
```

---

### Preservation Checking

**Goal:** Verifikasi bahwa untuk semua input di mana bug condition TIDAK berlaku, fungsi yang telah diperbaiki menghasilkan hasil yang identik dengan fungsi aslinya.

**Pseudocode:**
```
FOR ALL X WHERE NOT isBugCondition(X) DO
  ASSERT isContentLocked_original(X) = isContentLocked_fixed(X)
  ASSERT mutateFormDataBeforeSave_original(X) = mutateFormDataBeforeSave_fixed(X)
END FOR
```

**Testing Approach:** Property-based testing direkomendasikan untuk preservation checking karena:
- Menghasilkan banyak test case secara otomatis di seluruh domain input
- Menangkap edge case yang mungkin terlewat oleh unit test manual
- Memberikan jaminan kuat bahwa perilaku tidak berubah untuk semua input non-buggy

**Test Cases:**

1. **DRAFT Form Active Preservation** — Verifikasi form RPP berstatus `DRAFT` tetap aktif (tidak dikunci) setelah fix
2. **APPROVED Form Locked Preservation** — Verifikasi form RPP berstatus `APPROVED` tetap terkunci setelah fix
3. **DRAFT Save Allowed Preservation** — Verifikasi save RPP berstatus `DRAFT` tetap berhasil setelah fix
4. **PENDING Save Rejected Preservation** — Verifikasi save RPP berstatus `PENDING` tetap ditolak setelah fix (ini adalah perilaku yang **diperbaiki**, bukan dipertahankan — lihat catatan di bawah)
5. **APPROVED Save Rejected Preservation** — Verifikasi save RPP berstatus `APPROVED` tetap ditolak setelah fix

> **Catatan:** Perilaku `PENDING` di `mutateFormDataBeforeSave` sebenarnya juga diperbaiki (dari "diizinkan" menjadi "ditolak"), sehingga test untuk `PENDING` masuk ke Fix Checking, bukan Preservation Checking murni.

---

### Unit Tests

- Test `isContentLocked()` untuk setiap status: `null` (create), `DRAFT`, `PENDING`, `REVISED`, `APPROVED`
- Test `mutateFormDataBeforeSave()` untuk setiap status: `DRAFT`, `PENDING`, `REVISED`, `APPROVED`
- Test bahwa form field `file_path` tidak disabled ketika status `REVISED`
- Test bahwa ValidationException dilempar dengan pesan yang benar untuk status yang tidak diizinkan

### Property-Based Tests

- Generate RPP dengan status acak dari `['DRAFT', 'PENDING', 'APPROVED']` dan verifikasi `isContentLocked()` menghasilkan nilai yang sama sebelum dan sesudah fix
- Generate RPP dengan status acak dari `['DRAFT', 'APPROVED']` dan verifikasi `mutateFormDataBeforeSave()` menghasilkan hasil yang sama sebelum dan sesudah fix

### Integration Tests

- Test alur lengkap: kepsek request revisi → guru membuka form edit → guru upload file baru → guru save → status tetap `REVISED` → guru submit → status menjadi `PENDING`
- Test bahwa RPP berstatus `APPROVED` tidak dapat disimpan melalui form edit
- Test bahwa RPP berstatus `DRAFT` tetap dapat diedit dan disimpan setelah fix diterapkan
