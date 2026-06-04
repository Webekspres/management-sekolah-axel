<?php

namespace App\Models;

use App\HasUlid;
use App\Models\Traits\HasStudentWithAcademicLevel;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rapor extends Model
{
    use HasFactory, HasStudentWithAcademicLevel, HasUlid, LogsActivity;

    public static function getActivityLogName(): string
    {
        return 'rapor';
    }

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'file_path',
        'status',
        'approved_at',
        'rejection_note',
        'program',
        'sumber_pembelajaran',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'generated_at' => 'datetime',
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === 'DRAFT';
    }

    public function isFinalized(): bool
    {
        return $this->status === 'FINALIZED';
    }

    public function isApproved(): bool
    {
        return $this->status === 'APPROVED';
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
