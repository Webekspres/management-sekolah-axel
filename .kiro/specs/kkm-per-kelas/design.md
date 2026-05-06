# Design Document: KKM Per Kelas

## Overview

Fitur ini menambahkan kolom `kkm` (nullable `decimal(5,2)`) pada tabel `classes` sehingga setiap kelas dapat memiliki nilai KKM tersendiri. Perubahan menyentuh tiga area:

1. **Database & Model** — migrasi + update `SchoolClass` fillable/cast
2. **Admin Form** — tambah field KKM di `SchoolClassForm`
3. **PDF Rapor** — `RaporService::generatePdf()` menggunakan KKM kelas sebagai sumber utama, dengan fallback ke `SubjectKkm::getKkm()` → 70.0
4. **GradeStatsWidget** — widget nilai siswa menggunakan KKM kelas jika tersedia

Tidak ada perubahan pada model `SubjectKkm`, tabel `subject_kkms`, atau logika bisnis lainnya. Semua kelas yang belum dikonfigurasi KKM-nya tetap berfungsi normal melalui mekanisme fallback.

---

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│  Admin Panel (SchoolClassResource)                          │
│  SchoolClassForm → tambah TextInput KKM                     │
└──────────────────────────┬──────────────────────────────────┘
                           │ fillable: kkm
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  SchoolClass Model (tabel: classes)                         │
│  + kolom kkm decimal(5,2) nullable                          │
│  + fillable: 'kkm'                                          │
│  + cast: 'kkm' => 'decimal:2'                               │
└──────────────────────────┬──────────────────────────────────┘
                           │ student->schoolClass->kkm
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  KKM Resolution Logic (digunakan di dua tempat)             │
│  Priority: (1) schoolClass->kkm  →  (2) SubjectKkm::getKkm │
│            →  (3) 70.0 fallback                             │
└──────────┬──────────────────────────────┬───────────────────┘
           │                              │
           ▼                              ▼
┌──────────────────────┐    ┌─────────────────────────────────┐
│  RaporService        │    │  GradeStatsWidget               │
│  generatePdf()       │    │  (Student Panel)                │
│  → knowledgeSkill    │    │  → belowKkmCount                │
│    scores.kkm        │    │    menggunakan KKM kelas        │
└──────────────────────┘    └─────────────────────────────────┘
```

### Prinsip Desain

- **Backward compatible**: kolom nullable, semua kelas lama tetap berfungsi
- **Single source of truth**: logika resolusi KKM terpusat di satu helper method
- **Minimal footprint**: hanya 4 file yang dimodifikasi + 1 migrasi baru

---

## Components and Interfaces

### 1. Migrasi Database

**File:** `database/migrations/YYYY_MM_DD_HHMMSS_add_kkm_to_classes_table.php`

Menambahkan kolom `kkm decimal(5,2) nullable after academic_year_id` pada tabel `classes`. Kolom nullable memastikan semua baris yang sudah ada otomatis mendapat nilai `null` tanpa perlu data seeding.

### 2. `SchoolClass` Model (dimodifikasi)

**File:** `app/Models/SchoolClass.php`

Perubahan:

- Tambah `'kkm'` ke array `$fillable`
- Tambah cast `'kkm' => 'decimal:2'` via method `casts()`

### 3. `SchoolClassForm` (dimodifikasi)

**File:** `app/Filament/Clusters/Academic/Resources/SchoolClasses/Schemas/SchoolClassForm.php`

Tambah `TextInput::make('kkm')` di dalam Section "Informasi Kelas":

- Label: "KKM"
- Tipe: numeric, step 0.01
- Range: 0–100 (inklusif)
- Opsional (nullable)
- Placeholder: "Kosongkan jika menggunakan KKM default"

### 4. `RaporService` (dimodifikasi)

**File:** `app/Services/RaporService.php`

Perubahan pada `generatePdf()`:

- Setelah load `student.schoolClass`, ambil `$schoolClass->kkm`
- Pada bagian `$knowledgeSkillScores->map(...)`, ganti logika KKM:
  - Jika `$schoolClass->kkm !== null` → gunakan nilai tersebut untuk semua mata pelajaran
  - Jika null → fallback ke `SubjectKkm::getKkm($ks->subject_id, $levelId)`

Tambah private helper method `resolveKkm()` untuk memusatkan logika resolusi.

### 5. `GradeStatsWidget` (dimodifikasi)

**File:** `app/Filament/Student/Widgets/GradeStatsWidget.php`

Perubahan pada `getStats()`:

- Setelah mendapat `$student->schoolClass`, ambil `$schoolClass->kkm`
- Pada filter `belowKkmCount`, gunakan `$schoolClass->kkm` jika tidak null, fallback ke `SubjectKkm::getKkm()`

---

## Data Models

### SchoolClass (dimodifikasi)

```
classes
  id                char(26)       PK
  name              varchar(255)
  level_id          char(26)       FK → levels
  teacher_id        char(26)       FK → teachers
  academic_year_id  char(26)       FK → academic_years
  kkm               decimal(5,2)   nullable  ← BARU
