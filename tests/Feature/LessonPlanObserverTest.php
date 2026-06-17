<?php

use App\Models\LessonPlan;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;

test('observer membuat notifikasi saat status berubah menjadi PENDING', function (): void {
    $kepsek = User::factory()->asKepalaSekolah()->create();
    $lessonPlan = LessonPlan::factory()->create(['status' => 'DRAFT']);

    $lessonPlan->update(['status' => 'PENDING']);

    expect($kepsek->notifications()->count())->toBe(1);
});

test('observer membuat notifikasi saat status berubah menjadi APPROVED', function (): void {
    $lessonPlan = LessonPlan::factory()->create(['status' => 'PENDING']);
    $guruUser = $lessonPlan->teacher->user;

    $lessonPlan->update(['status' => 'APPROVED']);

    expect($guruUser->notifications()->count())->toBe(1);
});

test('observer membuat notifikasi saat status berubah menjadi REVISED', function (): void {
    $lessonPlan = LessonPlan::factory()->create(['status' => 'PENDING']);
    $guruUser = $lessonPlan->teacher->user;

    $lessonPlan->update(['status' => 'REVISED', 'revision_note' => 'Perbaiki indikator']);

    expect($guruUser->notifications()->count())->toBe(1);
});

test('observer tidak membuat notifikasi saat kolom non-status berubah', function (): void {
    $lessonPlan = LessonPlan::factory()->create(['status' => 'DRAFT', 'topic' => 'Topik Awal']);

    $lessonPlan->update(['topic' => 'Topik Baru']);

    expect(DatabaseNotification::count())->toBe(0);
});

test('observer tidak membuat notifikasi saat revision_note berubah tanpa status berubah', function (): void {
    $lessonPlan = LessonPlan::factory()->create([
        'status' => 'REVISED',
        'revision_note' => 'Catatan lama',
    ]);

    $lessonPlan->update(['revision_note' => 'Catatan baru']);

    expect(DatabaseNotification::count())->toBe(0);
});

test('exception dalam service tidak menggagalkan update model', function (): void {
    Log::shouldReceive('error')->once();

    // Mock NotificationService untuk throw exception
    $this->mock(NotificationService::class, function ($mock): void {
        $mock->shouldReceive('createForLessonPlanPending')
            ->once()
            ->andThrow(new RuntimeException('Database error'));
    });

    $lessonPlan = LessonPlan::factory()->create(['status' => 'DRAFT']);

    // Update harus berhasil meskipun notifikasi gagal
    $lessonPlan->update(['status' => 'PENDING']);

    // Verifikasi status tersimpan di database
    expect($lessonPlan->fresh()->status)->toBe('PENDING');
});

test('observer tidak membuat notifikasi saat update tanpa perubahan status', function (): void {
    $lessonPlan = LessonPlan::factory()->create(['status' => 'PENDING']);

    // Touch model tanpa mengubah status
    $lessonPlan->update(['topic' => 'Topik yang sama']);

    // Hanya notifikasi dari factory (jika ada), tidak ada notifikasi baru
    $initialCount = DatabaseNotification::count();
    $lessonPlan->update(['topic' => 'Topik lain']);

    expect(DatabaseNotification::count())->toBe($initialCount);
});
