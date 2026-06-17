<?php

namespace App\Models;

use App\HasUlid;
use App\Models\Traits\HasStudentWithAcademicLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $material_coverage_status 'Terpenuhi'|'Tidak Terpenuhi'
 * @property string|null $daily_assessment_predicate 'Kurang'|'Cukup'|'Baik'|'Sangat Baik'
 * @property string|null $midterm_assessment_predicate 'Kurang'|'Cukup'|'Baik'|'Sangat Baik'
 * @property string|null $final_assessment_predicate 'Kurang'|'Cukup'|'Baik'|'Sangat Baik'
 * @property string|null $achievement_status
 */
class LearningAchievement extends Model
{
    use HasFactory, HasStudentWithAcademicLevel, HasUlid;

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'subject_id',
        'academic_year_id',
        'topic_coverage',
        'notes',
        'material_coverage_status',
        'daily_assessment_predicate',
        'midterm_assessment_predicate',
        'final_assessment_predicate',
        'achievement_status',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
