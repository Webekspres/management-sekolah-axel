<?php

namespace App\Models;

use App\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LessonPlanMaterial extends Model
{
    use HasFactory, HasUlid;

    public $timestamps = false;

    protected $fillable = [
        'lesson_plan_id',
        'file_path',
        'original_filename',
    ];

    public function lessonPlan(): BelongsTo
    {
        return $this->belongsTo(LessonPlan::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (LessonPlanMaterial $material): void {
            try {
                Storage::disk('public')->delete($material->file_path);
            } catch (\Throwable) {
                // File tidak ditemukan — tetap hapus record
            }
        });
    }
}
