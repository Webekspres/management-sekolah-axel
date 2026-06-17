# Requirements Document

## Introduction

Fitur ini mengubah form input Capaian Pembelajaran pada panel Guru agar kolom-kolomnya sesuai dengan struktur tabel Capaian Pembelajaran yang ditampilkan pada rapor (e-rapor). Saat ini, form hanya memiliki field `topic_coverage` (Pemaparan Materi) dan `notes` (Keterangan). Berdasarkan struktur tabel rapor, form perlu ditambahkan field-field untuk Hasil Pembelajaran yang mencakup:

1. **Pemaparan Materi**: Terpenuhi / Tidak Terpenuhi
2. **Hasil Pembelajaran**:
   - Penilaian Harian (rata-rata PH1-PH4)
   - Asesmen Tengah Semester (ATS)
   - Sumatif Akhir Semester (SAS)
   - Keterangan: Terlampaui / Berkembang / dll

Nilai untuk Penilaian Harian, ATS, dan SAS akan ditampilkan sebagai predikat (Kurang / Cukup / Baik / Sangat Baik) berdasarkan skala:

- D (Kurang): < 59
- C (Cukup): 60-72
- B (Baik): 73-85
- A (Sangat Baik): 86-98

Scope perubahan:

- Menambahkan kolom baru ke tabel `learning_achievements`
- Mengubah form `LearningAchievementForm` untuk menampilkan field baru
- Mengubah tabel `LearningAchievementsTable` untuk menampilkan kolom baru
- Membuat migration untuk menambahkan kolom baru
- Membuat/update factory dan seeder jika diperlukan
- Membuat test untuk memverifikasi perubahan

---

## Glossary

- **LearningAchievement**: Model `App\Models\LearningAchievement` yang menyimpan data capaian pembelajaran siswa per mata pelajaran.
- **LearningAchievementForm**: Schema form Filament di `App\Filament\Guru\Resources\LearningAchievements\Schemas\LearningAchievementForm` yang digunakan untuk input data.
- **LearningAchievementsTable**: Konfigurasi tabel Filament di `App\Filament\Guru\Resources\LearningAchievements\Tables\LearningAchievementsTable` yang menampilkan daftar capaian pembelajaran.
- **Pemaparan_Materi**: Field yang menunjukkan apakah materi pembelajaran telah terpenuhi atau tidak (nilai: "Terpenuhi" atau "Tidak Terpenuhi").
- **Penilaian_Harian**: Rata-rata nilai dari PH1, PH2, PH3, dan PH4, ditampilkan sebagai predikat.
- **ATS**: Asesmen Tengah Semester, nilai ujian tengah semester.
- **SAS**: Sumatif Akhir Semester, nilai ujian akhir semester.
- **Predikat**: Huruf A/B/C/D yang merepresentasikan rentang nilai (A: 86-98, B: 73-85, C: 60-72, D: <59).
- **Keterangan_Capaian**: Field yang menunjukkan status capaian pembelajaran (nilai: "Terlampaui", "Berkembang", atau teks bebas lainnya).
- **Grade**: Model `App\Models\Grade` yang menyimpan nilai siswa per mata pelajaran dan tipe penilaian.
- **Grade_Types**: Tipe nilai yang valid: `PH1`, `PH2`, `PH3`, `PH4`, `ATS`, `SAS`, `RAPOR`, `TUGAS1`, `TUGAS2`, `TUGAS3`, `TUGAS4`.
- **PH_TYPES**: Konstanta di model Grade yang berisi array `['PH1', 'PH2', 'PH3', 'PH4']`.
- **Guru_Panel**: Panel Filament untuk guru di namespace `App\Filament\Guru`.
- **Migration**: File migration Laravel untuk mengubah struktur database.
- **Factory**: File factory Laravel untuk membuat data dummy untuk testing.

---

## Requirements

### Requirement 1: Menambahkan Kolom Database untuk Pemaparan Materi

**User Story:** Sebagai developer, saya ingin menambahkan kolom `material_coverage_status` ke tabel `learning_achievements`, sehingga guru dapat menyimpan status pemaparan materi (Terpenuhi/Tidak Terpenuhi).

#### Acceptance Criteria

