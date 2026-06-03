<?php

namespace App\Models;

use App\HasUlid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    use HasFactory, HasUlid;

    /**
     * Canonical education progression order (SD → SMP → SMA).
     *
     * @var list<string>
     */
    public const DISPLAY_ORDER = ['SD', 'SMP', 'SMA'];

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['name', 'default_spp'];

    protected function casts(): array
    {
        return ['default_spp' => 'decimal:2'];
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    /**
     * @param  Builder<Level>  $query
     * @return Builder<Level>
     */
    public function scopeOrderedForDisplay(Builder $query): Builder
    {
        return $query
            ->orderByRaw(
                "CASE name
                    WHEN 'SD' THEN 1
                    WHEN 'SMP' THEN 2
                    WHEN 'SMA' THEN 3
                    ELSE 99
                END",
            )
            ->orderBy('name');
    }
}
