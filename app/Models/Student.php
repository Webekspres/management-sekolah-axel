<?php

namespace App\Models;

use App\HasUlid;
use App\Models\Traits\HasClassWithAcademicLevel;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasClassWithAcademicLevel, HasFactory, HasUlid, LogsActivity;

    public static function getActivityLogName(): string
    {
        return 'siswa';
    }

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'class_id',
        'nipd',
        'nisn',
        'nik',
        'kk_number',
        'birth_cert_number',
        'religion',
        'school_code',
        'student_phone',
        'special_needs',
        'house_number',
        'rt',
        'rw',
        'village',
        'district',
        'city',
        'father_name',
        'father_phone',
        'mother_name',
        'mother_phone',
        'admission_date',
        'origin_school',
        'diploma_date',
        'diploma_number',
        'custom_spp',
    ];

    protected function casts(): array
    {
        return [
            'admission_date' => 'date',
            'diploma_date' => 'date',
            'custom_spp' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function rapors(): HasMany
    {
        return $this->hasMany(Rapor::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function attitudeScores(): HasMany
    {
        return $this->hasMany(AttitudeScore::class);
    }

    public function knowledgeSkillScores(): HasMany
    {
        return $this->hasMany(KnowledgeSkillScore::class);
    }

    public function learningAchievements(): HasMany
    {
        return $this->hasMany(LearningAchievement::class);
    }

    public function personalityScore(): HasOne
    {
        return $this->hasOne(PersonalityScore::class);
    }
}
