<?php

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\LessonPlan;
use App\Models\LessonPlanMaterial;
use App\Models\Rapor;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;

// ─────────────────────────────────────────────────────────────────────────────
// createForRaporApproved
// ─────────────────────────────────────────────────────────────────────────────

test('createForRaporApproved membuat notifikasi untuk siswa pemilik rapor', function (): void {
    $rapor = Rapor::factory()->approved()->create();
    $studentUser = $rapor->student->user;

    app(NotificationService::class)->createForRaporApproved($rapor);

    expect($studentUser->notifications()->count())->toBe(1);
});

test('createForRaporApproved judul mengandung nama tahun ajaran', function (): void {
    $academicYear = AcademicYear::factory()->create(['name' => '2024/2025']);
    $rapor = Rapor::factory()->approved()->create(['academic_year_id' => $academicYear->id]);
    $studentUser = $rapor->student->user;

    app(NotificationService::class)->createForRaporApproved($rapor);

    $notification = $studentUser->notifications()->first();
    $data = $notification->data;
    expect($data['title'])->toContain('2024/2025');
});

test('createForRaporApproved pesan berisi teks yang benar', function (): void {
    $rapor = Rapor::factory()->approved()->create();
    $studentUser = $rapor->student->user;

    app(NotificationService::class)->createForRaporApproved($rapor);

    $notification = $studentUser->notifications()->first();
    $data = $notification->data;
    expect($data['body'])->toBe('Rapor Anda telah disetujui. Silakan akses untuk melihat hasil belajar Anda.');
});

test('createForRaporApproved notifikasi dibuat dengan read_at null (belum dibaca)', function (): void {
    $rapor = Rapor::factory()->approved()->create();
    $studentUser = $rapor->student->user;

    app(NotificationService::class)->createForRaporApproved($rapor);

    $notification = $studentUser->notifications()->first();
    expect($notification->read_at)->toBeNull();
});

test('createForRaporApproved log error dan tidak throw jika relasi student null', function (): void {
    Log::shouldReceive('error')->once();

    $rapor = Rapor::factory()->approved()->create();
    $rapor->setRelation('student', null);

    expect(fn () => app(NotificationService::class)->createForRaporApproved($rapor))
        ->not->toThrow(Throwable::class);

    expect(DatabaseNotification::count())->toBe(0);
});

