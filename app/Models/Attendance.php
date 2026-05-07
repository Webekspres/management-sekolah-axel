<?php

namespace App\Models;

use App\HasUlid;
use App\Models\Traits\HasKbmWithAcademicLevel;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory, HasKbmWithAcademicLevel, HasUlid, LogsActivity;

    public static function getActivityLogName(): string
    {
        return 'absensi';
    }

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['kbm_id', 'student_id', 'status'];

    public function kbm(): BelongsTo
    {
        return $this->belongsTo(Kbm::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
