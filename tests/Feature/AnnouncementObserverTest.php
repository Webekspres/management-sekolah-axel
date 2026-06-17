<?php

use App\Models\Announcement;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

test('observer membuat notifikasi saat Announcement dibuat', function (): void {
    $guru = User::factory()->asGuru()->create();

    Announcement::factory()->forGuru()->create();

    expect($guru->notifications()->count())->toBe(1);
});

test('exception dalam service tidak menggagalkan pembuatan Announcement', function (): void {
    Log::shouldReceive('error')->once();

    $this->mock(NotificationService::class, function ($mock): void {
        $mock->shouldReceive('createForAnnouncement')
            ->once()
            ->andThrow(new RuntimeException('Service error'));
    });

    $announcement = Announcement::factory()->forGuru()->create();

    expect(Announcement::find($announcement->id))->not->toBeNull();
});
