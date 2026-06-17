# Requirements Document: Modul Penilaian (Assessment)

## Introduction

Modul Penilaian mengimplementasikan sistem input nilai, kalkulasi rapor, dan cetak rapor digital untuk aplikasi manajemen sekolah homeschooling. Sistem ini mencakup empat komponen nilai akademik (Penilaian Harian, Tugas/PR, ATS, SAS), nilai sikap, nilai pengetahuan dan keterampilan, capaian pembelajaran, serta kepribadian siswa. Rapor digenerate dalam format PDF tiga halaman sesuai format Excel yang sudah ada.

Tabel `grades` sudah tersedia di database dengan kolom: `id` (ULID), `student_id`, `subject_id`, `academic_year_id`, `grade_type` (varchar), `score` (decimal 5,2). Tabel `rapors` sudah tersedia dengan kolom: `id`, `student_id`, `academic_year_id`, `file_path`. Tabel `attendances` sudah tersedia dan digunakan untuk rekap absensi otomatis pada rapor.

Modul ini membutuhkan beberapa tabel baru untuk menyimpan data yang belum ada di schema saat ini: nilai sikap, capaian pembelajaran, kepribadian, KKM per mata pelajaran per level, dan status finalisasi rapor.

## Glossary

- **Assessment_System**: Sistem penilaian dan pengelolaan rapor siswa
- **Grade**: Record nilai satu siswa untuk satu mata pelajaran pada satu tahun akademik dengan tipe tertentu
- **Grade_Type**: Jenis nilai — `PH1`, `PH2`, `PH3`, `PH4` (Penilaian Harian), `TUGAS1`, `TUGAS2`, `TUGAS3`, `TUGAS4` (Tugas/PR), `ATS` (Asesmen Tengah Semester), `SAS` (Sumatif Akhir Semester)
- **Rapor**: Dokumen laporan hasil belajar siswa per semester, disimpan sebagai file PDF
- **Rapor_Score**: Nilai akhir rapor yang dikalkulasi dari komponen nilai (rata-rata PH + rata-rata Tugas + ATS + SAS)
- **Attitude_Score**: Nilai sikap siswa yang mencakup aspek penilaian dan deskripsi
- **Knowledge_Score**: Nilai pengetahuan per mata pelajaran dengan predikat dan deskripsi
- **Skill_Score**: Nilai keterampilan per mata pelajaran dengan predikat dan deskripsi
- **Learning_Achievement**: Capaian pembelajaran per mata pelajaran yang mendeskripsikan materi dan hasil belajar
- **Personality_Score**: Penilaian kepribadian siswa: Kedisiplinan, Kerapihan, Kerajinan, Kesopanan (A/B/C/D)
- **KKM**: Kriteria Ketuntasan Minimal — nilai minimum kelulusan per mata pelajaran per level
- **Grade_Predicate**: Predikat nilai berdasarkan rentang skor: A (86–100, Sangat Baik), B (73–85, Baik), C (60–72, Cukup), D (<60, Kurang)
- **AcademicYear**: Tahun akademik dengan semester (1 atau 2) dan status aktif
- **Subject**: Mata pelajaran yang terikat pada level tertentu
- **Student**: Siswa yang dinilai
- **Teacher**: Guru yang mengajar dan menginput nilai mata pelajaran yang diajarnya
- **Wali_Kelas**: Guru yang menjadi wali kelas, bertanggung jawab atas nilai sikap, kepribadian, dan finalisasi rapor
- **Kepsek**: Kepala Sekolah yang menyetujui dan melihat semua rapor
- **Admin**: Pengguna dengan role `super_admin` yang memiliki akses penuh ke semua data penilaian
- **Rapor_Status**: Status rapor — `DRAFT`, `FINALIZED`, `APPROVED`
- **Attendance_Summary**: Rekap absensi per mata pelajaran per bulan yang diambil dari tabel `attendances`

## Requirements

### Requirement 1: Input Nilai Harian (Penilaian Harian) oleh Guru

**User Story:** As a Guru, I want to input up to four daily assessment scores (PH1–PH4) per student per subject, so that I can record ongoing student performance throughout the semester.

#### Acceptance Criteria

1. WHEN a Guru accesses the grade input page for a subject, THE Assessment_System SHALL display all students enrolled in the SchoolClass associated with that subject's Schedule.
2. THE Assessment_System SHALL allow a Guru to input up to four Penilaian Harian scores (PH1, PH2, PH3, PH4) per student per subject per academic year, with each score as a decimal value between 0 and 100.
3. THE Assessment_System SHALL restrict a Guru to only input grades for subjects assigned to their Schedules in the active AcademicYear.
4. WHEN a Guru submits Penilaian Harian scores, THE Assessment_System SHALL create or update Grade records with the corresponding Grade_Type (`PH1`, `PH2`, `PH3`, `PH4`).
5. WHEN a Guru saves grades for a class, THE Assessment_System SHALL save all records in a single database transaction and display a success notification.
6. IF the grade save transaction fails, THEN THE Assessment_System SHALL roll back all changes and display an error notification to the Guru.

