<?php

namespace App\Models;

use App\HasUlid;
use App\Models\Traits\HasStudentWithAcademicLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttitudeScore extends Model
{
    use HasFactory, HasStudentWithAcademicLevel, HasUlid;

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'aspect',
        'score',
        'description',
    ];

    protected function casts(): array
    {
        return ['score' => 'decimal:2'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