1. THE Migration SHALL menambahkan kolom `material_coverage_status` dengan tipe `enum('Terpenuhi', 'Tidak Terpenuhi')` yang nullable ke tabel `learning_achievements`.
2. THE Migration SHALL ditempatkan di direktori `database/migrations/` dengan nama yang mengikuti konvensi Laravel timestamp.
3. THE Model `LearningAchievement` SHALL menambahkan `material_coverage_status` ke array `$fillable`.
4. WHEN migration dijalankan dengan `php artisan migrate`, THE Database SHALL memiliki kolom baru tanpa error.

---

### Requirement 2: Menambahkan Kolom Database untuk Hasil Pembelajaran

**User Story:** Sebagai developer, saya ingin menambahkan kolom untuk menyimpan predikat hasil pembelajaran (PH, ATS, SAS), sehingga data dapat ditampilkan di rapor sesuai format yang diinginkan.

#### Acceptance Criteria

1. THE Migration SHALL menambahkan kolom `daily_assessment_predicate` dengan tipe `enum('Kurang', 'Cukup', 'Baik', 'Sangat Baik')` yang nullable untuk menyimpan predikat Penilaian Harian.
2. THE Migration SHALL menambahkan kolom `midterm_assessment_predicate` dengan tipe `enum('Kurang', 'Cukup', 'Baik', 'Sangat Baik')` yang nullable untuk menyimpan predikat ATS.
3. THE Migration SHALL menambahkan kolom `final_assessment_predicate` dengan tipe `enum('Kurang', 'Cukup', 'Baik', 'Sangat Baik')` yang nullable untuk menyimpan predikat SAS.
4. THE Model `LearningAchievement` SHALL menambahkan ketiga kolom tersebut ke array `$fillable`.

---

### Requirement 3: Menambahkan Kolom Database untuk Keterangan Capaian

**User Story:** Sebagai developer, saya ingin menambahkan kolom `achievement_status` untuk menyimpan keterangan capaian pembelajaran, sehingga guru dapat mencatat status seperti "Terlampaui" atau "Berkembang".

#### Acceptance Criteria

1. THE Migration SHALL menambahkan kolom `achievement_status` dengan tipe `string` yang nullable ke tabel `learning_achievements`.
2. THE Model `LearningAchievement` SHALL menambahkan `achievement_status` ke array `$fillable`.
3. THE Kolom `notes` yang sudah ada SHALL tetap dipertahankan untuk catatan tambahan yang lebih detail.

---

### Requirement 4: Mengubah Field Pemaparan Materi di Form

**User Story:** Sebagai guru, saya ingin field Pemaparan Materi berubah dari textarea menjadi radio button dengan pilihan "Terpenuhi" atau "Tidak Terpenuhi", sehingga input lebih cepat dan konsisten.

#### Acceptance Criteria

1. THE `LearningAchievementForm` SHALL mengubah field `topic_coverage` dari `Textarea` menjadi `Textarea` yang tetap ada untuk deskripsi detail.
2. THE `LearningAchievementForm` SHALL menambahkan field `material_coverage_status` dengan komponen `Radio` yang memiliki opsi: "Terpenuhi" dan "Tidak Terpenuhi".
3. THE Field `material_coverage_status` SHALL ditempatkan di Section "Capaian Pembelajaran" sebelum field `topic_coverage`.
4. THE Field `material_coverage_status` SHALL memiliki label "Status Pemaparan Materi".
5. THE Field `material_coverage_status` SHALL bersifat nullable (tidak required).

---

### Requirement 5: Menambahkan Field Hasil Pembelajaran di Form

**User Story:** Sebagai guru, saya ingin melihat dan memilih predikat untuk Penilaian Harian, ATS, dan SAS di form, sehingga saya dapat mengisi data capaian pembelajaran sesuai dengan format rapor.

#### Acceptance Criteria

