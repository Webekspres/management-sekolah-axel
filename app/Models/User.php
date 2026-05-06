<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\HasUlid;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'gender',
    'phone_number',
    'address_id',
    'place_of_birth',
    'date_of_birth',
    'is_active',
    'city_id',
    'address_detail',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    use HasFactory, HasUlid, Notifiable;

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function schoolNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function temporaryPolicyGrants(): HasMany
    {
        return $this->hasMany(TemporaryPolicyGrant::class);
    }

    public function temporaryRoleElevations(): HasMany
    {
        return $this->hasMany(TemporaryRoleElevation::class);
    }

    public function policyAbilities(): HasMany
    {
        return $this->hasMany(UserPolicyAbility::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $effectiveRole = $this->effectiveRole();

        return match ($panel->getId()) {
            'auth' => true,
            'admin' => $effectiveRole === 'super_admin',
            'kepsek' => in_array($effectiveRole, ['super_admin', 'kepala_sekolah'], true),
            'guru' => $effectiveRole === 'guru',
            'student' => $effectiveRole === 'siswa_ortu',
            default => false,
        };
    }

    public function effectiveRole(): string
    {
        return $this->role;
    }

    public function resolveStudentAcademicLevelId(): ?string
    {
        $student = Student::query()
            ->withoutGlobalScopes()
            ->with('schoolClass:id,level_id')
            ->where('user_id', $this->id)
            ->first();

        return $student?->schoolClass?->level_id ? (string) $student->schoolClass->level_id : null;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
