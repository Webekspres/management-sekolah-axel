# Implementation Plan: Modul Penilaian (Assessment)

## Overview

Implementasi modul penilaian mengikuti pendekatan **TDD (Test-Driven Development)**: setiap task implementasi didahului oleh task menulis test (Red), kemudian implementasi minimal agar test lulus (Green), lalu refactor jika perlu. Urutan pengerjaan dimulai dari fondasi data (migrations, models), dilanjutkan ke service layer (kalkulasi), kemudian UI Filament per panel, dan diakhiri dengan PDF generation.

## Tasks

- [x] 1. Database Migrations dan Model Baru
  - Buat migration untuk tabel `attitude_scores`, `knowledge_skill_scores`, `learning_achievements`, `personality_scores`, `subject_kkms`
  - Buat migration untuk menambah kolom `status`, `approved_at`, `rejection_note` ke tabel `rapors`
  - Buat Eloquent models: `AttitudeScore`, `KnowledgeSkillScore`, `LearningAchievement`, `PersonalityScore`, `SubjectKkm`
  - Tambahkan konstanta `GRADE_TYPES`, `PH_TYPES`, `TUGAS_TYPES` ke model `Grade`
  - Tambahkan helper methods (`isDraft()`, `isFinalized()`, `isApproved()`) dan update `$fillable` di model `Rapor`
  - Tambahkan relasi baru ke model `Student` (`attitudeScores`, `knowledgeSkillScores`, `learningAchievements`, `personalityScore`)
  - Buat factories untuk semua model baru
  - _Requirements: 5.1, 6.1, 7.1, 8.1, 10.1, 11.1, 17.1–17.6_

- [x] 2. RaporService — Unit Tests dan Implementasi (TDD)
  - [x] 2.1 Tulis unit tests untuk `RaporService` (Red)
    - Buat `tests/Unit/Services/RaporServiceTest.php`
    - Test `calculateRaporScore()` dengan semua komponen lengkap
    - Test `calculateRaporScore()` dengan PH parsial (hanya PH1, PH2)
    - Test `calculateRaporScore()` dengan ATS/SAS kosong (dianggap 0)
    - Test `calculateRaporScore()` dengan tidak ada nilai sama sekali (semua 0)
    - Test `assignPredicate()` untuk setiap rentang (A/B/C/D)
    - Test `assignPredicate()` untuk nilai boundary: 86, 85.99, 73, 72.99, 60, 59.99
    - _Requirements: 4.1, 4.2, 4.3, 6.2, 19.6, 19.7_

  - [x] 2.2 Tulis property test untuk kalkulasi nilai rapor
    - Buat `tests/Unit/Services/RaporServicePropertyTest.php`
    - **Property 1: Kalkulasi Nilai Rapor** — 100 iterasi dengan kombinasi PH/TUGAS/ATS/SAS acak (termasuk parsial)
    - **Validates: Requirements 4.1, 4.2, 4.3, 19.8**

  - [x] 2.3 Tulis property test untuk determinisme predikat nilai
    - **Property 2: Determinisme Predikat Nilai** — 100 iterasi dengan score acak 0–100
    - **Validates: Requirements 6.2, 19.9**

  - [x] 2.4 Implementasi `RaporService` — kalkulasi dan predikat (Green)
    - Buat `app/Services/RaporService.php`
    - Implementasi `calculateRaporScore(array $phScores, array $tugasScores, float $ats, float $sas): float`
    - Implementasi `assignPredicate(float $score): string`
    - Jalankan tests 2.1, 2.2, 2.3 — semua harus lulus
    - _Requirements: 4.1, 4.2, 4.3, 6.2_

  - [x] 2.5 Tulis unit tests untuk `upsertGrade()` dan `recalculateRaporScore()` (Red)
    - Test `upsertGrade()` membuat record baru jika belum ada
    - Test `upsertGrade()` mengupdate record yang sudah ada (tidak duplikat)
    - Test `recalculateRaporScore()` menyimpan Grade dengan `grade_type = 'RAPOR'`
    - Test `recalculateRaporScore()` mengupdate nilai RAPOR setelah komponen berubah
    - _Requirements: 4.4, 4.6, 17.4, 17.5_

  - [x] 2.6 Tulis property test untuk upsert grade tidak membuat duplikat
    - **Property 3: Upsert Grade tidak membuat duplikat** — 100 iterasi dengan grade_type acak, simpan dua kali
    - **Validates: Requirements 17.4, 17.5**

  - [x] 2.7 Tulis property test untuk konsistensi nilai rapor setelah setiap simpan
    - **Property 4: Konsistensi Nilai Rapor setelah setiap simpan** — 100 iterasi dengan urutan simpan acak
    - **Validates: Requirements 4.4, 4.6**

  - [x] 2.8 Implementasi `upsertGrade()` dan `recalculateRaporScore()` di `RaporService` (Green)
    - Implementasi `upsertGrade(string $studentId, string $subjectId, string $academicYearId, string $gradeType, float $score): Grade`
    - Implementasi `recalculateRaporScore(string $studentId, string $subjectId, string $academicYearId): Grade`
    - Implementasi `saveGrades(array $gradeData, string $scheduleId, string $academicYearId): void` dengan `DB::transaction()`
    - Jalankan tests 2.5, 2.6, 2.7 — semua harus lulus
    - _Requirements: 1.4, 1.5, 1.6, 4.4, 4.6, 17.4, 17.5_

