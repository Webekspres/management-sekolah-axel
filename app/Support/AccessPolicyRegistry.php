<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Single source of truth for access_policies rows.
 * Synced via migration (deploy /release) and AccessPolicySeeder.
 */
class AccessPolicyRegistry
{
    /**
     * Upsert all access policies by code (idempotent).
     */
    public static function sync(): void
    {
        foreach (self::definitions() as $policy) {
            DB::table('access_policies')->upsert(
                [
                    array_merge($policy, [
                        'abilities' => json_encode($policy['abilities'], JSON_THROW_ON_ERROR),
                        'permanent_roles' => json_encode($policy['permanent_roles'], JSON_THROW_ON_ERROR),
                        'is_active' => true,
                        'created_at' => now(),
                    ]),
                ],
                ['code'],
                ['name', 'description', 'target_model', 'abilities', 'permanent_roles', 'is_active'],
            );
        }
    }

    /**
     * @return array<int, array{id: string, code: string, name: string, description: string, target_model: string, abilities: array<string>, permanent_roles: array<string>}>
     */
    public static function definitions(): array
    {
        return [
            [
                'id' => (string) Str::ulid(),
                'code' => 'announcement_management',
                'name' => 'Manajemen Pengumuman',
                'description' => 'Akses membuat, mengubah, menghapus, dan melihat seluruh pengumuman lintas role.',
                'target_model' => 'App\\Models\\Announcement',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin', 'kepala_sekolah', 'guru'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'lesson_plan_management',
                'name' => 'Manajemen RPP',
                'description' => 'Akses kelola data RPP termasuk melihat, membuat, mengubah, dan menghapus.',
                'target_model' => 'App\\Models\\LessonPlan',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin', 'kepala_sekolah', 'guru'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'lesson_plan_review',
                'name' => 'Review RPP Guru Lain',
                'description' => 'Akses melihat dan mereview RPP milik guru lain (bukan milik sendiri).',
                'target_model' => 'App\\Models\\LessonPlan',
                'abilities' => ['viewAny', 'view', 'update'],
                'permanent_roles' => ['super_admin', 'kepala_sekolah'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'kbm_management',
                'name' => 'Manajemen Laporan KBM',
                'description' => 'Akses kelola data laporan KBM termasuk melihat, membuat, mengubah, dan menghapus.',
                'target_model' => 'App\\Models\\Kbm',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin', 'kepala_sekolah', 'guru'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'kbm_review',
                'name' => 'Review Laporan KBM Guru Lain',
                'description' => 'Akses melihat dan mereview laporan KBM milik guru lain (bukan milik sendiri).',
                'target_model' => 'App\\Models\\Kbm',
                'abilities' => ['viewAny', 'view', 'update'],
                'permanent_roles' => ['super_admin', 'kepala_sekolah'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'teacher_management',
                'name' => 'Data Personalia Guru',
                'description' => 'Akses melihat, menambah, mengubah, dan menghapus data personalia guru.',
                'target_model' => 'App\\Models\\Teacher',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'student_management',
                'name' => 'Data Personalia Siswa',
                'description' => 'Akses melihat, menambah, mengubah, dan menghapus data personalia siswa.',
                'target_model' => 'App\\Models\\Student',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'academic_year_management',
                'name' => 'Manajemen Tahun Ajaran',
                'description' => 'Akses melihat, menambah, mengubah, dan menghapus data tahun ajaran.',
                'target_model' => 'App\\Models\\AcademicYear',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'schedule_management',
                'name' => 'Manajemen Jadwal Pelajaran',
                'description' => 'Akses melihat, menambah, mengubah, dan menghapus jadwal pelajaran.',
                'target_model' => 'App\\Models\\Schedule',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'school_class_management',
                'name' => 'Manajemen Data Kelas',
                'description' => 'Akses melihat, menambah, mengubah, dan menghapus data kelas.',
                'target_model' => 'App\\Models\\SchoolClass',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'subject_management',
                'name' => 'Manajemen Mata Pelajaran',
                'description' => 'Akses melihat, menambah, mengubah, dan menghapus daftar mata pelajaran.',
                'target_model' => 'App\\Models\\Subject',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'grade_management',
                'name' => 'Manajemen Nilai',
                'description' => 'Akses input dan mengelola nilai siswa (PH, Tugas, ATS, SAS) per jadwal mata pelajaran.',
                'target_model' => 'App\\Models\\Grade',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin', 'guru'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'rapor_management',
                'name' => 'Manajemen Rapor',
                'description' => 'Akses melihat, finalisasi, approve/reject, dan generate PDF rapor siswa.',
                'target_model' => 'App\\Models\\Rapor',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin', 'kepala_sekolah', 'guru'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'attendance_management',
                'name' => 'Manajemen Absensi',
                'description' => 'Akses melihat, menambah, mengubah, dan menghapus data absensi siswa.',
                'target_model' => 'App\\Models\\Attendance',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin', 'guru'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'attendance_review',
                'name' => 'Review Data Absensi',
                'description' => 'Akses melihat data absensi lintas guru (read-only di panel Kepsek).',
                'target_model' => 'App\\Models\\Attendance',
                'abilities' => ['viewAny', 'view'],
                'permanent_roles' => ['kepala_sekolah'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'attitude_score_management',
                'name' => 'Manajemen Nilai Sikap',
                'description' => 'Akses input dan mengelola nilai sikap siswa (wali kelas).',
                'target_model' => 'App\\Models\\AttitudeScore',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin', 'kepala_sekolah', 'guru'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'knowledge_skill_score_management',
                'name' => 'Manajemen Nilai Pengetahuan & Keterampilan',
                'description' => 'Akses input dan mengelola nilai pengetahuan serta keterampilan per mata pelajaran.',
                'target_model' => 'App\\Models\\KnowledgeSkillScore',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin', 'kepala_sekolah', 'guru'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'learning_achievement_management',
                'name' => 'Manajemen Capaian Pembelajaran',
                'description' => 'Akses input dan mengelola capaian pembelajaran siswa per mata pelajaran.',
                'target_model' => 'App\\Models\\LearningAchievement',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin', 'kepala_sekolah', 'guru'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'personality_score_management',
                'name' => 'Manajemen Nilai Kepribadian',
                'description' => 'Akses input dan mengelola nilai kepribadian siswa (wali kelas).',
                'target_model' => 'App\\Models\\PersonalityScore',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin', 'kepala_sekolah', 'guru'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'subject_kkm_management',
                'name' => 'Manajemen KKM Mata Pelajaran',
                'description' => 'Akses melihat dan mengatur KKM (Kriteria Ketuntasan Minimal) per mata pelajaran.',
                'target_model' => 'App\\Models\\SubjectKkm',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin', 'kepala_sekolah', 'guru'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'staff_management',
                'name' => 'Data Admin & Kepala Sekolah',
                'description' => 'Akses melihat dan mengelola akun admin serta kepala sekolah.',
                'target_model' => 'App\\Models\\User',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin', 'kepala_sekolah'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'invoice_management',
                'name' => 'Manajemen Tagihan SPP',
                'description' => 'Akses melihat, membuat, mengubah, dan menghapus tagihan SPP siswa.',
                'target_model' => 'App\\Models\\Invoice',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete', 'recordManual'],
                'permanent_roles' => ['super_admin'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'payment_management',
                'name' => 'Manajemen Pembayaran SPP',
                'description' => 'Akses melihat dan mengelola data pembayaran tagihan SPP.',
                'target_model' => 'App\\Models\\Payment',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete', 'verify', 'reject'],
                'permanent_roles' => ['super_admin'],
            ],
            [
                'id' => (string) Str::ulid(),
                'code' => 'activity_log_management',
                'name' => 'Activity Log',
                'description' => 'Akses melihat log aktivitas sistem (audit trail).',
                'target_model' => 'App\\Models\\ActivityLog',
                'abilities' => ['viewAny', 'view'],
                'permanent_roles' => ['super_admin'],
            ],
        ];
    }

    /**
     * Policy codes introduced after the initial access_policies migration.
     *
     * @return list<string>
     */
    public static function codesAddedAfterInitialRelease(): array
    {
        return [
            'teacher_management',
            'student_management',
            'academic_year_management',
            'schedule_management',
            'school_class_management',
            'subject_management',
            'grade_management',
            'rapor_management',
            'attendance_management',
            'attendance_review',
            'attitude_score_management',
            'knowledge_skill_score_management',
            'learning_achievement_management',
            'personality_score_management',
            'subject_kkm_management',
            'staff_management',
            'invoice_management',
            'payment_management',
            'activity_log_management',
        ];
    }
}
