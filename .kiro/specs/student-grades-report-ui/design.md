# Design Document: Student Grades Report UI

## Overview

Fitur ini mendesain ulang dua halaman pada Student Panel (`siswa_ortu`):

- **MyGradesPage** (`/student/nilai-saya`) — halaman nilai akademik siswa
- **MyRaporPage** (`/student/rapor-saya`) — halaman daftar rapor siswa

Kedua halaman saat ini menggunakan tabel HTML manual. Redesain mengganti implementasi tersebut dengan komponen Filament v5 yang native: `StatsOverviewWidget`, `Infolist`, `Table`, `Section`, dan `Grid`. Tidak ada data yang dihilangkan — hanya cara penyajiannya yang ditingkatkan.

Perubahan bersifat **UI-only**: tidak ada perubahan pada model, migrasi, atau logika bisnis inti. Logika pengambilan data di `mount()` dipertahankan dan diperluas seperlunya.

---

## Architecture

Kedua halaman adalah `Filament\Pages\Page` (Livewire component) yang merender Blade view. Pendekatan redesain:

```
MyGradesPage (Livewire Page)
├── getHeaderWidgets() → GradeStatsWidget (StatsOverviewWidget)
└── Blade view
    ├── Empty/no-profile state (Filament notification component)
    └── Per-subject grade cards
        └── Section (per mapel)
            └── Grid (3 kolom: PH group, Tugas group, ATS/SAS/RAPOR)
                └── InfoList entries per grade type

MyRaporPage (Livewire Page)
├── getHeaderWidgets() → RaporStatsWidget (StatsOverviewWidget)
└── Blade view
    ├── Empty/no-profile state
    └── Filament Table (via HasTable trait)
        ├── TextColumn: Tahun Akademik
        ├── TextColumn: Semester
        ├── TextColumn: Status (badge)
        ├── TextColumn: Tanggal Disetujui
        └── Action: Download (conditional)
```

### Pola Widget untuk Stats

Stats ditampilkan melalui dedicated `StatsOverviewWidget` yang di-register via `getHeaderWidgets()` pada masing-masing Page. Widget menerima data dari Page melalui Livewire public properties atau query langsung ke database.

### Pola Table pada Custom Page

`MyRaporPage` mengimplementasikan `HasTable` + `InteractsWithTable` dari Filament untuk mendapatkan Filament Table yang fully-featured (sorting, empty state, actions) tanpa perlu membuat Resource terpisah.

---

## Components and Interfaces

### 1. `GradeStatsWidget`

**File:** `app/Filament/Student/Widgets/GradeStatsWidget.php`

Extends `StatsOverviewWidget`. Menampilkan:

- **Jumlah Mata Pelajaran** — count distinct subjects dengan nilai
- **Rata-rata Nilai RAPOR** — mean dari semua score dengan `grade_type = RAPOR`
- **Di Bawah KKM** — count mata pelajaran dengan RAPOR < KKM (fallback 70.0 via `SubjectKkm::getKkm()`)

Widget ini hanya dapat dilihat oleh user dengan role `siswa_ortu` dan hanya ketika student profile ada.

```php
// Stat definitions (pseudocode)
Stat::make('Mata Pelajaran', $subjectCount)->color('info')
Stat::make('Rata-rata RAPOR', number_format($avgRapor, 2))->color('success')
Stat::make('Di Bawah KKM', $belowKkmCount)->color($belowKkmCount > 0 ? 'danger' : 'success')
```

### 2. `RaporStatsWidget`

**File:** `app/Filament/Student/Widgets/RaporStatsWidget.php`

Extends `StatsOverviewWidget`. Menampilkan:

- **Total Rapor** — total count
- **Siap Diunduh** — count status `APPROVED`
- **Belum Siap** — count status `DRAFT` atau `FINALIZED`

### 3. `MyGradesPage` (dimodifikasi)

**File:** `app/Filament/Student/Pages/MyGradesPage.php`

Perubahan:

