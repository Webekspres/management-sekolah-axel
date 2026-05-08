<?php

namespace App\Observers;

use App\Models\Announcement;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class AnnouncementObserver
{
    public function __construct(private NotificationService $notificationService) {}

    public function created(Announcement $announcement): void
    {
        try {
            $this->notificationService->createForAnnouncement($announcement);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat notifikasi Announcement', [
                'context' => 'AnnouncementObserver',
                'announcement_id' => $announcement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