### Requirement 2: Input Nilai Tugas/PR oleh Guru

**User Story:** As a Guru, I want to input up to four assignment/homework scores (TUGAS1–TUGAS4) per student per subject, so that I can record student performance on assignments throughout the semester.

#### Acceptance Criteria

1. THE Assessment_System SHALL allow a Guru to input up to four Tugas/PR scores (TUGAS1, TUGAS2, TUGAS3, TUGAS4) per student per subject per academic year, with each score as a decimal value between 0 and 100.
2. THE Assessment_System SHALL restrict a Guru to only input Tugas/PR grades for subjects assigned to their Schedules.
3. WHEN a Guru submits Tugas/PR scores, THE Assessment_System SHALL create or update Grade records with the corresponding Grade_Type (`TUGAS1`, `TUGAS2`, `TUGAS3`, `TUGAS4`).
4. WHEN a Guru saves Tugas/PR grades for a class, THE Assessment_System SHALL save all records in a single database transaction.

### Requirement 3: Input Nilai ATS dan SAS oleh Guru

**User Story:** As a Guru, I want to input the mid-semester assessment (ATS) and end-semester assessment (SAS) scores per student per subject, so that I can record formal assessment results.

#### Acceptance Criteria

1. THE Assessment_System SHALL allow a Guru to input one ATS score and one SAS score per student per subject per academic year, with each score as a decimal value between 0 and 100.
2. THE Assessment_System SHALL restrict a Guru to only input ATS and SAS grades for subjects assigned to their Schedules.
3. WHEN a Guru submits ATS or SAS scores, THE Assessment_System SHALL create or update Grade records with Grade_Type `ATS` or `SAS` respectively.
4. WHEN a Guru saves ATS or SAS grades for a class, THE Assessment_System SHALL save all records in a single database transaction.

### Requirement 4: Kalkulasi Otomatis Nilai Rapor

**User Story:** As a Guru or Admin, I want the system to automatically calculate the final report card score from grade components, so that I don't have to manually compute the final score.

#### Acceptance Criteria

1. THE Assessment_System SHALL calculate Rapor_Score for each student per subject using the formula: `((average of available PH scores) + (average of available TUGAS scores) + ATS + SAS) / 4`, rounded to two decimal places.
2. WHEN only some PH or TUGAS scores are entered (e.g., only PH1 and PH2), THE Assessment_System SHALL calculate the average using only the available scores (non-null values).
3. WHEN ATS or SAS score is missing, THE Assessment_System SHALL treat the missing component as 0 in the Rapor_Score calculation.
4. THE Assessment_System SHALL recalculate Rapor_Score automatically whenever any component grade is saved.
5. THE Assessment_System SHALL display the calculated Rapor_Score alongside the component grades in the grade input view.
6. THE Assessment_System SHALL store the calculated Rapor_Score as a Grade record with Grade_Type `RAPOR`.

### Requirement 5: Input Nilai Sikap oleh Wali Kelas

**User Story:** As a Wali_Kelas, I want to input attitude assessment scores and descriptions for each student, so that I can document student behavioral performance in the report card.

#### Acceptance Criteria

1. THE Assessment_System SHALL allow a Wali_Kelas to input attitude assessment scores for each student in their class, with each aspect having a numeric score (0–100) and a text description.
2. THE Assessment_System SHALL support at least the following attitude aspects: Spiritual, Sosial, and any custom aspects defined by the school.
3. THE Assessment_System SHALL calculate and display the average attitude score across all assessed aspects for each student.
4. THE Assessment_System SHALL restrict Wali_Kelas to only input attitude scores for students in the SchoolClass they are assigned to as `teacher_id`.
5. WHEN a Wali_Kelas saves attitude scores, THE Assessment_System SHALL save all records in a single database transaction.

### Requirement 6: Input Nilai Pengetahuan dan Keterampilan oleh Guru

**User Story:** As a Guru, I want to input knowledge and skill scores with predicates and descriptions per student per subject, so that I can provide detailed academic performance information for the report card.

#### Acceptance Criteria