- Tambah `getHeaderWidgets()` yang mengembalikan `[GradeStatsWidget::class]`
- Blade view diganti dengan layout berbasis `Section` + `Grid` + InfoList entries

Data yang sudah ada di `$gradesBySubject` dipertahankan. Struktur array diperluas untuk menyertakan `subject_id` dan `level_id` agar KKM dapat di-lookup di widget.

### 4. `MyRaporPage` (dimodifikasi)

**File:** `app/Filament/Student/Pages/MyRaporPage.php`

Perubahan:

- Implement `HasTable` + `InteractsWithTable`
- Tambah `table(Table $table): Table` method
- Tambah `getHeaderWidgets()` yang mengembalikan `[RaporStatsWidget::class]`
- Method `downloadRapor()` dipertahankan, dipanggil via Filament `Action`

### 5. Blade Views (dimodifikasi)

**`my-grades-page.blade.php`** — Mengganti tabel HTML manual dengan:

```blade
{{-- No profile state --}}
<x-filament::section> ... </x-filament::section>

{{-- Per-subject loop --}}
@foreach ($gradesBySubject as $item)
    <x-filament::section :heading="$item['subject_name']">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            {{-- PH Group --}}
            {{-- Tugas Group --}}
            {{-- ATS / SAS / RAPOR --}}
        </div>
    </x-filament::section>
@endforeach
```

**`my-rapor-page.blade.php`** — Mengganti tabel HTML manual dengan:

```blade
{{ $this->table }}
```

---

## Data Models

### Grade (existing, read-only)

```
grades
  id              char(26)
  student_id      char(26)
  subject_id      char(26)
  academic_year_id char(26)
  grade_type      varchar(255)  -- PH1|PH2|PH3|PH4|TUGAS1|TUGAS2|TUGAS3|TUGAS4|ATS|SAS|RAPOR
  score           decimal(5,2)
```

Konstanta yang tersedia di `Grade` model:

- `Grade::GRADE_TYPES` — semua 11 tipe
- `Grade::PH_TYPES` — ['PH1','PH2','PH3','PH4']
- `Grade::TUGAS_TYPES` — ['TUGAS1','TUGAS2','TUGAS3','TUGAS4']

### Rapor (existing, read-only)

```
rapors
  id              char(26)
  student_id      char(26)
  academic_year_id char(26)
  file_path       varchar(255) nullable
  status          enum('DRAFT','FINALIZED','APPROVED')
  approved_at     timestamp nullable
  generated_at    timestamp nullable
```

Methods yang tersedia: `isDraft()`, `isFinalized()`, `isApproved()`

### SubjectKkm (existing, read-only)

```
subject_kkms
  subject_id  char(26)
  level_id    char(26)
  kkm         decimal(5,2)
```

Static helper: `SubjectKkm::getKkm(subjectId, levelId)` — returns 70.0 jika tidak dikonfigurasi.

### Data Flow: GradeStatsWidget

```
auth()->user()->student
  → student->class->level_id  (untuk KKM lookup)
  → Grade::where(student_id, academic_year_id)
      ->where(grade_type, 'RAPOR')
      ->with('subject')
      ->get()
  → compute: count, avg, below_kkm
```

### Data Flow: RaporStatsWidget

```
auth()->user()->student
  → Rapor::where(student_id)->get()
  → compute: total, approved, not_ready
```

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Statistik nilai dihitung dengan benar dari koleksi grade

*For any* koleksi grade yang dikelompokkan per mata pelajaran, fungsi komputasi statistik SHALL menghasilkan: (a) jumlah mata pelajaran = jumlah distinct subject dalam koleksi, (b) rata-rata RAPOR = mean aritmetika dari semua score dengan grade_type RAPOR, (c) jumlah di bawah KKM = jumlah mata pelajaran di mana score RAPOR < nilai KKM yang berlaku.

**Validates: Requirements 1.1**

### Property 2: Nilai yang tidak tersedia selalu ditampilkan sebagai placeholder

