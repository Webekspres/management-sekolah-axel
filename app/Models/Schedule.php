<?php

namespace App\Models;

use App\HasUlid;
use App\Models\Traits\HasClassWithAcademicLevel;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasClassWithAcademicLevel, HasFactory, HasUlid, LogsActivity;

    public static function getActivityLogName(): string
    {
        return 'jadwal';
    }

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['class_id', 'subject_id', 'teacher_id', 'day_of_week', 'start_time', 'end_time'];

    protected function casts(): array
    {
        return ['day_of_week' => 'integer'];
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function subjectForDisplay(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id')
            ->withoutGlobalScopes();
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function kbms(): HasMany
    {
        return $this->hasMany(Kbm::class);
    }
}
