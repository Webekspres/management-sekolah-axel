# Requirements Document: Modul Kehadiran (Attendance)

## Introduction

Modul Kehadiran mengimplementasikan pencatatan dan rekap absensi harian siswa pada aplikasi manajemen sekolah. Absensi dicatat per sesi KBM (Kegiatan Belajar Mengajar) sehingga setiap record kehadiran terikat langsung pada KBM yang sudah ada. Guru dapat menginput absensi saat atau setelah membuat laporan KBM, Admin memiliki akses penuh, Kepala Sekolah dapat melihat seluruh data, dan Siswa dapat melihat rekap kehadiran dirinya sendiri.

Tabel `attendances` sudah tersedia di database dengan kolom: `id` (ULID), `kbm_id`, `student_id`, `status` (HADIR/SAKIT/IZIN/ALPA), dan unique constraint pada `(kbm_id, student_id)`.

## Glossary

- **Attendance_System**: Sistem pencatatan dan rekap kehadiran siswa
- **Attendance**: Record kehadiran satu siswa pada satu sesi KBM
- **KBM**: Kegiatan Belajar Mengajar — sesi pembelajaran yang menjadi konteks absensi
- **SchoolClass**: Kelas sekolah yang memiliki daftar siswa dan jadwal pelajaran
- **Schedule**: Jadwal pelajaran yang menghubungkan kelas, mata pelajaran, dan guru
- **Student**: Siswa yang kehadirannya dicatat
- **Teacher**: Guru yang mengajar dan menginput absensi
- **Admin**: Pengguna dengan role `super_admin` yang memiliki akses penuh
- **Guru**: Pengguna dengan role `guru` yang dapat menginput dan melihat absensi kelas yang diajarnya
- **Kepsek**: Pengguna dengan role `kepala_sekolah` yang hanya dapat melihat data absensi
- **Siswa**: Pengguna dengan role `siswa_ortu` yang hanya dapat melihat absensi dirinya sendiri
- **Attendance_Status**: Nilai enum status kehadiran: HADIR, SAKIT, IZIN, ALPA
- **Bulk_Input**: Fitur input absensi seluruh siswa dalam satu kelas sekaligus untuk satu KBM
- **Attendance_Summary**: Rekap statistik kehadiran per siswa, per kelas, atau per periode

## Requirements

### Requirement 1: Input Absensi per KBM oleh Guru

**User Story:** As a Guru, I want to input attendance for all students in a class for a specific KBM session, so that I can record which students were present, sick, excused, or absent.

#### Acceptance Criteria

1. WHEN a Guru accesses the attendance input page for a KBM, THE Attendance_System SHALL display all students enrolled in the SchoolClass associated with that KBM's Schedule.
2. WHEN a Guru submits attendance for a KBM, THE Attendance_System SHALL create or update one Attendance record per student with the selected Attendance_Status.
3. THE Attendance_System SHALL enforce the unique constraint on `(kbm_id, student_id)` so that each student has at most one Attendance record per KBM.
4. WHEN a Guru submits attendance for a KBM that already has attendance records, THE Attendance_System SHALL update the existing records rather than creating duplicates.
5. THE Attendance_System SHALL restrict Guru access so that a Guru can only input attendance for KBM sessions belonging to Schedules where the Guru is the assigned teacher.
6. WHEN a KBM has no students enrolled in its associated SchoolClass, THE Attendance_System SHALL display an informational message indicating no students are available.

### Requirement 2: Bulk Input Absensi Satu Kelas

**User Story:** As a Guru, I want to input attendance for an entire class at once using a bulk action, so that I can efficiently record attendance without editing each student individually.

#### Acceptance Criteria

1. WHEN a Guru selects a KBM and triggers the bulk attendance input action, THE Attendance_System SHALL present a form listing all students in the associated SchoolClass with a status selector for each student.
2. THE Attendance_System SHALL pre-populate existing Attendance_Status values for students who already have attendance records for the selected KBM.
3. WHEN a Guru submits the bulk attendance form, THE Attendance_System SHALL save all Attendance records in a single database transaction.
4. IF the bulk attendance transaction fails, THEN THE Attendance_System SHALL roll back all changes and display an error notification to the Guru.
5. THE Attendance_System SHALL display a success notification showing the count of attendance records saved after a successful bulk input.

### Requirement 3: CRUD Absensi oleh Admin

**User Story:** As an Admin, I want full create, read, update, and delete access to all attendance records, so that I can manage and correct attendance data across all classes and teachers.