*For any* mata pelajaran dengan data nilai parsial (sebagian grade_type tidak ada), untuk setiap grade_type yang tidak memiliki record di database, tampilan SHALL menampilkan "—" atau "Belum ada" — bukan nilai kosong, null, atau error.

**Validates: Requirements 2.3**

### Property 3: Semua 11 grade type selalu ditampilkan untuk setiap mata pelajaran

*For any* mata pelajaran yang memiliki minimal satu record grade, tampilan SHALL menampilkan semua 11 label grade type (PH1, PH2, PH3, PH4, TUGAS1, TUGAS2, TUGAS3, TUGAS4, ATS, SAS, RAPOR) — terlepas dari berapa banyak grade type yang benar-benar memiliki nilai.

**Validates: Requirements 2.4**

### Property 4: Nilai numerik selalu diformat dengan dua angka desimal

*For any* nilai numerik (score) yang ditampilkan, format output SHALL selalu memiliki tepat dua angka desimal (contoh: 85 → "85.00", 90.5 → "90.50", 100 → "100.00").

**Validates: Requirements 2.5**

### Property 5: Statistik rapor konsisten dengan data koleksi

*For any* koleksi rapor dengan berbagai kombinasi status, fungsi komputasi statistik SHALL menghasilkan: (a) total = jumlah semua rapor dalam koleksi, (b) siap unduh = jumlah rapor dengan status APPROVED, (c) belum siap = jumlah rapor dengan status DRAFT atau FINALIZED, dan (d) total = siap unduh + belum siap (invariant).

**Validates: Requirements 5.1**

### Property 6: Semua data yang ada sebelumnya tetap ditampilkan setelah redesain

*For any* data grade yang tersedia untuk seorang siswa, halaman Nilai Saya SHALL menampilkan semua nilai (score) untuk semua mata pelajaran dan semua grade type yang ada di database — tidak ada data yang hilang dibandingkan tampilan sebelumnya.

**Validates: Requirements 6.5**

---

## Error Handling

### Tidak Ada Profil Siswa

Kondisi: `auth()->user()->student === null`

Kedua halaman menangani ini di `mount()` dengan set `$hasStudentProfile = false`. Blade view menampilkan pesan informatif menggunakan `<x-filament::section>` dengan ikon `heroicon-o-information-circle`.

### Tidak Ada Tahun Akademik Aktif

Kondisi: `AcademicYear::where('is_active', true)->first()` returns null

`MyGradesPage::mount()` sudah menangani ini — `$gradesBySubject` tetap array kosong, halaman menampilkan empty state.

### Tidak Ada Data Nilai / Rapor

Kondisi: query returns empty collection

Kedua halaman menampilkan empty state dengan ikon dan pesan yang sesuai. Untuk `MyRaporPage` yang menggunakan Filament Table, empty state dikonfigurasi via `->emptyStateHeading()` dan `->emptyStateIcon()`.

### File Rapor Tidak Ditemukan

Kondisi: `$rapor->file_path` null atau file tidak ada di storage

Method `downloadRapor()` yang sudah ada menangani ini dengan `session()->flash('error', ...)` dan redirect back. Tombol download hanya ditampilkan ketika `isApproved() && file_path !== null`.

### KKM Tidak Dikonfigurasi

Kondisi: tidak ada record di `subject_kkms` untuk subject + level tertentu

`SubjectKkm::getKkm()` sudah mengembalikan fallback `70.0`. Widget menggunakan nilai ini secara transparan.

---

## Testing Strategy

### Pendekatan

Fitur ini adalah **UI redesign** — logika bisnis inti tidak berubah. Testing fokus pada:

1. **Unit tests** untuk fungsi komputasi statistik (pure functions)
2. **Feature tests** untuk rendering halaman (Livewire component tests)
3. **Property-based tests** untuk memverifikasi properti universal pada komputasi dan rendering

