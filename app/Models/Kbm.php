<?php

namespace App\Models;

use App\HasUlid;
use App\Models\Traits\HasScheduleWithAcademicLevel;
use App\Models\Traits\LogsActivity;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kbm extends Model
{
    use HasFactory, HasScheduleWithAcademicLevel, HasUlid, LogsActivity;

    public static function getActivityLogName(): string
    {
        return 'kbm';
    }

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'schedule_id',
        'lesson_plan_id',
        'date',
        'process_note',
        'problem_note',
        'solution_note',
        'documentation_path',
        'status',
        'revision_note',
    ];

    public function submitForApproval(User $actor): void
    {
        if (! in_array($this->status, ['DRAFT', 'REVISED'], true)) {
            throw new DomainException('Laporan KBM hanya bisa diajukan dari status DRAFT atau REVISED.');
        }

        $this->update([
            'status' => 'PENDING',
            'revision_note' => null,
        ]);

        $this->recordActivity(
            actor: $actor,
            action: 'kbm_submitted',
            description: 'Guru mengajukan laporan KBM untuk approval kepala sekolah.'
        );
    }

    public function markAsRevised(User $actor, string $revisionNote): void
    {
        if ($this->status !== 'PENDING') {
            throw new DomainException('Laporan KBM hanya bisa direvisi dari status PENDING.');
        }

        $this->update([
            'status' => 'REVISED',
            'revision_note' => $revisionNote,
        ]);

        $this->recordActivity(
            actor: $actor,
            action: 'kbm_revised',
            description: 'Kepala sekolah meminta revisi laporan KBM: '.$revisionNote
        );
    }

    public function approve(User $actor): void
    {
        if (! in_array($this->status, ['PENDING', 'REVISED'], true)) {
            throw new DomainException('Laporan KBM hanya bisa disetujui dari status PENDING atau REVISED.');
        }

        $this->update([
            'status' => 'APPROVED',
            'revision_note' => null,
        ]);

        $this->recordActivity(
            actor: $actor,
            action: 'kbm_approved',
            description: 'Kepala sekolah menyetujui laporan KBM.'
        );
    }

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function lessonPlan(): BelongsTo
    {
        return $this->belongsTo(LessonPlan::class)->withoutGlobalScopes();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    private function recordActivity(User $actor, string $action, string $description): void
    {
        ActivityLog::query()->create([
            'user_id' => $actor->id,
            'action' => $action,
            'entity_type' => self::class,
            'entity_id' => $this->id,
            'description' => $description,
        ]);
    }
}