- [x] 3. Checkpoint — Pastikan semua unit tests lulus
  - Jalankan `php artisan test --compact tests/Unit/Services/RaporServiceTest.php tests/Unit/Services/RaporServicePropertyTest.php`
  - Semua tests harus lulus sebelum melanjutkan ke task berikutnya. Tanyakan ke user jika ada pertanyaan.

- [x] 4. Policies untuk Authorization
  - [x] 4.1 Tulis feature tests untuk GradePolicy (Red)
    - Buat `tests/Feature/Policies/GradePolicyTest.php`
    - Test Guru hanya bisa edit grade untuk jadwal miliknya
    - Test Guru tidak bisa akses grade untuk jadwal bukan miliknya
    - Test Admin bisa akses semua grade
    - _Requirements: 1.3, 2.2, 3.2, 14.3_

  - [x] 4.2 Implementasi `GradePolicy` (Green)
    - Buat `app/Policies/GradePolicy.php` menggunakan `php artisan make:policy GradePolicy --model=Grade --no-interaction`
    - Implementasi `viewAny`, `view`, `create`, `update`, `delete` berdasarkan role dan jadwal
    - Daftarkan policy di `AuthServiceProvider` atau `AppServiceProvider`
    - Jalankan tests 4.1 — semua harus lulus
    - _Requirements: 1.3, 14.3_

  - [x] 4.3 Tulis feature tests untuk RaporPolicy (Red)
    - Buat `tests/Feature/Policies/RaporPolicyTest.php`
    - Test Wali Kelas bisa finalisasi rapor kelas miliknya
    - Test Kepsek bisa approve/reject rapor FINALIZED
    - Test Siswa hanya bisa download rapor APPROVED miliknya
    - Test Guru tidak bisa edit nilai setelah rapor FINALIZED/APPROVED
    - _Requirements: 11.4, 12.2, 12.5, 15.3_

  - [x] 4.4 Implementasi `RaporPolicy`, `AttitudeScorePolicy`, `KnowledgeSkillScorePolicy`, `LearningAchievementPolicy`, `PersonalityScorePolicy`, `SubjectKkmPolicy` (Green)
    - Buat semua policy menggunakan `php artisan make:policy --no-interaction`
    - Implementasi authorization logic sesuai design
    - Jalankan tests 4.3 — semua harus lulus
    - _Requirements: 5.4, 6.5, 7.2, 8.3, 11.4, 12.2, 12.5, 15.3_

- [x] 5. Guru Panel — GradeInputPage (TDD)
  - [x] 5.1 Tulis feature tests untuk GradeInputPage (Red)
    - Buat `tests/Feature/Filament/Guru/GradeInputPageTest.php`
    - Test Guru dapat melihat halaman input nilai untuk jadwal miliknya
    - Test Guru tidak dapat akses jadwal bukan miliknya (403)
    - Test input nilai PH berhasil disimpan dan nilai RAPOR terhitung ulang
    - Test input nilai TUGAS berhasil disimpan
    - Test input nilai ATS/SAS berhasil disimpan
    - Test nilai di luar 0–100 ditolak validasi
    - Test transaksi rollback jika ada error (semua perubahan dibatalkan)
    - Test nilai RAPOR ditampilkan bersama komponen nilai
    - _Requirements: 1.1–1.6, 2.1–2.4, 3.1–3.4, 4.4, 4.5, 14.1–14.4, 17.1, 17.2, 19.1, 19.2_

  - [x] 5.2 Implementasi `GradeInputPage` di Guru Panel (Green)
    - Buat `app/Filament/Guru/Pages/GradeInputPage.php` sebagai custom Filament Page
    - Implementasi grid siswa × tipe nilai (PH1–PH4, TUGAS1–TUGAS4, ATS, SAS)
    - Tampilkan nilai RAPOR yang terhitung otomatis
    - Integrasikan dengan `RaporService::saveGrades()` dalam `DB::transaction()`
    - Tampilkan Filament `Notification::success()` / `Notification::danger()`
    - Jalankan tests 5.1 — semua harus lulus
    - _Requirements: 1.1–1.6, 2.1–2.4, 3.1–3.4, 4.4, 4.5, 14.1–14.4_

