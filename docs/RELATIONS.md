# Project Relation Map

Dokumen ini merangkum relasi model utama pada aplikasi `axel-manajemen-sekolah`, termasuk catatan penting yang sering jadi sumber bug.

## Core Identity

- `User` hasOne `Teacher` (`teachers.user_id`)
- `User` hasOne `Student` (`students.user_id`)
- `User` hasMany `ActivityLog` (`activity_logs.user_id`)
- `User` hasMany `Notification` (`notifications.user_id`)

## Academic Structure

- `Level` hasMany `Subject` (`subjects.level_id`)
- `Level` hasMany `SchoolClass` (`classes.level_id`)
- `AcademicYear` hasMany `SchoolClass` (`classes.academic_year_id`)
- `SchoolClass` belongsTo `Teacher` as homeroom (`classes.teacher_id`)

## Teaching & Planning

- `Schedule` belongsTo `SchoolClass` (`schedules.class_id`)
- `Schedule` belongsTo `Teacher` (`schedules.teacher_id`)
- `Schedule` belongsTo `Subject` (`schedules.subject_id`)
- `LessonPlan` belongsTo `Teacher` (`lesson_plans.teacher_id`)
- `LessonPlan` belongsTo `SchoolClass` (`lesson_plans.class_id`)
- `LessonPlan` belongsTo `Subject` (`lesson_plans.subject_id`)
- `Kbm` belongsTo `Schedule` (`kbms.schedule_id`)
- `Kbm` belongsTo `LessonPlan` (`kbms.lesson_plan_id`)
- `Attendance` belongsTo `Kbm` (`attendances.kbm_id`)
- `Attendance` belongsTo `Student` (`attendances.student_id`)

## Academic Outcomes

- `Grade` belongsTo `Student` (`grades.student_id`)
- `Grade` belongsTo `Subject` (`grades.subject_id`)
- `Grade` belongsTo `AcademicYear` (`grades.academic_year_id`)
- `Rapor` belongsTo `Student` (`rapors.student_id`)
- `Rapor` belongsTo `AcademicYear` (`rapors.academic_year_id`)

## Financial

- `Invoice` belongsTo `Student` (`invoices.student_id`)
- `Invoice` belongsTo `AcademicYear` (`invoices.academic_year_id`)
- `Payment` belongsTo `Invoice` (`payments.invoice_id`)

## Location

- `User` belongsTo `Address` (`users.address_id`)
- `User` belongsTo `City` (`users.city_id`)
- `Address` belongsTo `Village` / `SubDistrict` / `City` / `Province` (sesuai FK di tabel address dan region)

## Important Scope Rules

Model berikut memakai global scope `active_academic_level_id` dari session:

- `Subject` via trait `BelongsToAcademicLevel`
- `SchoolClass` via trait `BelongsToAcademicLevel`
- `Schedule` / `LessonPlan` via trait `HasClassWithAcademicLevel`
- `Kbm` via trait `HasScheduleWithAcademicLevel`

Artinya query standar bisa otomatis terfilter level aktif.

## Important Display Rule (Subject in RPP/KBM)

Untuk mencegah nama mata pelajaran kosong di tampilan RPP/KBM saat data lama lintas level:

- `LessonPlan::subjectForDisplay()` -> `belongsTo(Subject::class)->withoutGlobalScopes()`
- `Schedule::subjectForDisplay()` -> `belongsTo(Subject::class)->withoutGlobalScopes()`

Gunakan relasi `subjectForDisplay` untuk **display/read** di tabel/detail.
Gunakan relasi `subject` untuk **selection/input** agar tetap mengikuti filtering level aktif.

## Workflow Status (RPP/KBM)

- `DRAFT` -> `PENDING` (submit guru)
- `PENDING` -> `REVISED` (catatan revisi)
- `PENDING` -> `APPROVED`

Role matrix yang berlaku:

- `super_admin`: CRUD
- `kepala_sekolah`: Read + Update status/catatan
- `guru`: CRUD terbatas status (tidak bisa delete jika `APPROVED`)
- `siswa_ortu`: tidak bisa akses resource RPP/KBM
