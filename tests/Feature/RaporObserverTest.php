<?php

use App\Models\Rapor;
use App\Services\NotificationService;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;

test('observer membuat notifikasi saat status berubah ke APPROVED', function (): void {
    $rapor = Rapor::factory()->create(['status' => 'FINALIZED']);
    $studentUser = $rapor->student->user;

    $rapor->update(['status' => 'APPROVED']);

    expect($studentUser->notifications()->count())->toBe(1);
});

test('observer tidak membuat notifikasi saat status berubah ke FINALIZED', function (): void {
    $rapor = Rapor::factory()->create(['status' => 'DRAFT']);

    $rapor->update(['status' => 'FINALIZED']);

    expect(DatabaseNotification::count())->toBe(0);
});

test('observer tidak membuat notifikasi saat kolom non-status berubah', function (): void {
    $rapor = Rapor::factory()->create(['status' => 'DRAFT']);

    $rapor->update(['rejection_note' => 'Catatan baru']);

    expect(DatabaseNotification::count())->toBe(0);
});

test('exception dalam service tidak menggagalkan update model', function (): void {
    Log::shouldReceive('error')->once();

    $this->mock(NotificationService::class, function ($mock): void {
        $mock->shouldReceive('createForRaporApproved')
            ->once()
            ->andThrow(new RuntimeException('Service error'));
    });

    $rapor = Rapor::factory()->create(['status' => 'FINALIZED']);

    $rapor->update(['status' => 'APPROVED']);

    expect($rapor->fresh()->status)->toBe('APPROVED');
});
