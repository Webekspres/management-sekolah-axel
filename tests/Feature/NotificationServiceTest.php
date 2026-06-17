<?php

use App\Models\Kbm;
use App\Models\LessonPlan;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;

// ─────────────────────────────────────────────────────────────────────────────
// createForLessonPlanPending
// ─────────────────────────────────────────────────────────────────────────────

test('createForLessonPlanPending membuat notifikasi untuk setiap kepsek aktif', function (): void {
    $kepsek1 = User::factory()->asKepalaSekolah()->create();
    $kepsek2 = User::factory()->asKepalaSekolah()->create();

    $lessonPlan = LessonPlan::factory()->create(['status' => 'PENDING']);

    app(NotificationService::class)->createForLessonPlanPending($lessonPlan);

    expect($kepsek1->notifications()->count())->toBe(1)
        ->and($kepsek2->notifications()->count())->toBe(1);
});

test('createForLessonPlanPending judul mengandung nama guru dan nama mapel', function (): void {
    $kepsek = User::factory()->asKepalaSekolah()->create();
    $guruUser = User::factory()->asGuru()->create(['name' => 'Budi Santoso']);
    $teacher = Teacher::factory()->create(['user_id' => $guruUser->id]);
    $subject = Subject::factory()->create(['name' => 'Matematika']);
    $lessonPlan = LessonPlan::factory()->create([
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
        'status' => 'PENDING',
    ]);

    app(NotificationService::class)->createForLessonPlanPending($lessonPlan);

    $notification = $kepsek->notifications()->first();
    $data = $notification->data;
    expect($data['title'])->toContain('Budi Santoso')
        ->and($data['title'])->toContain('Matematika');
});

test('createForLessonPlanPending pesan mengandung nama kelas dan tanggal implementasi', function (): void {
    $kepsek = User::factory()->asKepalaSekolah()->create();
    $schoolClass = SchoolClass::factory()->create(['name' => 'X IPA 1']);
    $lessonPlan = LessonPlan::factory()->create([
        'class_id' => $schoolClass->id,
        'implementation_date' => '2024-07-15',
        'status' => 'PENDING',
    ]);

    app(NotificationService::class)->createForLessonPlanPending($lessonPlan);

    $notification = $kepsek->notifications()->first();
    $data = $notification->data;
    expect($data['body'])->toContain('X IPA 1')
        ->and($data['body'])->toContain('15');
});

test('createForLessonPlanPending tidak membuat notifikasi jika tidak ada kepsek aktif', function (): void {
    // Pastikan tidak ada kepsek aktif
    User::where('role', 'kepala_sekolah')->delete();

    $lessonPlan = LessonPlan::factory()->create(['status' => 'PENDING']);

    expect(fn () => app(NotificationService::class)->createForLessonPlanPending($lessonPlan))
        ->not->toThrow(Throwable::class);

    expect(DatabaseNotification::count())->toBe(0);
});

test('createForLessonPlanPending tidak membuat notifikasi untuk kepsek yang tidak aktif', function (): void {
    $inactiveKepsek = User::factory()->asKepalaSekolah()->inactive()->create();
    $lessonPlan = LessonPlan::factory()->create(['status' => 'PENDING']);

    app(NotificationService::class)->createForLessonPlanPending($lessonPlan);

    expect($inactiveKepsek->notifications()->count())->toBe(0);
});

