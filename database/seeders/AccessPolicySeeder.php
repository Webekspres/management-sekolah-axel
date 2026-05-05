<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccessPolicySeeder extends Seeder
{
    /**
     * Idempotent seeder — upserts all access policies.
     * Run this every time a new resource/feature is added.
     */
    public function run(): void
    {
        $policies = $this->policies();

        foreach ($policies as $policy) {
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
     * Single source of truth for all access policies.
     * Add a new entry here whenever a new Filament resource is created.
     *
     * @return array<int, array{id: string, code: string, name: string, description: string, target_model: string, abilities: array<string>, permanent_roles: array<string>}>
     */
    private function policies(): array
    {
        return [
            // ---------------------------------------------------------------
            // Pengumuman
            // ---------------------------------------------------------------
            [
                'id' => (string) Str::ulid(),
                'code' => 'announcement_management',
                'name' => 'Manajemen Pengumuman',
                'description' => 'Akses membuat, mengubah, menghapus, dan melihat seluruh pengumuman lintas role.',
                'target_model' => 'App\\Models\\Announcement',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin', 'kepala_sekolah', 'guru'],
            ],

            // ---------------------------------------------------------------
            // RPP (Lesson Plan)
            // ---------------------------------------------------------------
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

            // ---------------------------------------------------------------
            // KBM (Laporan Kegiatan Belajar Mengajar)
            // ---------------------------------------------------------------
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

            // ---------------------------------------------------------------
            // Data Personalia — Guru
            // ---------------------------------------------------------------
            [
                'id' => (string) Str::ulid(),
                'code' => 'teacher_management',
                'name' => 'Data Personalia Guru',
                'description' => 'Akses melihat, menambah, mengubah, dan menghapus data personalia guru.',
                'target_model' => 'App\\Models\\Teacher',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin'],
            ],

            // ---------------------------------------------------------------
            // Data Personalia — Siswa
            // ---------------------------------------------------------------
            [
                'id' => (string) Str::ulid(),
                'code' => 'student_management',
                'name' => 'Data Personalia Siswa',
                'description' => 'Akses melihat, menambah, mengubah, dan menghapus data personalia siswa.',
                'target_model' => 'App\\Models\\Student',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin'],
            ],

            // ---------------------------------------------------------------
            // Tahun Ajaran
            // ---------------------------------------------------------------
            [
                'id' => (string) Str::ulid(),
                'code' => 'academic_year_management',
                'name' => 'Manajemen Tahun Ajaran',
                'description' => 'Akses melihat, menambah, mengubah, dan menghapus data tahun ajaran.',
                'target_model' => 'App\\Models\\AcademicYear',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin'],
            ],

            // ---------------------------------------------------------------
            // Jadwal Pelajaran
            // ---------------------------------------------------------------
            [
                'id' => (string) Str::ulid(),
                'code' => 'schedule_management',
                'name' => 'Manajemen Jadwal Pelajaran',
                'description' => 'Akses melihat, menambah, mengubah, dan menghapus jadwal pelajaran.',
                'target_model' => 'App\\Models\\Schedule',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin'],
            ],

            // ---------------------------------------------------------------
            // Data Kelas
            // ---------------------------------------------------------------
            [
                'id' => (string) Str::ulid(),
                'code' => 'school_class_management',
                'name' => 'Manajemen Data Kelas',
                'description' => 'Akses melihat, menambah, mengubah, dan menghapus data kelas.',
                'target_model' => 'App\\Models\\SchoolClass',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin'],
            ],

            // ---------------------------------------------------------------
            // Mata Pelajaran
            // ---------------------------------------------------------------
            [
                'id' => (string) Str::ulid(),
                'code' => 'subject_management',
                'name' => 'Manajemen Mata Pelajaran',
                'description' => 'Akses melihat, menambah, mengubah, dan menghapus daftar mata pelajaran.',
                'target_model' => 'App\\Models\\Subject',
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permanent_roles' => ['super_admin'],
            ],

            // ---------------------------------------------------------------
            // Penilaian (Nilai & Rapor)
            // ---------------------------------------------------------------
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
        ];
    }
}