#### Acceptance Criteria

1. THE Attendance_System SHALL allow Admin to create individual Attendance records by selecting a KBM, a Student, and an Attendance_Status.
2. THE Attendance_System SHALL allow Admin to update the Attendance_Status of any existing Attendance record.
3. THE Attendance_System SHALL allow Admin to delete any Attendance record.
4. THE Attendance_System SHALL display all Attendance records to Admin with filters for date range, SchoolClass, and Attendance_Status.
5. WHEN Admin creates an Attendance record with a duplicate `(kbm_id, student_id)` combination, THE Attendance_System SHALL display a validation error message.
6. THE Attendance_System SHALL allow Admin to perform bulk attendance input for any KBM, regardless of which teacher is assigned to the Schedule.

### Requirement 4: Integrasi Absensi dengan KBM

**User Story:** As a Guru, I want to input attendance directly from the KBM form or list, so that I can record attendance as part of my teaching activity workflow without navigating to a separate module.

#### Acceptance Criteria

1. WHEN a Guru views the KBM list, THE Attendance_System SHALL display an "Input Absensi" action button for each KBM record.
2. WHEN a Guru clicks the "Input Absensi" action on a KBM, THE Attendance_System SHALL open the bulk attendance input form pre-loaded with the students of the associated SchoolClass.
3. THE Attendance_System SHALL display the current attendance completion status (e.g., "15/30 siswa diabsen") on the KBM list for each KBM that belongs to the authenticated Guru's schedules.
4. WHEN all students in a KBM's SchoolClass have an Attendance record, THE Attendance_System SHALL display a visual indicator (badge or icon) showing attendance is complete for that KBM.

### Requirement 5: Rekap Kehadiran per Siswa

**User Story:** As an Admin or Kepsek, I want to view an attendance summary for each student, so that I can monitor individual student attendance rates and identify students with low attendance.

#### Acceptance Criteria

1. THE Attendance_System SHALL provide a summary view showing, for each Student: total KBM sessions, count of HADIR, SAKIT, IZIN, ALPA, and attendance percentage.
2. THE Attendance_System SHALL allow filtering the summary by SchoolClass, date range, and academic year.
3. WHEN a Student's attendance percentage falls below 75%, THE Attendance_System SHALL visually highlight the student's row with a warning color.
4. THE Attendance_System SHALL calculate attendance percentage as: (HADIR count / total KBM sessions) × 100, rounded to one decimal place.
5. THE Attendance_System SHALL allow Admin to export the attendance summary to a downloadable format.

### Requirement 6: Rekap Kehadiran per Kelas

**User Story:** As an Admin, Guru, or Kepsek, I want to view attendance statistics for an entire class, so that I can assess overall class attendance and identify patterns.

#### Acceptance Criteria

1. THE Attendance_System SHALL provide a class-level summary showing total sessions, average attendance rate, and per-status counts for a selected SchoolClass.
2. THE Attendance_System SHALL allow filtering the class summary by date range.
3. WHEN a Guru accesses the class attendance summary, THE Attendance_System SHALL restrict the view to only SchoolClasses associated with the Guru's Schedules.
4. THE Attendance_System SHALL display the class summary with a breakdown per student, showing each student's HADIR, SAKIT, IZIN, and ALPA counts.

### Requirement 7: Rekap Kehadiran per Periode

**User Story:** As an Admin or Kepsek, I want to view attendance data filtered by a specific date range or academic period, so that I can generate periodic attendance reports.

#### Acceptance Criteria

1. THE Attendance_System SHALL allow filtering all attendance views by a start date and end date range.
2. THE Attendance_System SHALL allow filtering attendance data by academic year (via the active `AcademicYear`).
3. WHEN a date range filter is applied, THE Attendance_System SHALL include only Attendance records whose associated KBM date falls within the specified range.
4. THE Attendance_System SHALL display the applied filter criteria alongside the attendance data.

### Requirement 8: Tampilan Absensi untuk Siswa

**User Story:** As a Siswa, I want to view my own attendance records, so that I can monitor my attendance status and identify any discrepancies.

#### Acceptance Criteria

1. THE Attendance_System SHALL display only the Attendance records belonging to the authenticated Siswa's Student profile in the Student panel.
2. THE Attendance_System SHALL show each attendance record with the KBM date, subject name, class name, and Attendance_Status.
3. THE Attendance_System SHALL display a summary at the top of the page showing the Siswa's total HADIR, SAKIT, IZIN, ALPA counts and attendance percentage.
4. THE Attendance_System SHALL allow the Siswa to filter their attendance records by date range and Attendance_Status.
5. IF the authenticated Siswa has no Student profile linked to their User account, THEN THE Attendance_System SHALL display an informational message indicating no attendance data is available.

