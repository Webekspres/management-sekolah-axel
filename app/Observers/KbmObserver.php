<?php

namespace App\Observers;

use App\Models\Kbm;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class KbmObserver
{
    public function __construct(private NotificationService $notificationService) {}

    public function updated(Kbm $kbm): void
    {
        if (! $kbm->wasChanged('status')) {
            return;
        }

        try {
            match ($kbm->status) {
                'PENDING' => $this->notificationService->createForKbmPending($kbm),
                'APPROVED' => $this->notificationService->createForKbmApproved($kbm),
                'REVISED' => $this->notificationService->createForKbmRevised($kbm),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('Gagal membuat notifikasi KBM', [
                'context' => 'KbmObserver',
                'kbm_id' => $kbm->id,
                'status' => $kbm->status,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
