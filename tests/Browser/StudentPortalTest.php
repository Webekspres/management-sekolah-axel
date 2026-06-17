<?php

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\KnowledgeSkillScore;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;

test('student without profile sees helpful message on grades page', function (): void {
    $user = User::factory()->asSiswa()->create();

    visitAuthenticated($user, '/student/nilai-saya')
        ->assertSee('Profil siswa tidak ditemukan')
        ->assertNoSmoke();
});

test('student sees empty state when no grades exist', function (): void {
    $user = User::factory()->asSiswa()->create();
    Student::factory()->create(['user_id' => $user->id]);
    AcademicYear::factory()->active()->create();

    visitAuthenticated($user, '/student/nilai-saya')
        ->assertSee('Belum ada nilai')
        ->assertNoSmoke();
});

test('student sees grades table when grades exist', function (): void {
    $user = User::factory()->asSiswa()->create();
    $student = Student::factory()->create(['user_id' => $user->id]);
    $academicYear = AcademicYear::factory()->active()->create();
    $subject = Subject::factory()->create();

    Grade::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'academic_year_id' => $academicYear->id,
        'grade_type' => 'PH1',
        'score' => 88.00,
    ]);

    KnowledgeSkillScore::factory()->create([
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'academic_year_id' => $academicYear->id,
    ]);

    visitAuthenticated($user, '/student/nilai-saya')
        ->assertSee($subject->name)
        ->assertNoSmoke();
});

test('student portal key pages load without javascript errors', function (): void {
    $user = User::factory()->asSiswa()->create();
    Student::factory()->create(['user_id' => $user->id]);
    AcademicYear::factory()->active()->create();

    smokeAuthenticatedPages($user, [
        '/student',
        '/student/nilai-saya',
        '/student/rapor-saya',
        '/student/attendances',
        '/student/announcements/pengumuman',
    ]);
});
