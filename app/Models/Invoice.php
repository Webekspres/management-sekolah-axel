<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\HasUlid;
use App\Models\Traits\HasStudentWithAcademicLevel;
use App\Models\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Invoice extends Model
{
    use HasFactory, HasStudentWithAcademicLevel, HasUlid, LogsActivity;

    public static function getActivityLogName(): string
    {
        return 'spp';
    }

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'invoice_number',
        'student_id',
        'academic_year_id',
        'amount',
        'description',
        'billing_period',
        'status',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'created_at' => 'datetime',
            'status' => PaymentStatus::class,
        ];
    }

    public static function billingPeriodFromDate(Carbon $date): string
    {
        return $date->format('Y-m');
    }

    /**
     * @param  Builder<Invoice>  $query
     */
    public function scopeForBillingPeriod(Builder $query, Student $student, AcademicYear $academicYear, string $billingPeriod): Builder
    {
        return $query
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('billing_period', $billingPeriod);
    }

    public function hasPaymentHistory(): bool
    {
        if ($this->relationLoaded('payments')) {
            return $this->payments->isNotEmpty();
        }

        return $this->payments()->exists();
    }

    public function isLockedForEditing(): bool
    {
        $status = $this->status instanceof PaymentStatus
            ? $this->status
            : PaymentStatus::tryFrom((string) $this->status);

        return $status === PaymentStatus::Paid || $this->hasPaymentHistory();
    }

    public function isPayableByStudent(): bool
    {
        $status = $this->status instanceof PaymentStatus
            ? $this->status
            : PaymentStatus::tryFrom((string) $this->status);

        return in_array($status, [PaymentStatus::Unpaid, PaymentStatus::Failed], true);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