- [x] 6. Guru Panel — AttitudeScoreResource (TDD)
  - [x] 6.1 Tulis feature tests untuk AttitudeScoreResource (Red)
    - Buat `tests/Feature/Filament/Guru/AttitudeScoreResourceTest.php`
    - Test Wali Kelas dapat input nilai sikap untuk siswa di kelasnya
    - Test Wali Kelas tidak dapat input untuk kelas lain (403)
    - Test rata-rata nilai sikap terhitung dan ditampilkan
    - Test simpan dalam satu transaksi
    - _Requirements: 5.1–5.5, 19.3_

  - [x] 6.2 Implementasi `AttitudeScoreResource` di Guru Panel (Green)
    - Buat `app/Filament/Guru/Resources/AttitudeScoreResource.php` menggunakan `php artisan make:filament-resource --no-interaction`
    - Implementasi form dengan aspek Spiritual, Sosial, dan aspek kustom
    - Tampilkan rata-rata nilai sikap
    - Jalankan tests 6.1 — semua harus lulus
    - _Requirements: 5.1–5.5_

- [x] 7. Guru Panel — KnowledgeSkillScoreResource (TDD)
  - [x] 7.1 Tulis feature tests untuk KnowledgeSkillScoreResource (Red)
    - Buat `tests/Feature/Filament/Guru/KnowledgeSkillScoreResourceTest.php`
    - Test Guru dapat input nilai pengetahuan & keterampilan untuk jadwal miliknya
    - Test predikat otomatis ter-assign berdasarkan skor (A/B/C/D)
    - Test KKM ditampilkan di samping input nilai
    - Test nilai di bawah KKM mendapat indikator visual warning
    - Test Guru tidak bisa akses jadwal bukan miliknya
    - _Requirements: 6.1–6.6, 19.3_

  - [x] 7.2 Implementasi `KnowledgeSkillScoreResource` di Guru Panel (Green)
    - Buat `app/Filament/Guru/Resources/KnowledgeSkillScoreResource.php`
    - Implementasi auto-assign predikat via `RaporService::assignPredicate()`
    - Tampilkan KKM dari `SubjectKkm::getKkm()` (default 70 jika tidak ada)
    - Highlight nilai di bawah KKM
    - Jalankan tests 7.1 — semua harus lulus
    - _Requirements: 6.1–6.6_

- [x] 8. Guru Panel — LearningAchievementResource (TDD)
  - [x] 8.1 Tulis feature tests untuk LearningAchievementResource (Red)
    - Buat `tests/Feature/Filament/Guru/LearningAchievementResourceTest.php`
    - Test Guru dapat input capaian pembelajaran untuk jadwal miliknya
    - Test tampilkan rata-rata PH, ATS, SAS sebagai referensi
    - Test Guru tidak bisa akses jadwal bukan miliknya
    - _Requirements: 7.1–7.4, 19.3_

  - [x] 8.2 Implementasi `LearningAchievementResource` di Guru Panel (Green)
    - Buat `app/Filament/Guru/Resources/LearningAchievementResource.php`
    - Implementasi form dengan `topic_coverage` (Pemaparan Materi) dan `notes` (Keterangan)
    - Tampilkan rata-rata PH, ATS, SAS sebagai referensi read-only
    - Jalankan tests 8.1 — semua harus lulus
    - _Requirements: 7.1–7.4_

