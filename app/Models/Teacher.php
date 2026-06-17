<?php

namespace App\Models;

use App\HasUlid;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            $teacher->classesHandled()->update(['teacher_id' => null]);
        });

        static::deleted(function (Teacher $teacher): void {
            $teacher->user()->delete();
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
}
