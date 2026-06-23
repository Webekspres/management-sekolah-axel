<?php

namespace App\Observers;

use App\Models\LessonPlan;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LessonPlanObserver
{
    public function __construct(private NotificationService $notificationService) {}

    public function created(LessonPlan $lessonPlan): void
    {
        $this->logFilePathDiagnostics($lessonPlan, 'created');
    }

    public function updated(LessonPlan $lessonPlan): void
    {
        if ($lessonPlan->wasChanged('file_path')) {
            $this->logFilePathDiagnostics($lessonPlan, 'file_path_updated');
        }

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

    private function logFilePathDiagnostics(LessonPlan $lessonPlan, string $event): void
    {
        $path = $lessonPlan->file_path;

        if ($path === null || $path === '') {
            return;
        }

        $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');

        // #region agent log
        file_put_contents(
            base_path('debug-0f345b.log'),
            json_encode([
                'sessionId' => '0f345b',
                'runId' => 'pre-fix',
                'hypothesisId' => 'B,C,D',
                'location' => 'LessonPlanObserver.php:logFilePathDiagnostics',
                'message' => 'Lesson plan file_path persisted',
                'data' => [
                    'event' => $event,
                    'lessonPlanId' => $lessonPlan->id,
                    'filePath' => $path,
                    'publicDiskExists' => Storage::disk('public')->exists($normalizedPath),
                    'localDiskExists' => Storage::disk('local')->exists($normalizedPath),
                    'publicSymlinkIsLink' => is_link(public_path('storage')),
                ],
                'timestamp' => (int) (microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES)."\n",
            FILE_APPEND
        );
        // #endregion
    }
}