- [x] 9. Guru Panel — PersonalityScoreResource (TDD)
  - [x] 9.1 Tulis feature tests untuk PersonalityScoreResource (Red)
    - Buat `tests/Feature/Filament/Guru/PersonalityScoreResourceTest.php`
    - Test Wali Kelas dapat input kepribadian (A/B/C/D) untuk siswa di kelasnya
    - Test nilai selain A/B/C/D ditolak validasi
    - Test Wali Kelas tidak bisa akses kelas lain
    - _Requirements: 8.1–8.4, 17.6, 19.3_

  - [x] 9.2 Implementasi `PersonalityScoreResource` di Guru Panel (Green)
    - Buat `app/Filament/Guru/Resources/PersonalityScoreResource.php`
    - Implementasi form dengan 4 aspek: Kedisiplinan, Kerapihan, Kerajinan, Kesopanan
    - Validasi nilai hanya A/B/C/D menggunakan `in:A,B,C,D` rule
    - Jalankan tests 9.1 — semua harus lulus
    - _Requirements: 8.1–8.4, 17.6_

- [x] 10. Checkpoint — Pastikan semua Guru Panel tests lulus
  - Jalankan `php artisan test --compact tests/Feature/Filament/Guru/`
  - Semua tests harus lulus sebelum melanjutkan. Tanyakan ke user jika ada pertanyaan.

- [x] 11. Admin Panel — SubjectKkmResource (TDD)
  - [x] 11.1 Tulis feature tests untuk SubjectKkmResource (Red)
    - Buat `tests/Feature/Filament/Admin/SubjectKkmResourceTest.php`
    - Test Admin dapat set KKM per mapel per level
    - Test default KKM 70 digunakan jika tidak dikonfigurasi
    - Test non-Admin tidak bisa akses resource ini
    - _Requirements: 10.1–10.5, 16.2, 19.5_

  - [x] 11.2 Implementasi `SubjectKkmResource` di Admin Panel (Green)
    - Buat `app/Filament/Clusters/Academic/Resources/SubjectKkmResource.php`
    - Implementasi static helper `SubjectKkm::getKkm(string $subjectId, string $levelId): float`
    - Jalankan tests 11.1 — semua harus lulus
    - _Requirements: 10.1–10.5_

- [x] 12. Workflow Rapor — Finalisasi dan Approval (TDD)
  - [x] 12.1 Implementasi `validateCompleteness()`, `finalizeRapor()`, `approveRapor()`, `rejectRapor()` di `RaporService`
    - Tambahkan method ke `app/Services/RaporService.php`
    - `validateCompleteness(Rapor $rapor): array` — kembalikan array komponen yang kurang
    - `finalizeRapor(Rapor $rapor): void` — ubah status ke FINALIZED
    - `approveRapor(Rapor $rapor): void` — ubah status ke APPROVED, set `approved_at`
    - `rejectRapor(Rapor $rapor, string $rejectionNote): void` — kembalikan ke DRAFT dengan catatan
    - _Requirements: 11.1–11.5, 12.1–12.6_

  - [x] 12.2 Tulis feature tests untuk workflow rapor (Red)
    - Buat `tests/Feature/Filament/Rapor/RaporWorkflowTest.php`
    - Test Wali Kelas dapat finalisasi rapor yang lengkap (DRAFT → FINALIZED)
    - Test finalisasi gagal jika komponen nilai kurang (tampilkan daftar missing)
    - Test Kepsek dapat approve rapor FINALIZED (FINALIZED → APPROVED)
    - Test Kepsek dapat reject rapor FINALIZED (FINALIZED → DRAFT dengan catatan)
    - Test Guru tidak dapat edit nilai setelah rapor FINALIZED
    - Test Guru tidak dapat edit nilai setelah rapor APPROVED
    - Test Wali Kelas dapat revert FINALIZED ke DRAFT (sebelum di-approve)
    - _Requirements: 11.1–11.5, 12.1–12.6, 19.4_

  - [x] 12.3 Implementasi Wali Kelas finalisasi rapor di Guru Panel (Green)
    - Tambahkan action finalisasi ke resource/page rapor di Guru Panel
    - Tampilkan modal dengan daftar komponen yang kurang jika validasi gagal
    - Jalankan tests 12.2 — semua harus lulus
    - _Requirements: 11.1–11.5_

  - [x] 12.4 Implementasi Kepsek approval di Kepsek Panel (Green)
    - Buat `app/Filament/Kepsek/Resources/RaporResource.php`
    - Implementasi action approve dan reject dengan modal catatan penolakan
    - Kepsek tidak bisa create/edit/delete data nilai (hanya approve/reject)
    - Jalankan tests 12.2 — semua harus lulus
    - _Requirements: 12.1–12.6_

