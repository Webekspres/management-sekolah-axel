<?php

namespace App\Models;

use App\HasUlid;
use App\Models\Traits\HasStudentWithAcademicLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    use HasFactory, HasStudentWithAcademicLevel, HasUlid;

    public const GRADE_TYPES = ['PH1', 'PH2', 'PH3', 'PH4', 'TUGAS1', 'TUGAS2', 'TUGAS3', 'TUGAS4', 'ATS', 'SAS', 'RAPOR'];

    public const PH_TYPES = ['PH1', 'PH2', 'PH3', 'PH4'];

    public const TUGAS_TYPES = ['TUGAS1', 'TUGAS2', 'TUGAS3', 'TUGAS4'];

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['student_id', 'subject_id', 'academic_year_id', 'grade_type', 'score'];

    protected function casts(): array
    {
        return ['score' => 'decimal:2'];
    }

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