### Requirement 9: Akses Kepsek (View Only)

**User Story:** As a Kepsek, I want to view all attendance data across all classes and teachers, so that I can monitor school-wide attendance without being able to modify records.

#### Acceptance Criteria

1. THE Attendance_System SHALL display all Attendance records to Kepsek with filters for date range, SchoolClass, and Attendance_Status.
2. THE Attendance_System SHALL NOT allow Kepsek to create, update, or delete Attendance records.
3. THE Attendance_System SHALL provide Kepsek access to the per-student and per-class attendance summaries.
4. THE Attendance_System SHALL display the teacher's name alongside each KBM's attendance data in the Kepsek view.

### Requirement 10: Validasi Data Absensi

**User Story:** As a developer, I want the system to validate all attendance inputs, so that data integrity is maintained and invalid records are prevented.

#### Acceptance Criteria

1. WHEN an Attendance record is created or updated, THE Attendance_System SHALL validate that the `status` field is one of: HADIR, SAKIT, IZIN, ALPA.
2. WHEN an Attendance record is created, THE Attendance_System SHALL validate that the referenced `kbm_id` exists in the `kbms` table.
3. WHEN an Attendance record is created, THE Attendance_System SHALL validate that the referenced `student_id` exists in the `students` table.
4. WHEN an Attendance record is created with a `(kbm_id, student_id)` combination that already exists, THE Attendance_System SHALL return a validation error rather than creating a duplicate.
5. THE Attendance_System SHALL validate that the Student belongs to the SchoolClass associated with the KBM's Schedule before saving an Attendance record.

### Requirement 11: Otorisasi Akses Panel

**User Story:** As a developer, I want each panel to enforce role-based access to attendance features, so that users can only perform actions permitted by their role.

#### Acceptance Criteria

1. THE Attendance_System SHALL allow Admin to perform all CRUD operations on Attendance records in the Admin panel.
2. THE Attendance_System SHALL allow Guru to create and update Attendance records only for KBM sessions assigned to their Schedules in the Guru panel.
3. THE Attendance_System SHALL NOT allow Guru to delete Attendance records.
4. THE Attendance_System SHALL allow Kepsek to view Attendance records and summaries without create, update, or delete permissions in the Kepsek panel.
5. THE Attendance_System SHALL allow Siswa to view only their own Attendance records in the Student panel.
6. WHEN an unauthenticated user attempts to access any attendance page, THE Attendance_System SHALL redirect the user to the login page.

### Requirement 12: Performa Query Absensi

**User Story:** As a developer, I want attendance queries to be performant, so that the attendance pages load quickly even with large datasets.

#### Acceptance Criteria

1. THE Attendance_System SHALL eager load related models (Student, KBM, Schedule, SchoolClass, Subject) when displaying attendance lists to prevent N+1 query problems.
2. THE Attendance_System SHALL use the existing database indexes on `kbm_id` and `student_id` columns in the `attendances` table for all filtered queries.
3. WHEN loading the attendance summary for a class with up to 40 students and 200 KBM sessions, THE Attendance_System SHALL complete the query within 500 milliseconds.
4. THE Attendance_System SHALL paginate attendance list views with a default page size of 25 records.

### Requirement 13: Testing Modul Kehadiran

**User Story:** As a developer, I want comprehensive tests for the attendance module, so that I can ensure correctness and prevent regressions.

#### Acceptance Criteria

1. THE test suite SHALL include feature tests for Guru bulk attendance input via the KBM integration action.
2. THE test suite SHALL include feature tests verifying that Guru cannot access or modify attendance for KBM sessions not assigned to their Schedules.
3. THE test suite SHALL include feature tests for Admin CRUD operations on Attendance records.
4. THE test suite SHALL include feature tests verifying that Kepsek can view but not modify attendance records.
5. THE test suite SHALL include feature tests verifying that Siswa can only view their own attendance records.
6. THE test suite SHALL include unit tests for attendance percentage calculation logic.
7. THE test suite SHALL verify that the unique constraint on `(kbm_id, student_id)` is enforced at the application level.
8. FOR ALL valid sets of Attendance records for a student, the attendance percentage SHALL equal (HADIR count / total count) × 100 rounded to one decimal place (round-trip property between raw counts and computed percentage).
