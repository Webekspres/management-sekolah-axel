<?php

namespace App\Models;

use Database\Factories\ClassesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    /** @use HasFactory<ClassesFactory> */
    use HasFactory;

    protected $guarded = ['id'];
}