test('createForLessonPlanPending log error dan tidak throw jika teacher null', function (): void {
    Log::shouldReceive('error')->once();

    // Buat LessonPlan tanpa teacher yang valid
    $lessonPlan = LessonPlan::factory()->create(['status' => 'PENDING']);
    $lessonPlan->setRelation('teacher', null);

    expect(fn () => app(NotificationService::class)->createForLessonPlanPending($lessonPlan))
        ->not->toThrow(Throwable::class);

    expect(DatabaseNotification::count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// createForLessonPlanApproved
// ─────────────────────────────────────────────────────────────────────────────

test('createForLessonPlanApproved membuat notifikasi untuk guru pemilik RPP', function (): void {
    $guruUser = User::factory()->asGuru()->create();
    $teacher = Teacher::factory()->create(['user_id' => $guruUser->id]);
    $subject = Subject::factory()->create(['name' => 'Fisika']);
    $lessonPlan = LessonPlan::factory()->create([
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
        'status' => 'APPROVED',
    ]);

    app(NotificationService::class)->createForLessonPlanApproved($lessonPlan);

    $notification = $guruUser->notifications()->first();
    $data = $notification->data;
    expect($notification)->not->toBeNull()
        ->and($data['title'])->toContain('Fisika')
        ->and($data['title'])->toContain('disetujui')
        ->and($data['body'])->toContain('Kepala sekolah telah menyetujui RPP Anda.');
});

test('createForLessonPlanApproved notifikasi dibuat dengan read_at null (belum dibaca)', function (): void {
    $guruUser = User::factory()->asGuru()->create();
    $teacher = Teacher::factory()->create(['user_id' => $guruUser->id]);
    $lessonPlan = LessonPlan::factory()->create([
        'teacher_id' => $teacher->id,
        'status' => 'APPROVED',
    ]);

    app(NotificationService::class)->createForLessonPlanApproved($lessonPlan);

    $notification = $guruUser->notifications()->first();
    expect($notification->read_at)->toBeNull();
});

test('createForLessonPlanApproved log error dan tidak throw jika teacher null', function (): void {
    Log::shouldReceive('error')->once();

    $lessonPlan = LessonPlan::factory()->create(['status' => 'APPROVED']);
    $lessonPlan->setRelation('teacher', null);

    expect(fn () => app(NotificationService::class)->createForLessonPlanApproved($lessonPlan))
        ->not->toThrow(Throwable::class);

    expect(DatabaseNotification::count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// createForLessonPlanRevised
// ─────────────────────────────────────────────────────────────────────────────

test('createForLessonPlanRevised membuat notifikasi untuk guru pemilik RPP', function (): void {
    $guruUser = User::factory()->asGuru()->create();
    $teacher = Teacher::factory()->create(['user_id' => $guruUser->id]);
    $subject = Subject::factory()->create(['name' => 'Kimia']);
    $lessonPlan = LessonPlan::factory()->create([
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
        'status' => 'REVISED',
        'revision_note' => 'Tambahkan indikator pembelajaran.',
    ]);

    app(NotificationService::class)->createForLessonPlanRevised($lessonPlan);

    $notification = $guruUser->notifications()->first();
    $data = $notification->data;
    expect($notification)->not->toBeNull()
        ->and($data['title'])->toContain('Kimia')
        ->and($data['title'])->toContain('direvisi')
        ->and($data['body'])->toContain('Tambahkan indikator pembelajaran.');
});

test('createForLessonPlanRevised pesan mengandung revision_note', function (): void {
    $guruUser = User::factory()->asGuru()->create();
    $teacher = Teacher::factory()->create(['user_id' => $guruUser->id]);
    $revisionNote = 'Perbaiki tujuan pembelajaran dan metode asesmen.';
    $lessonPlan = LessonPlan::factory()->create([
        'teacher_id' => $teacher->id,
        'status' => 'REVISED',
        'revision_note' => $revisionNote,
    ]);

    app(NotificationService::class)->createForLessonPlanRevised($lessonPlan);

    $notification = $guruUser->notifications()->first();
    $data = $notification->data;
    expect($data['body'])->toContain($revisionNote);
});

// ─────────────────────────────────────────────────────────────────────────────
// createForKbmPending
// ─────────────────────────────────────────────────────────────────────────────

test('createForKbmPending membuat notifikasi untuk setiap kepsek aktif', function (): void {
    $kepsek1 = User::factory()->asKepalaSekolah()->create();
    $kepsek2 = User::factory()->asKepalaSekolah()->create();

    $kbm = Kbm::factory()->create(['status' => 'PENDING']);

    app(NotificationService::class)->createForKbmPending($kbm);

    expect($kepsek1->notifications()->count())->toBe(1)
        ->and($kepsek2->notifications()->count())->toBe(1);
});

test('createForKbmPending judul mengandung nama guru dan tanggal KBM', function (): void {
    $kepsek = User::factory()->asKepalaSekolah()->create();
    $guruUser = User::factory()->asGuru()->create(['name' => 'Siti Rahayu']);
    $teacher = Teacher::factory()->create(['user_id' => $guruUser->id]);
    $schedule = Schedule::factory()->create(['teacher_id' => $teacher->id]);
    $kbm = Kbm::factory()->create([
        'schedule_id' => $schedule->id,
        'date' => '2024-08-20',
        'status' => 'PENDING',
    ]);

    app(NotificationService::class)->createForKbmPending($kbm);

    $notification = $kepsek->notifications()->first();
    $data = $notification->data;
    expect($data['title'])->toContain('Siti Rahayu')
        ->and($data['title'])->toContain('20');
});

test('createForKbmPending pesan mengandung nama mapel dan kelas', function (): void {
    $kepsek = User::factory()->asKepalaSekolah()->create();
    $subject = Subject::factory()->create(['name' => 'Biologi']);
    $schoolClass = SchoolClass::factory()->create(['name' => 'XI IPA 2']);
    $schedule = Schedule::factory()->create([
        'subject_id' => $subject->id,
        'class_id' => $schoolClass->id,
    ]);
    $kbm = Kbm::factory()->create([
        'schedule_id' => $schedule->id,
        'status' => 'PENDING',
    ]);

    app(NotificationService::class)->createForKbmPending($kbm);

    $notification = $kepsek->notifications()->first();
    $data = $notification->data;
    expect($data['body'])->toContain('Biologi')
        ->and($data['body'])->toContain('XI IPA 2');
});

test('createForKbmPending tidak membuat notifikasi jika tidak ada kepsek aktif', function (): void {
    User::where('role', 'kepala_sekolah')->delete();

    $kbm = Kbm::factory()->create(['status' => 'PENDING']);

    expect(fn () => app(NotificationService::class)->createForKbmPending($kbm))
        ->not->toThrow(Throwable::class);

    expect(DatabaseNotification::count())->toBe(0);
});

test('createForKbmPending log error dan tidak throw jika schedule null', function (): void {
    Log::shouldReceive('error')->once();

    $kbm = Kbm::factory()->create(['status' => 'PENDING']);
    $kbm->setRelation('schedule', null);

    expect(fn () => app(NotificationService::class)->createForKbmPending($kbm))
        ->not->toThrow(Throwable::class);

    expect(DatabaseNotification::count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// createForKbmApproved
// ─────────────────────────────────────────────────────────────────────────────

test('createForKbmApproved membuat notifikasi untuk guru pemilik KBM', function (): void {
    $guruUser = User::factory()->asGuru()->create();
    $teacher = Teacher::factory()->create(['user_id' => $guruUser->id]);
    $schedule = Schedule::factory()->create(['teacher_id' => $teacher->id]);
    $kbm = Kbm::factory()->create([
        'schedule_id' => $schedule->id,
        'date' => '2024-09-10',
        'status' => 'APPROVED',
    ]);

    app(NotificationService::class)->createForKbmApproved($kbm);

    $notification = $guruUser->notifications()->first();
    $data = $notification->data;
    expect($notification)->not->toBeNull()
        ->and($data['title'])->toContain('disetujui')
        ->and($data['title'])->toContain('10')
        ->and($data['body'])->toContain('Kepala sekolah telah menyetujui laporan KBM Anda.');
});

test('createForKbmApproved log error dan tidak throw jika schedule null', function (): void {
    Log::shouldReceive('error')->once();

    $kbm = Kbm::factory()->create(['status' => 'APPROVED']);
    $kbm->setRelation('schedule', null);

    expect(fn () => app(NotificationService::class)->createForKbmApproved($kbm))
        ->not->toThrow(Throwable::class);

    expect(DatabaseNotification::count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// createForKbmRevised
// ─────────────────────────────────────────────────────────────────────────────

test('createForKbmRevised membuat notifikasi untuk guru pemilik KBM', function (): void {
    $guruUser = User::factory()->asGuru()->create();
    $teacher = Teacher::factory()->create(['user_id' => $guruUser->id]);
    $schedule = Schedule::factory()->create(['teacher_id' => $teacher->id]);
    $revisionNote = 'Lengkapi dokumentasi kegiatan belajar.';
    $kbm = Kbm::factory()->create([
        'schedule_id' => $schedule->id,
        'date' => '2024-10-05',
        'status' => 'REVISED',
        'revision_note' => $revisionNote,
    ]);

    app(NotificationService::class)->createForKbmRevised($kbm);

    $notification = $guruUser->notifications()->first();
    $data = $notification->data;
    expect($notification)->not->toBeNull()
        ->and($data['title'])->toContain('direvisi')
        ->and($data['body'])->toContain($revisionNote);
});

test('createForKbmRevised log error dan tidak throw jika teacher null', function (): void {
    Log::shouldReceive('error')->once();

    $schedule = Schedule::factory()->create();
    $kbm = Kbm::factory()->create([
        'schedule_id' => $schedule->id,
        'status' => 'REVISED',
        'revision_note' => 'Catatan revisi.',
    ]);
    $kbm->schedule->setRelation('teacher', null);

    expect(fn () => app(NotificationService::class)->createForKbmRevised($kbm))
        ->not->toThrow(Throwable::class);

    expect(DatabaseNotification::count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// Persistensi — nilai awal notifikasi
// ─────────────────────────────────────────────────────────────────────────────

test('notifikasi yang dibuat memiliki read_at null dan created_at tidak null', function (): void {
    $kepsek = User::factory()->asKepalaSekolah()->create();
    $lessonPlan = LessonPlan::factory()->create(['status' => 'PENDING']);

    app(NotificationService::class)->createForLessonPlanPending($lessonPlan);

    $notification = $kepsek->notifications()->first();
    $data = $notification->data;
    expect($notification->read_at)->toBeNull()
        ->and($notification->created_at)->not->toBeNull()
        ->and($data['title'])->not->toBeEmpty()
        ->and($data['body'])->not->toBeEmpty();
});