1. THE Assessment_System SHALL allow a Guru to input a knowledge score (0–100) and a skill score (0–100) per student per subject per academic year.
2. THE Assessment_System SHALL automatically assign a Grade_Predicate based on the score: A for 86–100, B for 73–85, C for 60–72, D for below 60.
3. THE Assessment_System SHALL allow a Guru to input a text description for both knowledge and skill scores per student per subject.
4. THE Assessment_System SHALL display the KKM for each subject alongside the knowledge and skill score inputs.
5. THE Assessment_System SHALL restrict a Guru to only input knowledge and skill scores for subjects assigned to their Schedules.
6. WHEN a knowledge or skill score is below the KKM for the subject, THE Assessment_System SHALL visually highlight the score with a warning indicator.

### Requirement 7: Input Capaian Pembelajaran oleh Guru

**User Story:** As a Guru, I want to input learning achievement descriptions per student per subject, so that I can document what topics were covered and the student's learning outcomes.

#### Acceptance Criteria

1. THE Assessment_System SHALL allow a Guru to input a learning achievement record per student per subject per academic year, containing: topic coverage description (Pemaparan Materi), and a Keterangan (notes/remarks).
2. THE Assessment_System SHALL restrict a Guru to only input learning achievements for subjects assigned to their Schedules.
3. WHEN a Guru saves learning achievement data, THE Assessment_System SHALL save all records in a single database transaction.
4. THE Assessment_System SHALL display the Penilaian Harian average, ATS score, and SAS score alongside the learning achievement input for reference.

### Requirement 8: Input Kepribadian Siswa oleh Wali Kelas

**User Story:** As a Wali_Kelas, I want to input personality assessments for each student, so that I can document student character traits in the report card.

#### Acceptance Criteria

1. THE Assessment_System SHALL allow a Wali_Kelas to input personality scores for each student in their class across four aspects: Kedisiplinan, Kerapihan, Kerajinan, and Kesopanan.
2. THE Assessment_System SHALL restrict personality score values to the grades: A (Sangat Baik), B (Baik), C (Cukup), D (Kurang).
3. THE Assessment_System SHALL restrict Wali_Kelas to only input personality scores for students in the SchoolClass they are assigned to.
4. WHEN a Wali_Kelas saves personality scores, THE Assessment_System SHALL save all records in a single database transaction.

### Requirement 9: Rekap Absensi Otomatis pada Rapor

**User Story:** As a Wali_Kelas or Admin, I want the report card to automatically pull attendance data from the existing attendance records, so that I don't have to manually enter attendance summaries.

#### Acceptance Criteria

1. THE Assessment_System SHALL automatically calculate the attendance summary per student per subject by counting KBM sessions from the `attendances` table filtered by the active AcademicYear.
2. THE Assessment_System SHALL group attendance counts by month (July–December for semester 1, January–June for semester 2) per subject.
3. THE Assessment_System SHALL display the total session count per subject alongside the monthly breakdown.
4. THE Assessment_System SHALL count SAKIT, IZIN, and ALPA separately for the overall attendance summary section of the rapor.
5. WHEN generating the rapor, THE Assessment_System SHALL use the attendance data from the `attendances` table without requiring manual re-entry.

### Requirement 10: KKM per Mata Pelajaran per Level

**User Story:** As an Admin, I want to configure the minimum passing score (KKM) per subject per level, so that the system can display KKM on report cards and highlight students who have not met the minimum standard.

#### Acceptance Criteria

1. THE Assessment_System SHALL allow Admin to set a KKM value (0–100) per Subject per Level.
2. THE Assessment_System SHALL display the KKM value alongside knowledge and skill scores in the grade input and rapor views.
3. WHEN a student's Rapor_Score for a subject is below the KKM, THE Assessment_System SHALL visually indicate the score as below minimum standard.
4. THE Assessment_System SHALL use a default KKM of 70 when no KKM has been configured for a subject-level combination.
5. WHEN Admin updates a KKM value, THE Assessment_System SHALL apply the new value to all subsequent rapor generations without retroactively changing stored rapor PDFs.

### Requirement 11: Finalisasi Rapor oleh Wali Kelas

**User Story:** As a Wali_Kelas, I want to finalize a student's report card after all grades have been entered, so that the report card is locked and ready for principal approval.

#### Acceptance Criteria

1. THE Assessment_System SHALL allow a Wali_Kelas to finalize a rapor for each student in their class, changing the Rapor_Status from `DRAFT` to `FINALIZED`.
2. WHEN a Wali_Kelas finalizes a rapor, THE Assessment_System SHALL validate that all required grade components (at least one PH, ATS, SAS, knowledge score, skill score, attitude score, and personality score) have been entered for all subjects.
3. IF any required grade component is missing when a Wali_Kelas attempts to finalize, THEN THE Assessment_System SHALL display a list of missing components and prevent finalization.
4. WHEN a rapor is in `FINALIZED` or `APPROVED` status, THE Assessment_System SHALL prevent Guru from modifying any grade components for that student in that academic year.
5. THE Assessment_System SHALL allow a Wali_Kelas to revert a `FINALIZED` rapor back to `DRAFT` status if the rapor has not yet been approved by Kepsek.