```

Cast di model:

```php
protected function casts(): array
{
    return ['kkm' => 'decimal:2'];
}
```

### KKM Resolution Logic

Logika resolusi KKM yang digunakan di `RaporService` dan `GradeStatsWidget`:

```
resolveKkm(schoolClass, subjectId, levelId):
  if schoolClass.kkm !== null:
    return schoolClass.kkm          // Priority 1: KKM Kelas
  return SubjectKkm::getKkm(subjectId, levelId)  // Priority 2+3: fallback
```

`SubjectKkm::getKkm()` sudah menangani fallback ke 70.0 jika tidak ada record di `subject_kkms`.

### SubjectKkm (tidak berubah)

```
subject_kkms
  id          char(26)
  subject_id  char(26)
  level_id    char(26)
  kkm         decimal(5,2)
```

Static helper `SubjectKkm::getKkm(subjectId, levelId)` tetap digunakan sebagai fallback.

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: KKM valid diterima oleh form

*For any* nilai desimal dalam rentang [0.00, 100.00], form kelas SHALL menerimanya tanpa error validasi dan menyimpannya ke database dengan presisi dua angka desimal yang sama.

**Validates: Requirements 2.2, 2.5**

### Property 2: Nilai KKM di luar rentang ditolak oleh form

*For any* nilai numerik yang kurang dari 0 atau lebih dari 100, form kelas SHALL menampilkan error validasi dan menolak penyimpanan.

**Validates: Requirements 2.4**

### Property 3: Prioritas resolusi KKM selalu diikuti

*For any* kombinasi ketersediaan KKM kelas dan SubjectKkm, nilai KKM yang digunakan oleh `RaporService` SHALL mengikuti urutan prioritas: (1) `schoolClass->kkm` jika tidak null, (2) `SubjectKkm::getKkm()` jika ada, (3) 70.0 sebagai fallback akhir.

**Validates: Requirements 3.1, 3.3, 3.5, 4.2**

### Property 4: Penanda below-kkm konsisten dengan KKM yang berlaku

*For any* nilai pengetahuan atau keterampilan dan KKM yang berlaku, penanda `below-kkm` pada PDF rapor SHALL muncul jika dan hanya jika nilai tersebut lebih rendah dari KKM yang berlaku.

**Validates: Requirements 3.2, 3.4**

### Property 5: GradeStatsWidget menggunakan KKM kelas jika tersedia

*For any* siswa dengan kelas yang memiliki `kkm` tidak null, `GradeStatsWidget` SHALL menggunakan nilai `kkm` dari kelas tersebut (bukan dari `SubjectKkm`) untuk menghitung jumlah mata pelajaran di bawah KKM.

**Validates: Requirements 4.4**

---

## Error Handling

### KKM Kelas Null (Backward Compatibility)

Kondisi: `$schoolClass->kkm === null`

Ditangani di `RaporService::generatePdf()` dan `GradeStatsWidget::getStats()` dengan fallback ke `SubjectKkm::getKkm($subjectId, $levelId)`. Tidak ada exception yang dilempar — ini adalah kondisi normal untuk kelas yang belum dikonfigurasi.

### Kelas Siswa Null

Kondisi: `$student->schoolClass === null`

Sudah ditangani di kedua tempat:

- `RaporService`: `$schoolClass` bisa null, `$levelId = $schoolClass?->level_id` menggunakan null-safe operator
- `GradeStatsWidget`: `$levelId = $student->schoolClass?->level_id` sudah ada

Ketika `$schoolClass` null, `$levelId` juga null, dan `SubjectKkm::getKkm()` tidak bisa dipanggil dengan benar → fallback ke 70.0.

### Validasi Form

Nilai KKM di luar range 0–100 ditolak oleh validasi Filament sebelum mencapai database. Field bersifat opsional — nilai kosong disimpan sebagai `null`.

---

## Testing Strategy

### Pendekatan

Fitur ini menyentuh logika bisnis (resolusi KKM, validasi form) yang cocok untuk property-based testing. Library yang digunakan: **Pest** dengan loop iterasi manual menggunakan `fake()` untuk 100+ iterasi.

### Unit Tests — Logika Resolusi KKM

```php
// tests/Unit/KkmResolutionTest.php
it('menggunakan kkm kelas jika tidak null', function () { ... });
it('fallback ke SubjectKkm jika kkm kelas null', function () { ... });
it('fallback ke 70.0 jika tidak ada SubjectKkm', function () { ... });
```

### Feature Tests — Form Kelas

```php
// tests/Feature/SchoolClassFormKkmTest.php
it('menampilkan field KKM di form buat kelas', function () { ... });
it('menampilkan field KKM di form edit kelas', function () { ... });
it('menyimpan null ketika field KKM dikosongkan', function () { ... });
```

### Feature Tests — RaporService

```php
// tests/Feature/RaporServiceKkmTest.php
it('menggunakan kkm kelas pada generatePdf ketika tersedia', function () { ... });
it('fallback ke SubjectKkm ketika kkm kelas null', function () { ... });
it('fallback ke 70.0 ketika kkm kelas null dan tidak ada SubjectKkm', function () { ... });
```

### Property-Based Tests

Setiap property test dijalankan minimum **100 iterasi**.

```php
// tests/Feature/KkmPerKelasPropertyTest.php

