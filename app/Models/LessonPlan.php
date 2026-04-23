<?php

namespace App\Models;

use App\HasUlid;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonPlan extends Model
{
    use HasFactory, HasUlid;

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['teacher_id', 'subject_id', 'topic', 'file_path', 'status', 'revision_note'];

    public function submitForApproval(User $actor): void
    {
        if (! in_array($this->status, ['DRAFT', 'REVISED'], true)) {
            throw new DomainException('RPP hanya bisa diajukan dari status DRAFT atau REVISED.');
        }

        $this->update([
            'status' => 'PENDING',
            'revision_note' => null,
        ]);

        $this->recordActivity(
            actor: $actor,
            action: 'lesson_plan_submitted',
            description: 'Guru mengajukan RPP untuk approval kepala sekolah.'
        );
    }

    public function markAsRevised(User $actor, string $revisionNote): void
    {
        if ($this->status !== 'PENDING') {
            throw new DomainException('RPP hanya bisa direvisi dari status PENDING.');
        }

        $this->update([
            'status' => 'REVISED',
            'revision_note' => $revisionNote,
        ]);

        $this->recordActivity(
            actor: $actor,
            action: 'lesson_plan_revised',
            description: 'Kepala sekolah meminta revisi RPP: '.$revisionNote
        );
    }

    public function approve(User $actor): void
    {
        if ($this->status !== 'PENDING') {
            throw new DomainException('RPP hanya bisa disetujui dari status PENDING.');
        }

        $this->update([
            'status' => 'APPROVED',
            'revision_note' => null,
        ]);

        $this->recordActivity(
            actor: $actor,
            action: 'lesson_plan_approved',
            description: 'Kepala sekolah menyetujui RPP.'
        );
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class)
            ->withoutGlobalScope('academic_level');
    }

    public function kbms(): HasMany
    {
        return $this->hasMany(Kbm::class);
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
