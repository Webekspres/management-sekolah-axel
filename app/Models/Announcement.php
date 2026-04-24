<?php

namespace App\Models;

use App\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