/**
 * Feature: kkm-per-kelas, Property 1: KKM valid diterima oleh form
 */
it('form menerima semua nilai KKM dalam range 0-100', function () {
    repeat(100, function () {
        $validKkm = fake()->randomFloat(2, 0, 100);
        // livewire test: fillForm(['kkm' => $validKkm]) → assertHasNoFormErrors
    });
});

/**
 * Feature: kkm-per-kelas, Property 2: Nilai KKM di luar rentang ditolak
 */
it('form menolak nilai KKM di luar range 0-100', function () {
    repeat(100, function () {
        $invalidKkm = fake()->boolean()
            ? fake()->randomFloat(2, -100, -0.01)
            : fake()->randomFloat(2, 100.01, 200);
        // livewire test: fillForm(['kkm' => $invalidKkm]) → assertHasFormErrors(['kkm'])
    });
});

/**
 * Feature: kkm-per-kelas, Property 3: Prioritas resolusi KKM selalu diikuti
 */
it('resolusi KKM mengikuti urutan prioritas yang benar', function () {
    repeat(100, function () {
        $classKkm = fake()->boolean() ? fake()->randomFloat(2, 0, 100) : null;
        $subjectKkm = fake()->boolean() ? fake()->randomFloat(2, 0, 100) : null;
        // Verifikasi: jika classKkm ada → gunakan classKkm
        //             jika null dan subjectKkm ada → gunakan subjectKkm
        //             jika keduanya null → gunakan 70.0
    });
});

/**
 * Feature: kkm-per-kelas, Property 4: Penanda below-kkm konsisten
 */
it('penanda below-kkm muncul jika dan hanya jika nilai < KKM', function () {
    repeat(100, function () {
        $score = fake()->randomFloat(2, 0, 100);
        $kkm = fake()->randomFloat(2, 0, 100);
        $isBelowKkm = $score < $kkm;
        // Verifikasi: isBelowKkm === ($score < $kkm)
    });
});

/**
 * Feature: kkm-per-kelas, Property 5: GradeStatsWidget menggunakan KKM kelas
 */
it('GradeStatsWidget menggunakan kkm kelas jika tersedia', function () {
    repeat(100, function () {
        $classKkm = fake()->randomFloat(2, 0, 100);
        // Buat student dengan kelas yang memiliki kkm = $classKkm
        // Verifikasi widget menggunakan $classKkm, bukan SubjectKkm
    });
});
```

### Tag Format

```
Feature: kkm-per-kelas, Property {N}: {property_text}
```
