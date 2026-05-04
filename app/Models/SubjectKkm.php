<?php

namespace App\Models;

use App\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectKkm extends Model
{
    use HasFactory, HasUlid;

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'subject_id',
        'level_id',
        'kkm',
    ];

    protected function casts(): array
    {
        return ['kkm' => 'decimal:2'];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * Get the KKM for a subject at a given level. Returns 70.0 if not configured.
     */
    public static function getKkm(string $subjectId, string $levelId): float
    {
        return (float) static::where('subject_id', $subjectId)
            ->where('level_id', $levelId)
            ->value('kkm') ?? 70.0;
    }
}