1. THE `LearningAchievementForm` SHALL menambahkan Section baru "Hasil Pembelajaran" setelah Section "Referensi Nilai".
2. THE Section "Hasil Pembelajaran" SHALL memiliki 3 kolom dengan `->columns(3)`.
3. THE Section SHALL menambahkan field `daily_assessment_predicate` dengan komponen `Select` yang memiliki opsi: "Kurang", "Cukup", "Baik", "Sangat Baik".
4. THE Section SHALL menambahkan field `midterm_assessment_predicate` dengan komponen `Select` yang memiliki opsi: "Kurang", "Cukup", "Baik", "Sangat Baik".
5. THE Section SHALL menambahkan field `final_assessment_predicate` dengan komponen `Select` yang memiliki opsi: "Kurang", "Cukup", "Baik", "Sangat Baik".
6. THE Field `daily_assessment_predicate` SHALL memiliki label "Penilaian Harian".
7. THE Field `midterm_assessment_predicate` SHALL memiliki label "Asesmen Tengah Semester".
8. THE Field `final_assessment_predicate` SHALL memiliki label "Sumatif Akhir Semester".
9. THE Semua field predikat SHALL bersifat nullable (tidak required).

---

### Requirement 6: Menambahkan Field Keterangan Capaian di Form

**User Story:** Sebagai guru, saya ingin menambahkan keterangan capaian pembelajaran seperti "Terlampaui" atau "Berkembang", sehingga status capaian siswa dapat dicatat dengan jelas.

#### Acceptance Criteria

1. THE `LearningAchievementForm` SHALL menambahkan field `achievement_status` dengan komponen `TextInput` di Section "Capaian Pembelajaran".
2. THE Field `achievement_status` SHALL memiliki label "Keterangan Capaian".
3. THE Field `achievement_status` SHALL ditempatkan setelah field `topic_coverage` dan sebelum field `notes`.
4. THE Field `achievement_status` SHALL bersifat nullable (tidak required).
5. THE Field `achievement_status` SHALL memiliki placeholder "Contoh: Terlampaui, Berkembang, dll".

---

### Requirement 7: Menambahkan Helper untuk Menghitung Predikat Otomatis

**User Story:** Sebagai guru, saya ingin sistem dapat menyarankan predikat berdasarkan nilai yang sudah ada, sehingga saya tidak perlu menghitung manual.

#### Acceptance Criteria

1. THE Section "Referensi Nilai" SHALL menambahkan Placeholder baru untuk menampilkan predikat yang disarankan berdasarkan nilai.
2. THE Placeholder `suggested_ph_predicate` SHALL menampilkan predikat yang dihitung dari rata-rata PH menggunakan logika: A (≥86), B (≥73), C (≥60), D (<60).
3. THE Placeholder `suggested_ats_predicate` SHALL menampilkan predikat yang dihitung dari nilai ATS menggunakan logika yang sama.
4. THE Placeholder `suggested_sas_predicate` SHALL menampilkan predikat yang dihitung dari nilai SAS menggunakan logika yang sama.
5. THE Placeholder SHALL menampilkan "—" jika nilai tidak tersedia.
6. THE Logika perhitungan predikat SHALL menggunakan helper method atau closure yang dapat digunakan kembali.

---

### Requirement 8: Mengubah Tabel untuk Menampilkan Kolom Baru

**User Story:** Sebagai guru, saya ingin melihat kolom Status Pemaparan Materi dan Keterangan Capaian di tabel daftar capaian pembelajaran, sehingga saya dapat melihat ringkasan data dengan cepat.

#### Acceptance Criteria

1. THE `LearningAchievementsTable` SHALL menambahkan kolom `material_coverage_status` dengan label "Status Materi" setelah kolom "Tahun Akademik".
2. THE `LearningAchievementsTable` SHALL menambahkan kolom `achievement_status` dengan label "Keterangan Capaian" setelah kolom "Status Materi".
3. THE Kolom `topic_coverage` SHALL tetap ditampilkan dengan label "Pemaparan Materi (Detail)".
4. THE Kolom `notes` SHALL tetap ditampilkan sebagai kolom yang dapat di-toggle (hidden by default).
5. THE Kolom baru SHALL dapat di-sort jika memungkinkan.

---

### Requirement 9: Menambahkan Kolom Predikat di Tabel (Optional)

**User Story:** Sebagai guru, saya ingin melihat predikat hasil pembelajaran di tabel daftar, sehingga saya dapat melihat ringkasan penilaian dengan cepat.

#### Acceptance Criteria