- [x] 13. Admin Panel — RaporResource dan GradeResource (TDD)
  - [x] 13.1 Tulis feature tests untuk Admin RaporResource dan GradeResource (Red)
    - Buat `tests/Feature/Filament/Admin/RaporResourceTest.php`
    - Test Admin dapat melihat semua rapor dengan filter (tahun akademik, kelas, mapel, siswa)
    - Test Admin dapat mengubah Rapor_Status
    - Test Admin dapat generate dan download PDF rapor
    - Test Admin dapat CRUD semua Grade record
    - _Requirements: 16.1–16.5, 18.3_

  - [x] 13.2 Implementasi `RaporResource` dan `GradeResource` di Admin Panel (Green)
    - Buat/lengkapi `app/Filament/Clusters/Academic/Resources/RaporResource.php`
    - Buat/lengkapi `app/Filament/Clusters/Academic/Resources/GradeResource.php`
    - Implementasi filter: academic_year, class, subject, student
    - Implementasi paginasi default 25 records
    - Eager load relasi (Student, Subject, AcademicYear, SchoolClass) untuk mencegah N+1
    - Jalankan tests 13.1 — semua harus lulus
    - _Requirements: 16.1–16.5, 18.1, 18.3_

- [x] 14. Student Panel — MyGradesPage dan MyRaporPage (TDD)
  - [x] 14.1 Tulis feature tests untuk Student Panel (Red)
    - Buat `tests/Feature/Filament/Student/MyGradesPageTest.php`
    - Test Siswa hanya melihat nilai miliknya sendiri (tidak bisa lihat nilai siswa lain)
    - Test Siswa tidak dapat memodifikasi nilai
    - Test Siswa dapat melihat nilai dikelompokkan per mapel dengan semua Grade_Type dan Rapor_Score
    - Test Siswa tanpa profil Student mendapat pesan informatif
    - Test Siswa dapat download rapor APPROVED miliknya
    - Test Siswa tidak dapat download rapor DRAFT atau FINALIZED
    - _Requirements: 15.1–15.5, 19.5_

  - [x] 14.2 Implementasi `MyGradesPage` dan `MyRaporPage` di Student Panel (Green)
    - Buat `app/Filament/Student/Pages/MyGradesPage.php`
    - Buat `app/Filament/Student/Pages/MyRaporPage.php`
    - Tampilkan nilai dikelompokkan per mapel
    - Tampilkan pesan informatif jika tidak ada profil Student
    - Tombol download hanya aktif untuk rapor APPROVED
    - Jalankan tests 14.1 — semua harus lulus
    - _Requirements: 15.1–15.5_

- [x] 15. Rekap Absensi Otomatis
  - [x] 15.1 Tulis unit tests untuk `AttendanceSummaryService` — metode baru (Red)
    - Tambahkan tests ke `tests/Unit/Services/AttendanceSummaryServiceTest.php`
    - Test `getMonthlyBreakdownBySubject()` mengelompokkan absensi per bulan per mapel
    - Test semester 1 menggunakan bulan Juli–Desember
    - Test semester 2 menggunakan bulan Januari–Juni
    - Test `getOverallSummary()` menghitung total SAKIT, IZIN, ALPA
    - _Requirements: 9.1–9.5_

  - [x] 15.2 Implementasi metode baru di `AttendanceSummaryService` (Green)
    - Tambahkan `getMonthlyBreakdownBySubject(Student $student, AcademicYear $academicYear): Collection`
    - Tambahkan `getOverallSummary(Student $student, AcademicYear $academicYear): array`
    - Jalankan tests 15.1 — semua harus lulus
    - _Requirements: 9.1–9.5_

- [x] 16. Checkpoint — Pastikan semua tests lulus sebelum PDF generation
  - Jalankan `php artisan test --compact`
  - Semua tests harus lulus sebelum melanjutkan ke PDF generation. Tanyakan ke user jika ada pertanyaan.

