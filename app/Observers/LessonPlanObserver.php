<?php

namespace App\Observers;

use App\Models\LessonPlan;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class LessonPlanObserver
{
    public function __construct(private NotificationService $notificationService) {}

    public function updated(LessonPlan $lessonPlan): void
    {
        if (! $lessonPlan->wasChanged('status')) {
            return;
        }

        try {
            match ($lessonPlan->status) {
                'PENDING' => $this->notificationService->createForLessonPlanPending($lessonPlan),
                'APPROVED' => $this->notificationService->createForLessonPlanApproved($lessonPlan),
                'REVISED' => $this->notificationService->createForLessonPlanRevised($lessonPlan),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('Gagal membuat notifikasi RPP', [
                'context' => 'LessonPlanObserver',
                'lesson_plan_id' => $lessonPlan->id,
                'status' => $lessonPlan->status,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
