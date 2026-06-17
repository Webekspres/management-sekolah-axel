<?php

/**
 * Access Control Tests — Property 10: Akses kontrol berdasarkan role
 *
 * Memverifikasi bahwa:
 * - Siswa mendapat 403 saat akses halaman RPP di panel Guru dan Kepsek
 * - Ortu mendapat 403 saat akses halaman RPP Guru, Kepsek, dan halaman materi siswa
 * - Guru hanya melihat RPP miliknya sendiri
 *
 * **Validates: Requirements 7.1, 7.2, 7.3, 7.4**
 */

// Feature: rpp-materi-upload, Property 10: Akses kontrol berdasarkan role

use App\Filament\Guru\Resources\LessonPlans\LessonPlanResource as GuruLessonPlanResource;
use App\Models\LessonPlan;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Filament\Facades\Filament;

// ─────────────────────────────────────────────────────────────────────────────
// Test 10.1 — Siswa mendapat 403 saat akses halaman RPP di panel Guru
// Validates: Requirement 7.2
// ─────────────────────────────────────────────────────────────────────────────

test('siswa mendapat 403 saat akses halaman RPP di panel Guru', function () {
    $siswaUser = User::factory()->asSiswa()->create();

    $this->actingAs($siswaUser)
        ->get('/guru/lesson-plans')
        ->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 10.2 — Siswa mendapat 403 saat akses halaman RPP di panel Kepsek
// Validates: Requirement 7.2
// ─────────────────────────────────────────────────────────────────────────────

test('siswa mendapat 403 saat akses halaman RPP di panel Kepsek', function () {
    $siswaUser = User::factory()->asSiswa()->create();

    $this->actingAs($siswaUser)
        ->get('/kepsek/lesson-plans')
        ->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 10.3 — Ortu mendapat 403 saat akses halaman RPP di panel Guru
// Validates: Requirement 7.3
// ─────────────────────────────────────────────────────────────────────────────

test('ortu mendapat 403 saat akses halaman RPP di panel Guru', function () {
    // Ortu: siswa_ortu role tanpa profil student
    $ortuUser = User::factory()->asSiswa()->create();
    // Tidak membuat Student profile — ini yang membedakan ortu dari siswa

    $this->actingAs($ortuUser)
        ->get('/guru/lesson-plans')
        ->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 10.4 — Ortu mendapat 403 saat akses halaman RPP di panel Kepsek
// Validates: Requirement 7.3
// ─────────────────────────────────────────────────────────────────────────────

test('ortu mendapat 403 saat akses halaman RPP di panel Kepsek', function () {
    // Ortu: siswa_ortu role tanpa profil student
    $ortuUser = User::factory()->asSiswa()->create();

    $this->actingAs($ortuUser)
        ->get('/kepsek/lesson-plans')
        ->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 10.5 — Ortu mendapat 403 saat akses halaman materi siswa di panel Student
// Validates: Requirement 7.3, 6.3
// ─────────────────────────────────────────────────────────────────────────────

test('ortu mendapat 403 saat akses halaman materi siswa di panel Student', function () {
    // Ortu: siswa_ortu role tanpa profil student
    $ortuUser = User::factory()->asSiswa()->create();
    // Tidak membuat Student profile — ortu tidak memiliki profil siswa

    $this->actingAs($ortuUser)
        ->get('/student/lesson-plan-materials')
        ->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 10.6 — Siswa dengan profil dapat mengakses halaman materi siswa
// Validates: Requirement 6.3 (positive case)
// ─────────────────────────────────────────────────────────────────────────────

test('siswa dengan profil dapat mengakses halaman materi siswa', function () {
    $student = Student::factory()->create();
    $siswaUser = $student->user;

    $this->actingAs($siswaUser)
        ->get('/student/lesson-plan-materials')
        ->assertOk();
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 10.7 — Guru hanya melihat RPP miliknya sendiri (property test 100 iterasi)
// Validates: Requirement 7.4
// ─────────────────────────────────────────────────────────────────────────────

test('guru hanya melihat RPP miliknya sendiri — tidak ada RPP guru lain yang muncul', function () {
    // Feature: rpp-materi-upload, Property 10: Akses kontrol berdasarkan role
    Filament::setCurrentPanel(Filament::getPanel('guru'));

    for ($i = 0; $i < propertyIterationCount(); $i++) {
        // Buat dua guru berbeda
        $guruUser = User::factory()->asGuru()->create();
        $teacher = Teacher::factory()->create(['user_id' => $guruUser->id]);

        $otherTeacher = Teacher::factory()->create();

        // Buat RPP untuk guru yang sedang login
        $ownLessonPlan = LessonPlan::factory()->create([
            'teacher_id' => $teacher->id,
            'status' => fake()->randomElement(['DRAFT', 'PENDING', 'REVISED', 'APPROVED']),
        ]);

        // Buat RPP untuk guru lain (jumlah acak 1–3)
        $otherCount = fake()->numberBetween(1, 3);
        LessonPlan::factory()->count($otherCount)->create([
            'teacher_id' => $otherTeacher->id,
        ]);

        $this->actingAs($guruUser);

        // Verifikasi via getEloquentQuery: hanya RPP milik guru ini yang muncul
        $query = GuruLessonPlanResource::getEloquentQuery();
        $ids = $query->pluck('id')->all();

        expect($ids)->toContain($ownLessonPlan->id);

        // Tidak ada RPP guru lain yang muncul
        $otherIds = LessonPlan::where('teacher_id', $otherTeacher->id)->pluck('id')->all();
        foreach ($otherIds as $otherId) {
            expect($ids)->not->toContain($otherId);
        }
    }
});
