<?php

namespace App\Observers;

use App\Models\Rapor;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class RaporObserver
{
    public function __construct(private NotificationService $notificationService) {}

    public function updated(Rapor $rapor): void
    {
        if (! $rapor->wasChanged('status')) {
            return;
        }

        if ($rapor->status !== 'APPROVED') {
            return;
        }

        try {
            $this->notificationService->createForRaporApproved($rapor);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat notifikasi Rapor', [
                'context' => 'RaporObserver',
                'rapor_id' => $rapor->id,
                'status' => $rapor->status,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
