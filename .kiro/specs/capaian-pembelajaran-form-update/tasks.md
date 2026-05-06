# Implementation Plan: capaian-pembelajaran-form-update

## Overview

Menambahkan 5 kolom baru ke tabel `learning_achievements`, memperbarui model, form, tabel, factory, dan template rapor PDF agar sesuai dengan struktur e-rapor.

## Tasks

- [x] 1. Buat migration untuk menambah kolom baru ke tabel `learning_achievements`
  - Tambahkan kolom `material_coverage_status` (enum nullable), `daily_assessment_predicate` (enum nullable), `midterm_assessment_predicate` (enum nullable), `final_assessment_predicate` (enum nullable), `achievement_status` (string nullable)
  - Method `down()` harus menghapus semua 5 kolom tersebut
  - _Requirements: 1.1, 1.2, 2.1, 2.2, 2.3, 3.1, 18.1, 18.2, 18.3_

- [x] 2. Update model `LearningAchievement`
  - [x] 2.1 Tambahkan 5 kolom baru ke `$fillable`
    - Tambahkan `material_coverage_status`, `daily_assessment_predicate`, `midterm_assessment_predicate`, `final_assessment_predicate`, `achievement_status`
    - _Requirements: 1.3, 2.4, 3.2, 14.1, 14.2, 14.3_
  - [x] 2.2 Tambahkan PHPDoc block untuk property baru
    - Dokumentasikan tipe dan nilai enum yang valid untuk setiap property
    - _Requirements: 14.1, 14.2, 14.3_

- [x] 3. Update `LearningAchievementForm` — Section "Referensi Nilai"
  - Tambahkan 3 Placeholder predikat otomatis (`suggested_ph_predicate`, `suggested_ats_predicate`, `suggested_sas_predicate`) di baris kedua Section "Referensi Nilai"
  - Gunakan helper closure `$calculatePredicate` yang dapat digunakan kembali
  - Placeholder menampilkan "—" jika nilai tidak tersedia
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6_

- [x] 4. Update `LearningAchievementForm` — Section "Capaian Pembelajaran"
  - [x] 4.1 Tambahkan field `material_coverage_status` (Radio, inline) sebelum `topic_coverage`
    - Label "Status Pemaparan Materi", opsi: Terpenuhi / Tidak Terpenuhi, nullable
    - _Requirements: 4.2, 4.3, 4.4, 4.5_
  - [x] 4.2 Tambahkan field `achievement_status` (TextInput) setelah `topic_coverage`
    - Label "Keterangan Capaian", placeholder "Contoh: Terlampaui, Berkembang, dll", nullable
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [x] 5. Update `LearningAchievementForm` — Section baru "Hasil Pembelajaran"
  - Tambahkan Section baru setelah Section "Referensi Nilai" dengan 3 kolom
  - Tambahkan Select untuk `daily_assessment_predicate` (label: Penilaian Harian), `midterm_assessment_predicate` (label: Asesmen Tengah Semester), `final_assessment_predicate` (label: Sumatif Akhir Semester)
  - Opsi semua Select: Kurang, Cukup, Baik, Sangat Baik — semua nullable
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9_

- [x] 6. Update `LearningAchievementsTable`
  - Tambahkan kolom `material_coverage_status` (label: Status Materi, sortable) setelah kolom `academicYear.name`
  - Tambahkan kolom `achievement_status` (label: Keterangan Capaian, limit 40) setelah kolom Status Materi
  - Ubah label `topic_coverage` menjadi "Pemaparan Materi (Detail)"
  - Tambahkan kolom predikat (PH, ATS, SAS) dengan badge berwarna, toggleable hidden by default
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 9.1, 9.2, 9.3, 9.4, 19.1, 19.2_

- [x] 7. Update `LearningAchievementFactory`
  - Tambahkan definisi untuk 5 kolom baru dengan nilai random yang sesuai
  - _Requirements: 10.1, 10.2, 10.3_

- [x] 8. Update template rapor `halaman3.blade.php`
  - Kolom "Pemaparan Materi": tampilkan `material_coverage_status ?? topic_coverage ?? '—'`
  - Kolom PH: tampilkan `daily_assessment_predicate ?? $phAvg`
  - Kolom ATS: tampilkan `midterm_assessment_predicate ?? grades['ATS'] ?? '—'`
  - Kolom SAS: tampilkan `final_assessment_predicate ?? grades['SAS'] ?? '—'`
  - Kolom Keterangan: tampilkan `achievement_status ?? notes ?? '—'`
  - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5, 15.1, 15.2_

- [x] 9. Checkpoint — Jalankan migration dan pastikan tidak ada error
  - Jalankan `php artisan migrate` dan pastikan semua kolom baru terbuat
  - Ensure all tests pass, ask the user if questions arise.

- [x] 10. Tulis feature test untuk form create/edit
  - [x] 10.1 Test: guru dapat membuat LearningAchievement baru dengan semua field baru terisi
    - Verifikasi data tersimpan ke database dengan benar
    - _Requirements: 11.1, 11.2, 11.3_
  - [ ]* 10.2 Test: form dapat di-submit tanpa field opsional (nullable fields)
    - _Requirements: 11.4_
  - [ ]* 10.3 Test: data lama (tanpa kolom baru) dapat diedit tanpa error — backward compatibility
    - _Requirements: 15.2, 11.5_

- [x] 11. Tulis feature test untuk table display
  - [ ]* 11.1 Test: kolom `material_coverage_status` tampil di tabel
    - _Requirements: 12.1, 12.2_
  - [ ]* 11.2 Test: kolom `achievement_status` tampil di tabel
    - _Requirements: 12.1, 12.3_

- [x] 12. Tulis property test untuk logika kalkulasi predikat
  - [ ]* 12.1 Property 1: `calculatePredicate` mengembalikan predikat yang tepat untuk semua nilai 0–100
    - **Property 1: Logika Kalkulasi Predikat**
    - **Validates: Requirements 7.2, 7.3, 7.4**
  - [ ]* 12.2 Property 2: `calculatePredicate` mengembalikan '—' untuk nilai null
    - **Property 2: Nilai Null Menghasilkan Placeholder**
    - **Validates: Requirements 7.5**
  - [ ]* 12.3 Property 3: LearningAchievement dengan semua kolom baru null dapat disimpan dan diambil kembali
    - **Property 3: Backward Compatibility — Data Lama Tetap Valid**
    - **Validates: Requirements 15.1, 15.2**

- [x] 13. Final checkpoint — Jalankan semua test
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks bertanda `*` adalah opsional dan dapat dilewati untuk MVP yang lebih cepat
- Setiap task mereferensikan requirement spesifik untuk traceability
- Logika `calculatePredicate` adalah closure di dalam form — tidak perlu helper class terpisah
- Backward compatibility dijamin oleh nullable columns dan operator `??` di template
