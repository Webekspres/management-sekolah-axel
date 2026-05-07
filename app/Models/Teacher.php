<?php

namespace App\Models;

use App\HasUlid;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Teacher extends Model
{
    use HasFactory, HasUlid, LogsActivity;

    public static function getActivityLogName(): string
    {
        return 'guru';
    }

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['user_id', 'nip', 'employment_status'];

    protected static function booted(): void
    {
        static::deleting(function (Teacher $teacher): void {
            $teacher->ensureDeletable();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classesHandled(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'teacher_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function lessonPlans(): HasMany
    {
        return $this->hasMany(LessonPlan::class);
    }

    public function ensureDeletable(): void
    {
        $blockingRelations = $this->getBlockingRelations();

        if ($blockingRelations === []) {
            return;
        }

        $details = collect($blockingRelations)
            ->map(fn (array $relation): string => "{$relation['label']} ({$relation['count']})")
            ->implode(', ');

        throw ValidationException::withMessages([
            'teacher' => "Guru tidak dapat dihapus karena masih dipakai pada: {$details}.",
        ]);
    }

    /**
     * @return array<int, array{label: string, count: int}>
     */
    public function getBlockingRelations(): array
    {
        $this->loadCount(['classesHandled', 'schedules', 'lessonPlans']);

        $relations = [
            ['label' => 'Kelas', 'count' => (int) $this->classes_handled_count],
            ['label' => 'Jadwal', 'count' => (int) $this->schedules_count],
            ['label' => 'RPP', 'count' => (int) $this->lesson_plans_count],
        ];

        return array_values(array_filter(
            $relations,
            fn (array $relation): bool => $relation['count'] > 0,
        ));
    }
}
