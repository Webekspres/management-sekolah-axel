# Design Document — capaian-pembelajaran-form-update

## Overview

Fitur ini memperluas form input Capaian Pembelajaran di panel Guru dengan menambahkan kolom-kolom baru yang sesuai dengan struktur tabel rapor (e-rapor). Perubahan mencakup database migration, model, form schema, table schema, factory, dan integrasi template rapor PDF.

**Scope perubahan:**

- Migration baru untuk menambah 5 kolom ke tabel `learning_achievements`
- Update `LearningAchievement` model: `$fillable`, PHPDoc
- Update `LearningAchievementForm`: Radio untuk status materi, Section baru "Hasil Pembelajaran", field keterangan capaian, placeholder predikat otomatis
- Update `LearningAchievementsTable`: kolom baru dengan badge
- Update `LearningAchievementFactory`: definisi kolom baru
- Update `halaman3.blade.php`: baca kolom baru dari model

---

## Architecture

```
database/migrations/
  └── YYYY_MM_DD_HHMMSS_add_columns_to_learning_achievements_table.php

app/Models/
  └── LearningAchievement.php          ← tambah $fillable + PHPDoc

app/Filament/Guru/Resources/LearningAchievements/
  ├── Schemas/LearningAchievementForm.php   ← tambah fields + section baru
  └── Tables/LearningAchievementsTable.php  ← tambah kolom baru

database/factories/
  └── LearningAchievementFactory.php    ← tambah definisi kolom baru

resources/views/rapor/partials/
  └── halaman3.blade.php                ← baca kolom baru dari $la

tests/Feature/
  ├── LearningAchievementFormTest.php   ← test form create/edit
  └── LearningAchievementTableTest.php  ← test table display
```

---

## Components and Interfaces

### Migration

File baru `add_columns_to_learning_achievements_table.php` menambahkan 5 kolom setelah kolom `notes` yang sudah ada:

| Kolom | Tipe | Nullable | Nilai Valid |
|---|---|---|---|
| `material_coverage_status` | `enum` | ya | `Terpenuhi`, `Tidak Terpenuhi` |
| `daily_assessment_predicate` | `enum` | ya | `Kurang`, `Cukup`, `Baik`, `Sangat Baik` |
| `midterm_assessment_predicate` | `enum` | ya | `Kurang`, `Cukup`, `Baik`, `Sangat Baik` |
| `final_assessment_predicate` | `enum` | ya | `Kurang`, `Cukup`, `Baik`, `Sangat Baik` |
| `achievement_status` | `string` | ya | teks bebas |

Method `down()` menghapus semua 5 kolom tersebut.

### LearningAchievement Model

Tambahan ke `$fillable`:

```php
protected $fillable = [
    'student_id',
    'subject_id',
    'academic_year_id',
    'topic_coverage',
    'notes',
    'material_coverage_status',
    'daily_assessment_predicate',
    'midterm_assessment_predicate',
    'final_assessment_predicate',
    'achievement_status',
];
```

PHPDoc properties baru:

```php
/**
 * @property string|null $material_coverage_status  'Terpenuhi'|'Tidak Terpenuhi'
 * @property string|null $daily_assessment_predicate 'Kurang'|'Cukup'|'Baik'|'Sangat Baik'
 * @property string|null $midterm_assessment_predicate 'Kurang'|'Cukup'|'Baik'|'Sangat Baik'
 * @property string|null $final_assessment_predicate 'Kurang'|'Cukup'|'Baik'|'Sangat Baik'
 * @property string|null $achievement_status
 */
```

### LearningAchievementForm

Perubahan pada tiga area:

**1. Section "Referensi Nilai" — tambah 3 Placeholder predikat otomatis**

Setelah placeholder `ph_avg`, `ats_score`, `sas_score` yang sudah ada, tambahkan 3 placeholder baru di baris kedua:

- `suggested_ph_predicate` — label "Predikat PH", dihitung dari rata-rata PH
- `suggested_ats_predicate` — label "Predikat ATS", dihitung dari nilai ATS
- `suggested_sas_predicate` — label "Predikat SAS", dihitung dari nilai SAS

Logika perhitungan predikat menggunakan helper closure yang sama:

```php
$calculatePredicate = function (?float $score): string {
    if ($score === null) return '—';
    return match (true) {
        $score >= 86 => 'A (Sangat Baik)',
        $score >= 73 => 'B (Baik)',
        $score >= 60 => 'C (Cukup)',
        default      => 'D (Kurang)',
    };
};
```

**2. Section "Capaian Pembelajaran" — tambah Radio + TextInput**

Urutan field setelah perubahan:

1. `material_coverage_status` — `Radio` dengan opsi `['Terpenuhi' => 'Terpenuhi', 'Tidak Terpenuhi' => 'Tidak Terpenuhi']`, inline, label "Status Pemaparan Materi"
2. `topic_coverage` — `Textarea` yang sudah ada (tetap)
3. `achievement_status` — `TextInput`, label "Keterangan Capaian", placeholder "Contoh: Terlampaui, Berkembang, dll"
4. `notes` — `Textarea` yang sudah ada (tetap)

**3. Section baru "Hasil Pembelajaran"** — ditempatkan setelah Section "Referensi Nilai":

```
Section::make('Hasil Pembelajaran')
    ->columns(3)
    ->schema([
        Select::make('daily_assessment_predicate')     // label: Penilaian Harian
        Select::make('midterm_assessment_predicate')   // label: Asesmen Tengah Semester
        Select::make('final_assessment_predicate')     // label: Sumatif Akhir Semester
    ])
```

Opsi untuk semua Select: `['Kurang' => 'Kurang', 'Cukup' => 'Cukup', 'Baik' => 'Baik', 'Sangat Baik' => 'Sangat Baik']`

### LearningAchievementsTable

Kolom baru ditambahkan setelah kolom `academicYear.name`:

```
TextColumn::make('material_coverage_status')   // label: Status Materi, sortable
TextColumn::make('achievement_status')          // label: Keterangan Capaian, limit(40)
```

Kolom `topic_coverage` diubah labelnya menjadi "Pemaparan Materi (Detail)".

Kolom `notes` tetap dengan `->toggleable(isToggledHiddenByDefault: true)`.

Kolom predikat (hidden by default, dengan badge):

```
TextColumn::make('daily_assessment_predicate')      // label: PH, badge, toggleable hidden
TextColumn::make('midterm_assessment_predicate')    // label: ATS, badge, toggleable hidden
TextColumn::make('final_assessment_predicate')      // label: SAS, badge, toggleable hidden
```

Warna badge untuk predikat:

```php
->color(fn (?string $state): string => match ($state) {
    'Sangat Baik' => 'success',
    'Baik'        => 'info',
    'Cukup'       => 'warning',
    'Kurang'      => 'danger',
    default       => 'gray',
})
```

### LearningAchievementFactory

Tambahan definisi:

```php
'material_coverage_status' => fake()->optional()->randomElement(['Terpenuhi', 'Tidak Terpenuhi']),
'daily_assessment_predicate' => fake()->optional()->randomElement(['Kurang', 'Cukup', 'Baik', 'Sangat Baik']),
'midterm_assessment_predicate' => fake()->optional()->randomElement(['Kurang', 'Cukup', 'Baik', 'Sangat Baik']),
'final_assessment_predicate' => fake()->optional()->randomElement(['Kurang', 'Cukup', 'Baik', 'Sangat Baik']),
'achievement_status' => fake()->optional()->randomElement(['Terlampaui', 'Berkembang', 'Sesuai Target']),
```

### halaman3.blade.php

Tabel Capaian Pembelajaran diubah untuk membaca kolom baru:

- Kolom "Pemaparan Materi": tampilkan `$la->material_coverage_status ?? $la->topic_coverage ?? '—'`
- Kolom "PH (Rata-rata)": tampilkan `$la->daily_assessment_predicate ?? $phAvg` (predikat dari DB jika ada, fallback ke kalkulasi)
- Kolom "ATS": tampilkan `$la->midterm_assessment_predicate ?? $subjectGrades['grades']['ATS'] ?? '—'`
- Kolom "SAS": tampilkan `$la->final_assessment_predicate ?? $subjectGrades['grades']['SAS'] ?? '—'`
- Kolom "Keterangan": tampilkan `$la->achievement_status ?? $la->notes ?? '—'`

---

## Data Models

### Tabel `learning_achievements` (setelah migration)

```
id                          char(26) PK
student_id                  char(26) FK
subject_id                  char(26) FK
academic_year_id            char(26) FK
topic_coverage              text NULL
notes                       text NULL
material_coverage_status    enum('Terpenuhi','Tidak Terpenuhi') NULL   ← baru
daily_assessment_predicate  enum('Kurang','Cukup','Baik','Sangat Baik') NULL  ← baru
midterm_assessment_predicate enum('Kurang','Cukup','Baik','Sangat Baik') NULL ← baru
final_assessment_predicate  enum('Kurang','Cukup','Baik','Sangat Baik') NULL  ← baru
achievement_status          varchar(255) NULL                          ← baru
```

Unique constraint `uq_la` pada `(student_id, subject_id, academic_year_id)` tetap dipertahankan.

### Logika Predikat

Fungsi `calculatePredicate(float $score): string` digunakan di form (Placeholder) dan bisa diekstrak ke helper:

| Rentang | Predikat |
|---|---|
| ≥ 86 | A (Sangat Baik) |
| 73–85 | B (Baik) |
| 60–72 | C (Cukup) |
| < 60 | D (Kurang) |

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

Dari prework analysis, terdapat beberapa kriteria yang cocok untuk property-based testing:

**Requirement 7 (Logika Predikat)** adalah pure function dengan input numerik dan output predikat — sangat cocok untuk PBT.

**Requirement 17 (Validasi Enum)** adalah property yang harus berlaku untuk semua input — cocok untuk PBT.

**Requirement 15 (Backward Compatibility)** adalah property yang harus berlaku untuk semua data lama — cocok untuk PBT.

### Property Reflection

Setelah review:

- Property predikat (dari Req 7) dan property validasi enum (dari Req 17) adalah dua hal berbeda: satu tentang logika kalkulasi, satu tentang validasi input. Keduanya unik.
- Property backward compatibility (dari Req 15) bisa digabung dengan property model fillable karena keduanya menguji bahwa model menerima nilai null untuk kolom baru.

### Property 1: Logika Kalkulasi Predikat

*For any* nilai numerik yang valid (0–100), fungsi `calculatePredicate` SHALL mengembalikan predikat yang tepat sesuai rentang: A untuk ≥86, B untuk 73–85, C untuk 60–72, D untuk <60.

**Validates: Requirements 7.2, 7.3, 7.4**

### Property 2: Nilai Null Menghasilkan Placeholder

*For any* pemanggilan `calculatePredicate` dengan nilai `null`, fungsi SHALL mengembalikan `'—'` tanpa melempar exception.

**Validates: Requirements 7.5**

### Property 3: Backward Compatibility — Data Lama Tetap Valid

*For any* `LearningAchievement` yang dibuat tanpa mengisi kolom baru (semua null), model SHALL dapat disimpan dan diambil kembali dari database tanpa error, dengan semua kolom baru bernilai null.

**Validates: Requirements 15.1, 15.2**

---

## Error Handling

### Nilai Enum Tidak Valid