### Requirement 12: Approval Rapor oleh Kepsek

**User Story:** As a Kepsek, I want to approve finalized report cards, so that I can officially authorize the report cards before they are distributed to students.

#### Acceptance Criteria

1. THE Assessment_System SHALL allow Kepsek to view all rapors with their current Rapor_Status.
2. THE Assessment_System SHALL allow Kepsek to approve a `FINALIZED` rapor, changing its Rapor_Status to `APPROVED`.
3. WHEN a Kepsek approves a rapor, THE Assessment_System SHALL record the approval timestamp.
4. THE Assessment_System SHALL NOT allow Kepsek to modify any grade data — only to approve or reject rapors.
5. THE Assessment_System SHALL allow Kepsek to reject a `FINALIZED` rapor, reverting it to `DRAFT` status with a rejection note.
6. WHEN a rapor is in `APPROVED` status, THE Assessment_System SHALL prevent any further modifications to grade data by Guru or Wali_Kelas.

### Requirement 13: Generate dan Cetak Rapor PDF

**User Story:** As a Wali_Kelas, Admin, or Kepsek, I want to generate and download a PDF report card for each student, so that the report card can be printed and distributed.

#### Acceptance Criteria

1. THE Assessment_System SHALL generate a PDF rapor for each student containing three sections: (1) Data Absensi & Daftar Nilai, (2) Laporan Hasil Belajar Siswa, (3) Capaian Pembelajaran.
2. THE PDF SHALL include the student header information: Nama, NIS/NISN, Kelas, Semester, Program, Tahun Pembelajaran.
3. THE PDF Section 1 SHALL include the attendance table (per subject per month) and the grade table (PH1–PH4, TUGAS1–TUGAS4, ATS, SAS, Nilai Rapor, Guru Bidang Studi).
4. THE PDF Section 2 SHALL include: Nilai Sikap (aspects and descriptions), Nilai Pengetahuan dan Keterampilan (KKM, score, predicate, description), attendance summary (Sakit/Izin/Alpa), and Kepribadian (Kedisiplinan, Kerapihan, Kerajinan, Kesopanan).
5. THE PDF Section 3 SHALL include the Capaian Pembelajaran table (subject, topic coverage, PH average, ATS, SAS, Keterangan) with the grade predicate legend (A=86–100, B=73–85, C=60–72, D<60).
6. THE PDF SHALL include signature placeholders for: Orang Tua/Wali, Wali Kelas, Kepala Sekolah (Section 2) and Ketua Litbang HS-TKB, Wali Kelas (Section 3).
7. WHEN a rapor PDF is generated, THE Assessment_System SHALL store the file path in the `rapors` table and make the file downloadable.
8. THE Assessment_System SHALL allow regeneration of the PDF for a rapor in `DRAFT` or `FINALIZED` status; regeneration SHALL overwrite the previous file.
9. WHEN a rapor is in `APPROVED` status, THE Assessment_System SHALL allow download of the existing PDF but SHALL NOT regenerate it unless the Kepsek reverts the status.

### Requirement 14: Akses Guru — Input Nilai Mata Pelajaran Sendiri

**User Story:** As a Guru, I want to access grade input only for subjects I teach, so that I can efficiently manage my students' grades without seeing unrelated data.

#### Acceptance Criteria

1. THE Assessment_System SHALL display to a Guru only the subjects and classes associated with their Schedules in the active AcademicYear.
2. THE Assessment_System SHALL allow a Guru to input all grade types (PH1–PH4, TUGAS1–TUGAS4, ATS, SAS) for students in their assigned classes.
3. THE Assessment_System SHALL NOT allow a Guru to view or modify grades for subjects not assigned to their Schedules.
4. WHEN a Guru accesses the grade input page, THE Assessment_System SHALL default to showing the Guru's first active Schedule's class and subject.

### Requirement 15: Akses Siswa/Orang Tua — Lihat Nilai dan Rapor

**User Story:** As a Siswa or parent, I want to view my own grades and download my report card, so that I can monitor my academic performance.

#### Acceptance Criteria

