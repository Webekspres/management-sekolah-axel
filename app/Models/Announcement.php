<?php

namespace App\Models;

use App\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    use HasFactory, HasUlid;

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['title', 'content', 'target_role'];

    protected function casts(): array
    {
        return [
            'target_role' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function isRead(?int $userId = null): bool
    {
        $resolvedUserId = $userId ?? auth()->id();

        return $this->reads()
            ->where('user_id', (string) $resolvedUserId)
            ->exists();
    }
}