Library PBT yang digunakan: **[`pestphp/pest-plugin-faker`](https://github.com/pestphp/pest-plugin-faker)** untuk data generation, dikombinasikan dengan loop iterasi manual (100+ iterasi) karena Pest belum memiliki dedicated PBT library. Alternatif: gunakan `eris/eris` atau implementasi sederhana dengan `fake()` dalam loop.

### Unit Tests — Fungsi Komputasi Statistik

Ekstrak logika komputasi ke helper methods atau service class yang dapat diuji secara terisolasi:

```php
// tests/Unit/GradeStatsComputationTest.php
it('menghitung jumlah mata pelajaran dengan benar', function () { ... });
it('menghitung rata-rata RAPOR dengan benar', function () { ... });
it('menghitung jumlah di bawah KKM dengan benar', function () { ... });

// tests/Unit/RaporStatsComputationTest.php
it('menghitung total rapor dengan benar', function () { ... });
it('invariant: total = approved + not_ready', function () { ... });
```

### Feature Tests — Rendering Halaman

```php
// tests/Feature/MyGradesPageTest.php
it('menampilkan pesan ketika tidak ada profil siswa', function () { ... });
it('menampilkan empty state ketika tidak ada nilai', function () { ... });
it('menampilkan nama tahun akademik aktif', function () { ... });
it('menampilkan semua mata pelajaran dengan data nilai', function () { ... });

// tests/Feature/MyRaporPageTest.php
it('menampilkan pesan ketika tidak ada profil siswa', function () { ... });
it('menampilkan empty state ketika tidak ada rapor', function () { ... });
it('menampilkan tombol download untuk rapor APPROVED dengan file', function () { ... });
it('tidak menampilkan tombol download untuk rapor DRAFT', function () { ... });
```

### Property-Based Tests

Setiap property test dijalankan minimum **100 iterasi** dengan data yang di-generate secara acak.

```php
// tests/Feature/GradeStatsPropertyTest.php

/**
 * Feature: student-grades-report-ui, Property 1: Statistik nilai dihitung dengan benar
 */
it('statistik nilai dihitung dengan benar untuk semua kombinasi input', function () {
    repeat(100, function () {
        $subjectCount = fake()->numberBetween(1, 10);
        $grades = generateRandomGrades($subjectCount);
        $stats = computeGradeStats($grades);
        
        expect($stats['subject_count'])->toBe($subjectCount);
        expect($stats['avg_rapor'])->toBe(computeExpectedAvg($grades));
        expect($stats['below_kkm'])->toBe(computeExpectedBelowKkm($grades));
    });
});

/**
 * Feature: student-grades-report-ui, Property 5: Statistik rapor konsisten
 */
it('total rapor selalu sama dengan approved + not_ready', function () {
    repeat(100, function () {
        $rapors = generateRandomRapors();
        $stats = computeRaporStats($rapors);
        
        expect($stats['total'])->toBe($stats['approved'] + $stats['not_ready']);
    });
});
```

```php
// tests/Feature/GradeDisplayPropertyTest.php

/**
 * Feature: student-grades-report-ui, Property 2: Nilai tidak tersedia ditampilkan sebagai placeholder
 * Feature: student-grades-report-ui, Property 3: Semua 11 grade type selalu ditampilkan
 * Feature: student-grades-report-ui, Property 4: Nilai numerik diformat dengan 2 desimal
 */
it('grade display properties hold for random grade data', function () {
    repeat(100, function () {
        $student = Student::factory()->create();
        $grades = Grade::factory()
            ->count(fake()->numberBetween(1, 8))
            ->for($student)
            ->create();
        
        $page = livewire(MyGradesPage::class)
            ->assertOk();
        
        // Property 3: semua 11 grade type labels muncul
        foreach (Grade::GRADE_TYPES as $type) {
            $page->assertSee($type);
        }
        
        // Property 4: nilai numerik diformat dengan 2 desimal
        foreach ($grades as $grade) {
            $page->assertSee(number_format((float) $grade->score, 2));
        }
    });
});
```

### Tag Format

Setiap property test diberi komentar:

```
Feature: student-grades-report-ui, Property {N}: {property_text}
```