test('createForRaporApproved log error dan tidak throw jika relasi academicYear null', function (): void {
    Log::shouldReceive('error')->once();

    $rapor = Rapor::factory()->approved()->create();
    $rapor->setRelation('academicYear', null);

    expect(fn () => app(NotificationService::class)->createForRaporApproved($rapor))
        ->not->toThrow(Throwable::class);

    expect(DatabaseNotification::count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// createForAnnouncement
// ─────────────────────────────────────────────────────────────────────────────

test('createForAnnouncement membuat notifikasi untuk semua user aktif dengan role yang cocok', function (): void {
    $guru1 = User::factory()->asGuru()->create();
    $guru2 = User::factory()->asGuru()->create();
    $announcement = Announcement::factory()->forGuru()->make();

    app(NotificationService::class)->createForAnnouncement($announcement);

    expect($guru1->notifications()->count())->toBe(1)
        ->and($guru2->notifications()->count())->toBe(1);
});

test('createForAnnouncement tidak membuat notifikasi untuk role yang tidak cocok', function (): void {
    $siswa = User::factory()->asSiswa()->create();
    $announcement = Announcement::factory()->forGuru()->make();

    app(NotificationService::class)->createForAnnouncement($announcement);

    expect($siswa->notifications()->count())->toBe(0);
});

test('createForAnnouncement tidak membuat notifikasi untuk user tidak aktif', function (): void {
    $inactiveGuru = User::factory()->asGuru()->inactive()->create();
    $announcement = Announcement::factory()->forGuru()->make();

    app(NotificationService::class)->createForAnnouncement($announcement);

    expect($inactiveGuru->notifications()->count())->toBe(0);
});

test('createForAnnouncement pesan di-truncate ke 100 karakter dengan titik tiga', function (): void {
    $guru = User::factory()->asGuru()->create();
    $longContent = str_repeat('a', 200);
    $announcement = Announcement::factory()->forGuru()->make(['content' => $longContent]);

    app(NotificationService::class)->createForAnnouncement($announcement);

    $notification = $guru->notifications()->first();
    $data = $notification->data;
    expect(mb_strlen($data['body']))->toBeLessThanOrEqual(103) // 100 chars + "..."
        ->and($data['body'])->toEndWith('...');
});

test('createForAnnouncement tidak throw jika target_role kosong', function (): void {
    $announcement = Announcement::factory()->make(['target_role' => []]);

    expect(fn () => app(NotificationService::class)->createForAnnouncement($announcement))
        ->not->toThrow(Throwable::class);

    expect(DatabaseNotification::count())->toBe(0);
});

test('createForAnnouncement tidak throw jika tidak ada user yang cocok', function (): void {
    User::where('role', 'guru')->delete();
    $announcement = Announcement::factory()->forGuru()->make();

    expect(fn () => app(NotificationService::class)->createForAnnouncement($announcement))
        ->not->toThrow(Throwable::class);

    expect(DatabaseNotification::count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// createForLessonPlanMaterial
// ─────────────────────────────────────────────────────────────────────────────

test('createForLessonPlanMaterial membuat notifikasi untuk semua siswa aktif di kelas RPP', function (): void {
    $schoolClass = SchoolClass::factory()->create();
    $student1 = Student::factory()->create(['class_id' => $schoolClass->id]);
    $student2 = Student::factory()->create(['class_id' => $schoolClass->id]);
    $lessonPlan = LessonPlan::factory()->create(['class_id' => $schoolClass->id]);
    $material = LessonPlanMaterial::factory()->create(['lesson_plan_id' => $lessonPlan->id]);

    // Observer already fired on create; clear and call service directly to test it
    DatabaseNotification::query()->delete();
    app(NotificationService::class)->createForLessonPlanMaterial($material);

    expect($student1->user->notifications()->count())->toBe(1)
        ->and($student2->user->notifications()->count())->toBe(1);
});

test('createForLessonPlanMaterial tidak membuat notifikasi untuk siswa di kelas lain', function (): void {
    $otherClass = SchoolClass::factory()->create();
    $otherStudent = Student::factory()->create(['class_id' => $otherClass->id]);

    $lessonPlan = LessonPlan::factory()->create();
    $material = LessonPlanMaterial::factory()->create(['lesson_plan_id' => $lessonPlan->id]);

    DatabaseNotification::query()->delete();
    app(NotificationService::class)->createForLessonPlanMaterial($material);

    expect($otherStudent->user->notifications()->count())->toBe(0);
});

test('createForLessonPlanMaterial judul mengandung nama file dan nama mapel', function (): void {
    $subject = Subject::factory()->create(['name' => 'Matematika']);
    $schoolClass = SchoolClass::factory()->create();
    $student = Student::factory()->create(['class_id' => $schoolClass->id]);
    $lessonPlan = LessonPlan::factory()->create([
        'class_id' => $schoolClass->id,
        'subject_id' => $subject->id,
    ]);
    $material = LessonPlanMaterial::factory()->create([
        'lesson_plan_id' => $lessonPlan->id,
        'original_filename' => 'modul-bab-1.pdf',
    ]);

    DatabaseNotification::query()->delete();
    app(NotificationService::class)->createForLessonPlanMaterial($material);

    $notification = $student->user->notifications()->first();
    $data = $notification->data;
    expect($data['title'])->toContain('modul-bab-1.pdf')
        ->and($data['title'])->toContain('Matematika');
});

test('createForLessonPlanMaterial pesan mengandung nama kelas dan nama guru', function (): void {
    $guruUser = User::factory()->asGuru()->create(['name' => 'Pak Budi']);
    $teacher = Teacher::factory()->create(['user_id' => $guruUser->id]);
    $schoolClass = SchoolClass::factory()->create(['name' => 'X IPA 1']);
    $student = Student::factory()->create(['class_id' => $schoolClass->id]);
    $lessonPlan = LessonPlan::factory()->create([
        'class_id' => $schoolClass->id,
        'teacher_id' => $teacher->id,
    ]);
    $material = LessonPlanMaterial::factory()->create(['lesson_plan_id' => $lessonPlan->id]);

    DatabaseNotification::query()->delete();
    app(NotificationService::class)->createForLessonPlanMaterial($material);

    $notification = $student->user->notifications()->first();
    $data = $notification->data;
    expect($data['body'])->toContain('X IPA 1')
        ->and($data['body'])->toContain('Pak Budi');
});

test('createForLessonPlanMaterial log error dan tidak throw jika relasi lessonPlan null', function (): void {
    Log::shouldReceive('error')->once();

    $material = LessonPlanMaterial::factory()->create();
    DatabaseNotification::query()->delete();
    $material->setRelation('lessonPlan', null);

    expect(fn () => app(NotificationService::class)->createForLessonPlanMaterial($material))
        ->not->toThrow(Throwable::class);

    expect(DatabaseNotification::count())->toBe(0);
});

test('createForLessonPlanMaterial tidak throw jika tidak ada siswa di kelas', function (): void {
    $schoolClass = SchoolClass::factory()->create();
    // No students in this class
    $lessonPlan = LessonPlan::factory()->create(['class_id' => $schoolClass->id]);
    $material = LessonPlanMaterial::factory()->create(['lesson_plan_id' => $lessonPlan->id]);

    expect(fn () => app(NotificationService::class)->createForLessonPlanMaterial($material))
        ->not->toThrow(Throwable::class);

    expect(DatabaseNotification::count())->toBe(0);
});