1. THE `LearningAchievementsTable` SHALL menambahkan kolom `daily_assessment_predicate` dengan label "PH" yang dapat di-toggle (hidden by default).
2. THE `LearningAchievementsTable` SHALL menambahkan kolom `midterm_assessment_predicate` dengan label "ATS" yang dapat di-toggle (hidden by default).
3. THE `LearningAchievementsTable` SHALL menambahkan kolom `final_assessment_predicate` dengan label "SAS" yang dapat di-toggle (hidden by default).
4. THE Kolom predikat SHALL menampilkan badge dengan warna berbeda untuk setiap predikat: A (hijau), B (biru), C (kuning), D (merah).

---

### Requirement 10: Memperbarui Factory untuk Data Testing

**User Story:** Sebagai developer, saya ingin factory `LearningAchievement` dapat menghasilkan data dengan kolom baru, sehingga testing dapat dilakukan dengan data yang lengkap.

#### Acceptance Criteria

1. THE Factory `LearningAchievementFactory` SHALL menambahkan definisi untuk kolom `material_coverage_status` dengan nilai random antara "Terpenuhi" dan "Tidak Terpenuhi".
2. THE Factory SHALL menambahkan definisi untuk kolom `daily_assessment_predicate`, `midterm_assessment_predicate`, dan `final_assessment_predicate` dengan nilai random dari ["Kurang", "Cukup", "Baik", "Sangat Baik"].
3. THE Factory SHALL menambahkan definisi untuk kolom `achievement_status` dengan nilai random seperti "Terlampaui", "Berkembang", "Sesuai Target", atau null.
4. IF factory belum ada, THEN THE Factory SHALL dibuat menggunakan `php artisan make:factory LearningAchievementFactory --model=LearningAchievement`.

---

### Requirement 11: Membuat Feature Test untuk Form Input

**User Story:** Sebagai developer, saya ingin memverifikasi bahwa form dapat menyimpan data dengan kolom baru, sehingga perubahan tidak merusak fungsionalitas yang ada.

#### Acceptance Criteria

1. THE Feature test SHALL dibuat di `tests/Feature/LearningAchievementFormTest.php`.
2. THE Test SHALL memverifikasi bahwa guru dapat membuat `LearningAchievement` baru dengan semua field baru terisi.
3. THE Test SHALL memverifikasi bahwa data tersimpan ke database dengan benar.
4. THE Test SHALL memverifikasi bahwa form dapat di-submit tanpa field opsional (nullable fields).
5. THE Test SHALL menggunakan factory untuk membuat data relasi (Student, Subject, AcademicYear).

---

### Requirement 12: Membuat Feature Test untuk Tabel Display

**User Story:** Sebagai developer, saya ingin memverifikasi bahwa tabel menampilkan kolom baru dengan benar, sehingga guru dapat melihat data yang telah diinput.

#### Acceptance Criteria

1. THE Feature test SHALL dibuat di `tests/Feature/LearningAchievementTableTest.php`.
2. THE Test SHALL memverifikasi bahwa kolom `material_coverage_status` ditampilkan di tabel.
3. THE Test SHALL memverifikasi bahwa kolom `achievement_status` ditampilkan di tabel.
4. THE Test SHALL memverifikasi bahwa guru hanya dapat melihat data untuk mata pelajaran yang mereka ajar.

---

### Requirement 13: Memperbarui Seeder (Optional)

**User Story:** Sebagai developer, saya ingin seeder dapat menghasilkan data capaian pembelajaran dengan kolom baru, sehingga development dan demo dapat menggunakan data yang realistis.

#### Acceptance Criteria

1. IF seeder `LearningAchievementSeeder` ada, THEN THE Seeder SHALL diperbarui untuk menggunakan factory dengan kolom baru.
2. THE Seeder SHALL menghasilkan data untuk berbagai kombinasi status pemaparan materi dan predikat.
3. THE Seeder SHALL menghasilkan data yang konsisten dengan nilai yang ada di tabel `grades`.

---

### Requirement 14: Dokumentasi Perubahan Field

**User Story:** Sebagai developer, saya ingin mendokumentasikan perubahan field di model, sehingga developer lain memahami struktur data yang baru.

#### Acceptance Criteria

1. THE Model `LearningAchievement` SHALL menambahkan PHPDoc block yang menjelaskan field baru.
2. THE PHPDoc SHALL mencantumkan tipe data untuk setiap property: `@property string|null $material_coverage_status`, `@property string|null $daily_assessment_predicate`, dll.
3. THE PHPDoc SHALL mencantumkan nilai enum yang valid untuk field enum.