Form menggunakan `Select` dan `Radio` dengan opsi terbatas, sehingga nilai yang tidak valid tidak bisa dikirim melalui UI. Untuk proteksi di level server, model menggunakan `$fillable` yang hanya menerima field yang terdaftar. Jika nilai enum tidak valid dikirim langsung ke API, MySQL akan menolak INSERT/UPDATE dengan error.

### Kolom Null di Template Rapor

Template `halaman3.blade.php` menggunakan operator `??` untuk fallback:

- Jika `material_coverage_status` null → tampilkan `topic_coverage` (data lama)
- Jika predikat null → tampilkan nilai numerik dari `$gradesBySubject` (data lama)
- Jika `achievement_status` null → tampilkan `notes` (data lama)

Ini memastikan backward compatibility penuh dengan data yang sudah ada.

### Migration Rollback

Method `down()` menghapus semua 5 kolom baru menggunakan `$table->dropColumn([...])`. Kolom lama (`topic_coverage`, `notes`) tidak tersentuh.

---

## Testing Strategy

Fitur ini adalah kombinasi dari CRUD form, database migration, dan template rendering. PBT sesuai untuk logika kalkulasi predikat (pure function). Sebagian besar test menggunakan example-based testing dengan Livewire test helper.

### 1. Unit Test — Logika Predikat

Test pure function `calculatePredicate` dengan berbagai input:

```php
// tests/Unit/PredicateCalculationTest.php
it('calculates correct predicate for score ranges', function (float $score, string $expected) {
    expect(calculatePredicate($score))->toBe($expected);
})->with([
    [86.0, 'A (Sangat Baik)'],
    [100.0, 'A (Sangat Baik)'],
    [73.0, 'B (Baik)'],
    [85.0, 'B (Baik)'],
    [60.0, 'C (Cukup)'],
    [72.0, 'C (Cukup)'],
    [0.0, 'D (Kurang)'],
    [59.9, 'D (Kurang)'],
]);
```

### 2. Property Test — Logika Predikat (PBT)

Menggunakan Pest dengan `pest-plugin-faker` atau generator sederhana:

```php
// tests/Feature/LearningAchievementPredicatePropertyTest.php
it('always returns a valid predicate for any score 0-100', function () {
    foreach (range(0, 100) as $score) {
        $result = calculatePredicate((float) $score);
        expect($result)->toBeIn(['A (Sangat Baik)', 'B (Baik)', 'C (Cukup)', 'D (Kurang)']);
    }
});

it('returns dash for null score', function () {
    expect(calculatePredicate(null))->toBe('—');
});
```

### 3. Feature Test — Form Create/Edit

```php
// tests/Feature/LearningAchievementFormTest.php
it('can create learning achievement with all new fields', function () {
    // Livewire test mengisi semua field baru dan memverifikasi tersimpan ke DB
});

it('can create learning achievement without optional fields', function () {
    // Verifikasi form bisa disubmit tanpa field nullable
});

it('can edit existing learning achievement with legacy data', function () {
    // Verifikasi data lama (tanpa kolom baru) bisa diedit tanpa error
});
```

### 4. Feature Test — Table Display

```php
// tests/Feature/LearningAchievementTableTest.php
it('shows material_coverage_status column in table', function () {
    // Verifikasi kolom baru tampil di tabel
});

it('shows achievement_status column in table', function () {
    // Verifikasi kolom baru tampil di tabel
});
```

### 5. Smoke Test — Migration

Dijalankan otomatis oleh `RefreshDatabase` di setiap test. Tidak perlu test terpisah karena semua feature test sudah menggunakan database yang di-migrate.

### Konfigurasi PBT

- Logika predikat diuji dengan 101 iterasi (nilai 0–100)
- Tag format: `Feature: capaian-pembelajaran-form-update, Property 1: calculatePredicate returns correct predicate for any score`
- Library: Pest built-in datasets (tidak memerlukan library PBT eksternal karena input space terbatas dan bisa di-enumerate)