1. THE Assessment_System SHALL display to a Siswa only their own Grade records in the Student panel.
2. THE Assessment_System SHALL allow a Siswa to view their grades grouped by subject, showing all Grade_Types and the calculated Rapor_Score.
3. THE Assessment_System SHALL allow a Siswa to download their rapor PDF only when the Rapor_Status is `APPROVED`.
4. THE Assessment_System SHALL NOT allow a Siswa to modify any grade data.
5. IF the authenticated Siswa has no Student profile linked to their User account, THEN THE Assessment_System SHALL display an informational message indicating no grade data is available.

### Requirement 16: Akses Admin — CRUD Penuh

**User Story:** As an Admin, I want full create, read, update, and delete access to all grade and rapor data, so that I can manage and correct assessment data across all classes and teachers.

#### Acceptance Criteria

1. THE Assessment_System SHALL allow Admin to create, read, update, and delete any Grade record regardless of which teacher is assigned to the subject.
2. THE Assessment_System SHALL allow Admin to manage KKM values for all subjects and levels.
3. THE Assessment_System SHALL allow Admin to generate and download rapor PDFs for any student.
4. THE Assessment_System SHALL allow Admin to change the Rapor_Status of any rapor.
5. THE Assessment_System SHALL display all grade data to Admin with filters for academic year, class, subject, and student.

### Requirement 17: Validasi Data Penilaian

**User Story:** As a developer, I want the system to validate all grade inputs, so that data integrity is maintained and invalid records are prevented.

#### Acceptance Criteria

1. WHEN a Grade record is created or updated, THE Assessment_System SHALL validate that the `score` field is a decimal value between 0 and 100 inclusive.
2. WHEN a Grade record is created or updated, THE Assessment_System SHALL validate that the `grade_type` field is one of the defined Grade_Types: `PH1`, `PH2`, `PH3`, `PH4`, `TUGAS1`, `TUGAS2`, `TUGAS3`, `TUGAS4`, `ATS`, `SAS`, `RAPOR`.
3. WHEN a Grade record is created, THE Assessment_System SHALL validate that the referenced `student_id`, `subject_id`, and `academic_year_id` exist in their respective tables.
4. THE Assessment_System SHALL enforce a unique constraint on `(student_id, subject_id, academic_year_id, grade_type)` so that each student has at most one score per grade type per subject per academic year.
5. WHEN a Grade record is created with a duplicate `(student_id, subject_id, academic_year_id, grade_type)` combination, THE Assessment_System SHALL update the existing record rather than creating a duplicate.
6. WHEN a Personality_Score is created or updated, THE Assessment_System SHALL validate that the value is one of: A, B, C, D.

### Requirement 18: Performa Query Penilaian

**User Story:** As a developer, I want grade queries to be performant, so that the grade input and rapor pages load quickly even with large datasets.

#### Acceptance Criteria

1. THE Assessment_System SHALL eager load related models (Student, Subject, AcademicYear, SchoolClass) when displaying grade lists to prevent N+1 query problems.
2. WHEN loading the grade input page for a class with up to 40 students and 10 subjects, THE Assessment_System SHALL complete the initial data load within 1000 milliseconds.
3. THE Assessment_System SHALL paginate grade list views in the Admin panel with a default page size of 25 records.
4. WHEN generating a rapor PDF, THE Assessment_System SHALL load all required data in a maximum of 5 database queries using eager loading.

### Requirement 19: Testing Modul Penilaian

**User Story:** As a developer, I want comprehensive tests for the assessment module, so that I can ensure correctness and prevent regressions.

#### Acceptance Criteria

1. THE test suite SHALL include feature tests for Guru grade input (PH, Tugas, ATS, SAS) via the Filament resource.
2. THE test suite SHALL include feature tests verifying that Guru cannot access or modify grades for subjects not assigned to their Schedules.
3. THE test suite SHALL include feature tests for Wali_Kelas attitude score and personality score input.
4. THE test suite SHALL include feature tests for rapor finalization and Kepsek approval workflow.
5. THE test suite SHALL include feature tests verifying that Siswa can only view their own grades and download approved rapors.
6. THE test suite SHALL include unit tests for Rapor_Score calculation logic.
7. THE test suite SHALL include unit tests for Grade_Predicate assignment logic.
8. FOR ALL valid sets of Grade records for a student and subject, the Rapor_Score SHALL equal `((avg PH) + (avg TUGAS) + ATS + SAS) / 4` rounded to two decimal places (round-trip property between component grades and computed Rapor_Score).
9. FOR ALL score values between 0 and 100, the Grade_Predicate assignment SHALL be deterministic and consistent with the defined ranges (A: 86–100, B: 73–85, C: 60–72, D: <60).
10. THE test suite SHALL include feature tests for rapor PDF generation and download.
