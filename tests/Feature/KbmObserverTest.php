<?php

use App\Models\Kbm;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;

test('observer membuat notifikasi saat status berubah menjadi PENDING', function (): void {
    $kepsek = User::factory()->asKepalaSekolah()->create();
    $kbm = Kbm::factory()->create(['status' => 'DRAFT']);

    $kbm->update(['status' => 'PENDING']);

    expect($kepsek->notifications()->count())->toBe(1);
});

test('observer membuat notifikasi saat status berubah menjadi APPROVED', function (): void {
    $kbm = Kbm::factory()->create(['status' => 'PENDING']);
    $guruUser = $kbm->schedule->teacher->user;

    $kbm->update(['status' => 'APPROVED']);

    expect($guruUser->notifications()->count())->toBe(1);
});

test('observer membuat notifikasi saat status berubah menjadi REVISED', function (): void {
    $kbm = Kbm::factory()->create(['status' => 'PENDING']);
    $guruUser = $kbm->schedule->teacher->user;

    $kbm->update(['status' => 'REVISED', 'revision_note' => 'Lengkapi dokumentasi']);

    expect($guruUser->notifications()->count())->toBe(1);
});

test('observer tidak membuat notifikasi saat kolom non-status berubah', function (): void {
    $kbm = Kbm::factory()->create(['status' => 'DRAFT', 'process_note' => 'Catatan awal']);

    $kbm->update(['process_note' => 'Catatan baru']);

    expect(DatabaseNotification::count())->toBe(0);
});

test('observer tidak membuat notifikasi saat revision_note berubah tanpa status berubah', function (): void {
    $kbm = Kbm::factory()->create([
        'status' => 'REVISED',
        'revision_note' => 'Catatan lama',
    ]);

    $kbm->update(['revision_note' => 'Catatan baru']);

    expect(DatabaseNotification::count())->toBe(0);
});

test('exception dalam service tidak menggagalkan update model', function (): void {
    Log::shouldReceive('error')->once();

    // Mock NotificationService untuk throw exception
    $this->mock(NotificationService::class, function ($mock): void {
        $mock->shouldReceive('createForKbmPending')
            ->once()
            ->andThrow(new RuntimeException('Database error'));
    });

    $kbm = Kbm::factory()->create(['status' => 'DRAFT']);

    // Update harus berhasil meskipun notifikasi gagal
    $kbm->update(['status' => 'PENDING']);

    // Verifikasi status tersimpan di database
    expect($kbm->fresh()->status)->toBe('PENDING');
});

test('observer tidak membuat notifikasi saat update tanpa perubahan status', function (): void {
    $kbm = Kbm::factory()->create(['status' => 'PENDING']);

    // Touch model tanpa mengubah status
    $kbm->update(['process_note' => 'Catatan yang sama']);

    // Hanya notifikasi dari factory (jika ada), tidak ada notifikasi baru
    $initialCount = DatabaseNotification::count();
    $kbm->update(['process_note' => 'Catatan lain']);

    expect(DatabaseNotification::count())->toBe($initialCount);
});
