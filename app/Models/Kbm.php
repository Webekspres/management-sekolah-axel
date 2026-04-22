<?php

namespace App\Models;

use App\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kbm extends Model
{
    use HasFactory, HasUlid;

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
        return $this->belongsTo(LessonPlan::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
