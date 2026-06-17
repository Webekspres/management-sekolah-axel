<?php

namespace App\Observers;

use App\Models\LessonPlanMaterial;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class LessonPlanMaterialObserver
{
    public function __construct(private NotificationService $notificationService) {}

    public function created(LessonPlanMaterial $material): void
    {
        try {
            $this->notificationService->createForLessonPlanMaterial($material);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat notifikasi LessonPlanMaterial', [
                'context' => 'LessonPlanMaterialObserver',
                'material_id' => $material->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