- [x] 17. Generate PDF Rapor (TDD)
  - [x] 17.1 Tulis feature tests untuk PDF generation (Red)
    - Buat `tests/Feature/Filament/Rapor/RaporPdfTest.php`
    - Test PDF berhasil digenerate dan `file_path` tersimpan di tabel `rapors`
    - Test regenerasi PDF menimpa file lama (untuk rapor DRAFT/FINALIZED)
    - Test PDF tidak dapat digenerate ulang untuk rapor APPROVED (kecuali Kepsek revert)
    - Test Siswa dapat download rapor APPROVED (file tersedia)
    - Test Siswa tidak dapat download rapor DRAFT/FINALIZED
    - _Requirements: 13.7, 13.8, 13.9, 19.10_

  - [x] 17.2 Buat Blade template PDF rapor 3 halaman
    - Buat `resources/views/rapor/pdf.blade.php`
    - **Halaman 1**: Header siswa, tabel absensi per mapel per bulan, tabel daftar nilai (PH1–PH4, TUGAS1–TUGAS4, ATS, SAS, Nilai Rapor, Guru Bidang Studi)
    - **Halaman 2**: Nilai Sikap, Nilai Pengetahuan & Keterampilan (KKM, nilai, predikat, deskripsi), rekap absensi (Sakit/Izin/Alpa), Kepribadian, TTD (Orang Tua/Wali, Wali Kelas, Kepala Sekolah)
    - **Halaman 3**: Tabel Capaian Pembelajaran (Mapel, Pemaparan Materi, PH avg, ATS, SAS, Keterangan), legenda predikat, TTD (Ketua Litbang HS-TKB, Wali Kelas)
    - _Requirements: 13.1–13.6_

  - [x] 17.3 Implementasi `generatePdf()` di `RaporService` (Green)
    - Implementasi `generatePdf(Rapor $rapor): string` menggunakan `barryvdh/laravel-dompdf`
    - Load semua data dalam maksimal 5 query menggunakan eager loading
    - Simpan file ke storage dan update `file_path` di tabel `rapors`
    - Tangkap exception, log error, dan tampilkan notifikasi danger jika gagal
    - Jalankan tests 17.1 — semua harus lulus
    - _Requirements: 13.7, 13.8, 13.9, 18.4_

  - [x] 17.4 Integrasikan tombol generate/download PDF ke semua panel yang relevan
    - Tambahkan action generate PDF di Guru Panel (Wali Kelas), Admin Panel, Kepsek Panel
    - Tombol download di Student Panel (hanya APPROVED)
    - _Requirements: 13.1, 13.7, 13.8, 13.9_

- [x] 18. Kepsek Panel — RaporResource (TDD)
  - [x] 18.1 Tulis feature tests untuk Kepsek RaporResource (Red)
    - Buat `tests/Feature/Filament/Kepsek/RaporResourceTest.php`
    - Test Kepsek dapat melihat semua rapor dengan status masing-masing
    - Test Kepsek tidak dapat edit data nilai (hanya approve/reject)
    - Test Kepsek dapat approve rapor FINALIZED
    - Test Kepsek dapat reject rapor FINALIZED dengan catatan
    - _Requirements: 12.1–12.6, 19.4_

  - [x] 18.2 Lengkapi implementasi `RaporResource` di Kepsek Panel (Green)
    - Pastikan `canCreate()`, `canEdit()`, `canDelete()` mengembalikan `false`
    - Tampilkan semua rapor dengan filter status
    - Jalankan tests 18.1 — semua harus lulus
    - _Requirements: 12.1–12.6_

- [x] 19. Final Checkpoint — Semua tests lulus dan kode bersih
  - Jalankan `php artisan test --compact` — semua tests harus lulus
  - Jalankan `vendor/bin/pint --dirty --format agent` untuk memastikan code style konsisten
  - Pastikan tidak ada N+1 query: eager load relasi di semua list view
  - Pastikan semua Filament resource menggunakan paginasi default 25 records di Admin Panel
  - Tanyakan ke user jika ada pertanyaan sebelum dianggap selesai.

## Notes

- Tasks bertanda `*` bersifat opsional dan dapat dilewati untuk MVP yang lebih cepat
- Setiap task implementasi **harus** didahului oleh task test-nya (TDD: Red → Green → Refactor)
- Property tests (2.2, 2.3, 2.6, 2.7) menjalankan **100 iterasi** dengan input acak menggunakan `fake()` dalam loop
- Semua operasi bulk save dibungkus dalam `DB::transaction()` dengan rollback otomatis
- Gunakan `php artisan make:` commands untuk membuat file baru (models, migrations, policies, resources)
- Jalankan `vendor/bin/pint --dirty --format agent` setelah setiap sesi modifikasi PHP
