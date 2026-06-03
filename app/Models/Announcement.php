<?php

namespace App\Models;

use App\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    use HasFactory, HasUlid;

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['title', 'content', 'target_role', 'created_by'];

    protected function casts(): array
    {
        return [
            'target_role' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function isCreatedBy(?User $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null || blank($this->created_by)) {
            return false;
        }

        return (string) $this->created_by === (string) $user->id;
    }

    public function isRead(?int $userId = null): bool
    {
        $resolvedUserId = $userId ?? auth()->id();

        return $this->reads()
            ->where('user_id', (string) $resolvedUserId)
            ->exists();
    }
}
