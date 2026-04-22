<?php

namespace App\Models;

use App\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory, HasUlid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['province_id', 'name'];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function subDistricts(): HasMany
    {
        return $this->hasMany(SubDistrict::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