---

### Requirement 15: Backward Compatibility dengan Data Lama

**User Story:** Sebagai administrator, saya ingin data capaian pembelajaran yang sudah ada tetap dapat ditampilkan dan diedit, sehingga tidak ada data yang hilang setelah update.

#### Acceptance Criteria

1. THE Migration SHALL membuat semua kolom baru sebagai nullable, sehingga data lama yang tidak memiliki nilai untuk kolom baru tetap valid.
2. THE Form SHALL dapat menampilkan dan mengedit data lama yang tidak memiliki nilai untuk kolom baru tanpa error.
3. THE Tabel SHALL menampilkan "—" atau placeholder yang sesuai untuk kolom baru yang bernilai null pada data lama.

---

### Requirement 16: Integrasi dengan Rapor PDF

**User Story:** Sebagai pengguna, saya ingin data yang diinput di form capaian pembelajaran otomatis muncul di rapor PDF, sehingga tidak perlu input data dua kali.

#### Acceptance Criteria

1. THE Template rapor di `resources/views/rapor/partials/halaman3.blade.php` SHALL membaca kolom `material_coverage_status` untuk ditampilkan di kolom "Pemaparan Materi".
2. THE Template rapor SHALL membaca kolom `daily_assessment_predicate`, `midterm_assessment_predicate`, dan `final_assessment_predicate` untuk ditampilkan di kolom "Hasil Pembelajaran".
3. THE Template rapor SHALL membaca kolom `achievement_status` untuk ditampilkan di kolom "Keterangan".
4. IF kolom baru bernilai null, THEN THE Template SHALL menampilkan fallback yang sesuai (misalnya "—" atau nilai dari kolom lama).
5. THE Template SHALL tetap kompatibel dengan data lama yang tidak memiliki nilai untuk kolom baru.

---

### Requirement 17: Validasi Enum Values

**User Story:** Sebagai developer, saya ingin memastikan bahwa hanya nilai enum yang valid yang dapat disimpan, sehingga data tetap konsisten.

#### Acceptance Criteria

1. THE Form SHALL menggunakan komponen `Select` atau `Radio` dengan opsi yang terbatas untuk field enum.
2. THE Model SHALL menggunakan Laravel Enum atau validation rule untuk memvalidasi nilai enum.
3. IF nilai yang tidak valid dikirim ke server, THEN THE System SHALL mengembalikan validation error.

---

### Requirement 18: Migration Rollback

**User Story:** Sebagai developer, saya ingin dapat melakukan rollback migration jika terjadi masalah, sehingga database dapat dikembalikan ke state sebelumnya.

#### Acceptance Criteria

1. THE Migration SHALL memiliki method `down()` yang menghapus semua kolom yang ditambahkan di method `up()`.
2. WHEN `php artisan migrate:rollback` dijalankan, THE Database SHALL kembali ke state sebelum migration tanpa error.
3. THE Rollback SHALL tidak menghapus data di kolom lain yang sudah ada.

---

### Requirement 19: Performance Optimization untuk Query

**User Story:** Sebagai developer, saya ingin memastikan bahwa penambahan kolom tidak memperlambat query, sehingga performa aplikasi tetap optimal.

#### Acceptance Criteria

1. THE Query di `LearningAchievementsTable` SHALL tetap menggunakan eager loading untuk relasi `student.user`, `subject`, dan `academicYear`.
2. THE Query SHALL tidak menambahkan N+1 query problem.
3. THE Index yang sudah ada di tabel `learning_achievements` SHALL tetap dipertahankan.

---

### Requirement 20: UI/UX Consistency

**User Story:** Sebagai guru, saya ingin form capaian pembelajaran memiliki tampilan yang konsisten dengan form lain di panel guru, sehingga mudah digunakan.

#### Acceptance Criteria

1. THE Form SHALL menggunakan komponen Filament yang standar (Select, Radio, TextInput, Textarea).
2. THE Layout form SHALL menggunakan Section dan Grid yang konsisten dengan form lain di panel guru.
3. THE Label dan placeholder SHALL menggunakan bahasa Indonesia yang jelas dan konsisten.
4. THE Field yang nullable SHALL memiliki indikator visual yang jelas (tidak ada tanda asterisk merah).
