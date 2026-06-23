<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Kbm;
use App\Models\LessonPlan;
use App\Models\LessonPlanMaterial;
use App\Models\Rapor;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Kirim notifikasi ke semua kepsek aktif saat RPP diajukan.
     */
    public function createForLessonPlanPending(LessonPlan $lessonPlan): void
    {
        $teacher = $lessonPlan->teacher;

        if ($teacher === null) {
            Log::error('Gagal membuat notifikasi RPP PENDING: relasi teacher tidak valid.', [
                'lesson_plan_id' => $lessonPlan->id,
            ]);

            return;
        }

        $subject = $lessonPlan->subject;
        $schoolClass = $lessonPlan->schoolClass;

        $title = "{$teacher->user->name} mengajukan RPP {$subject->name}";
        $message = "Kelas {$schoolClass->name} · Tanggal implementasi: {$lessonPlan->implementation_date->translatedFormat('d M Y')}";

        $this->notifyAllActiveKepsek($title, $message);
    }

    /**
     * Kirim notifikasi ke guru pemilik RPP saat RPP disetujui.
     */
    public function createForLessonPlanApproved(LessonPlan $lessonPlan): void
    {
        $user = $this->resolveGuruUserFromLessonPlan($lessonPlan);

        if ($user === null) {
            return;
        }

        $subject = $lessonPlan->subject;

        $title = "RPP {$subject->name} Anda telah disetujui";
        $message = 'Kepala sekolah telah menyetujui RPP Anda.';

        $this->sendDatabaseNotification($user, $title, $message);
    }

    /**
     * Kirim notifikasi ke guru pemilik RPP saat RPP diminta revisi.
     */
    public function createForLessonPlanRevised(LessonPlan $lessonPlan): void
    {
        $user = $this->resolveGuruUserFromLessonPlan($lessonPlan);

        if ($user === null) {
            return;
        }

        $subject = $lessonPlan->subject;

        $title = "RPP {$subject->name} Anda perlu direvisi";
        $message = "Catatan: {$lessonPlan->revision_note}";

        // #region agent log
        file_put_contents(
            base_path('debug-cb6ab0.log'),
            json_encode([
                'sessionId' => 'cb6ab0',
                'runId' => 'pre-fix',
                'hypothesisId' => 'D',
                'location' => 'NotificationService.php:createForLessonPlanRevised',
                'message' => 'Lesson plan revised notification message composed',
                'data' => [
                    'messageContainsHtml' => str_contains($message, '<') && str_contains($message, '>'),
                    'messagePreview' => mb_substr($message, 0, 160),
                    'revisionNotePreview' => is_string($lessonPlan->revision_note) ? mb_substr($lessonPlan->revision_note, 0, 120) : null,
                ],
                'timestamp' => (int) (microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES)."\n",
            FILE_APPEND
        );
        // #endregion

        $this->sendDatabaseNotification($user, $title, $message);
    }

    /**
     * Kirim notifikasi ke semua kepsek aktif saat laporan KBM diajukan.
     */
    public function createForKbmPending(Kbm $kbm): void
    {
        $schedule = $kbm->schedule;

        if ($schedule === null) {
            Log::error('Gagal membuat notifikasi KBM PENDING: relasi schedule tidak valid.', [
                'kbm_id' => $kbm->id,
            ]);

            return;
        }

        $teacher = $schedule->teacher;

        if ($teacher === null) {
            Log::error('Gagal membuat notifikasi KBM PENDING: relasi teacher tidak valid.', [
                'kbm_id' => $kbm->id,
            ]);

            return;
        }

        $subject = $schedule->subject;
        $schoolClass = $schedule->schoolClass;

        $title = "{$teacher->user->name} mengajukan laporan KBM {$kbm->date->translatedFormat('d M Y')}";
        $message = "{$subject->name} · Kelas {$schoolClass->name}";

        $this->notifyAllActiveKepsek($title, $message);
    }

    /**
     * Kirim notifikasi ke guru pemilik KBM saat laporan KBM disetujui.
     */
    public function createForKbmApproved(Kbm $kbm): void
    {
        $user = $this->resolveGuruUserFromKbm($kbm);

        if ($user === null) {
            return;
        }

        $title = "Laporan KBM {$kbm->date->translatedFormat('d M Y')} Anda telah disetujui";
        $message = 'Kepala sekolah telah menyetujui laporan KBM Anda.';

        $this->sendDatabaseNotification($user, $title, $message);
    }

    /**
     * Kirim notifikasi ke guru pemilik KBM saat laporan KBM diminta revisi.
     */
    public function createForKbmRevised(Kbm $kbm): void
    {
        $user = $this->resolveGuruUserFromKbm($kbm);

        if ($user === null) {
            return;
        }

        $title = "Laporan KBM {$kbm->date->translatedFormat('d M Y')} Anda perlu direvisi";
        $message = "Catatan: {$kbm->revision_note}";

        $this->sendDatabaseNotification($user, $title, $message);
    }

    /**
     * Kirim notifikasi ke siswa pemilik rapor saat rapor disetujui.
     */
    public function createForRaporApproved(Rapor $rapor): void
    {
        $student = $rapor->student;

        if ($student === null) {
            Log::error('Gagal membuat notifikasi Rapor APPROVED: relasi student tidak valid.', [
                'rapor_id' => $rapor->id,
            ]);

            return;
        }

        $user = $student->user;

        if ($user === null) {
            Log::error('Gagal membuat notifikasi Rapor APPROVED: relasi student->user tidak valid.', [
                'rapor_id' => $rapor->id,
                'student_id' => $student->id,
            ]);

            return;
        }

        $academicYear = $rapor->academicYear;

        if ($academicYear === null) {
            Log::error('Gagal membuat notifikasi Rapor APPROVED: relasi academicYear tidak valid.', [
                'rapor_id' => $rapor->id,
            ]);

            return;
        }

        $title = "Rapor {$academicYear->name} Anda telah tersedia";
        $message = 'Rapor Anda telah disetujui. Silakan akses untuk melihat hasil belajar Anda.';

        $this->sendDatabaseNotification($user, $title, $message);
    }

    /**
     * Kirim notifikasi ke semua user aktif sesuai target_role saat pengumuman dibuat.
     */
    public function createForAnnouncement(Announcement $announcement): void
    {
        $targetRoles = $announcement->target_role;

        if (empty($targetRoles)) {
            return;
        }

        $users = User::query()
            ->where('is_active', true)
            ->whereIn('role', $targetRoles)
            ->get();

        $title = "Pengumuman: {$announcement->title}";
        $message = Str::limit($announcement->content, 100);

        foreach ($users as $user) {
            $this->sendDatabaseNotification($user, $title, $message);
        }
    }

    /**
     * Kirim notifikasi ke semua siswa aktif di kelas RPP saat materi baru diupload.
     */
    public function createForLessonPlanMaterial(LessonPlanMaterial $material): void
    {
        $lessonPlan = $material->lessonPlan;

        if ($lessonPlan === null) {
            Log::error('Gagal membuat notifikasi LessonPlanMaterial: relasi lessonPlan tidak valid.', [
                'material_id' => $material->id,
            ]);

            return;
        }

        $schoolClass = $lessonPlan->schoolClass;

        if ($schoolClass === null) {
            Log::error('Gagal membuat notifikasi LessonPlanMaterial: relasi schoolClass tidak valid.', [
                'material_id' => $material->id,
                'lesson_plan_id' => $lessonPlan->id,
            ]);

            return;
        }

        $subject = $lessonPlan->subject;

        if ($subject === null) {
            Log::error('Gagal membuat notifikasi LessonPlanMaterial: relasi subject tidak valid.', [
                'material_id' => $material->id,
                'lesson_plan_id' => $lessonPlan->id,
            ]);

            return;
        }

        $teacher = $lessonPlan->teacher;

        if ($teacher === null || $teacher->user === null) {
            Log::error('Gagal membuat notifikasi LessonPlanMaterial: relasi teacher->user tidak valid.', [
                'material_id' => $material->id,
                'lesson_plan_id' => $lessonPlan->id,
            ]);

            return;
        }

        $subjectName = $subject->name;
        $className = $schoolClass->name;
        $teacherName = $teacher->user->name;

        $title = "Materi baru: {$material->original_filename} ({$subjectName})";
        $message = "Kelas {$className} · Diunggah oleh {$teacherName}";

        $students = $schoolClass->students()->with('user')->get();

        foreach ($students as $student) {
            $user = $student->user;

            if ($user === null || ! $user->is_active) {
                continue;
            }

            $this->sendDatabaseNotification($user, $title, $message);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function notifyAllActiveKepsek(string $title, string $message): void
    {
        $kepsekUsers = User::query()
            ->where('role', 'kepala_sekolah')
            ->where('is_active', true)
            ->get();

        foreach ($kepsekUsers as $kepsek) {
            $this->sendDatabaseNotification($kepsek, $title, $message);
        }
    }

    private function sendDatabaseNotification(User $user, string $title, string $message): void
    {
        Notification::make()
            ->title($title)
            ->body($message)
            ->sendToDatabase($user);
    }

    private function resolveGuruUserFromLessonPlan(LessonPlan $lessonPlan): ?User
    {
        $teacher = $lessonPlan->teacher;

        if ($teacher === null) {
            Log::error('Gagal membuat notifikasi RPP: relasi teacher tidak valid.', [
                'lesson_plan_id' => $lessonPlan->id,
                'status' => $lessonPlan->status,
            ]);

            return null;
        }

        $user = $teacher->user;

        if ($user === null) {
            Log::error('Gagal membuat notifikasi RPP: relasi teacher->user tidak valid.', [
                'lesson_plan_id' => $lessonPlan->id,
                'teacher_id' => $teacher->id,
                'status' => $lessonPlan->status,
            ]);

            return null;
        }

        return $user;
    }

    private function resolveGuruUserFromKbm(Kbm $kbm): ?User
    {
        $schedule = $kbm->schedule;

        if ($schedule === null) {
            Log::error('Gagal membuat notifikasi KBM: relasi schedule tidak valid.', [
                'kbm_id' => $kbm->id,
                'status' => $kbm->status,
            ]);

            return null;
        }

        $teacher = $schedule->teacher;

        if ($teacher === null) {
            Log::error('Gagal membuat notifikasi KBM: relasi schedule->teacher tidak valid.', [
                'kbm_id' => $kbm->id,
                'schedule_id' => $schedule->id,
                'status' => $kbm->status,
            ]);

            return null;
        }

        $user = $teacher->user;

        if ($user === null) {
            Log::error('Gagal membuat notifikasi KBM: relasi teacher->user tidak valid.', [
                'kbm_id' => $kbm->id,
                'teacher_id' => $teacher->id,
                'status' => $kbm->status,
            ]);

            return null;
        }

        return $user;
    }
}
