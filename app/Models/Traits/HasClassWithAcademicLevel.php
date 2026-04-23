<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasClassWithAcademicLevel
{
    protected static function bootHasClassWithAcademicLevel(): void
    {
        static::addGlobalScope('academic_level', function (Builder $builder) {
            if (app()->runningInConsole() || ! request()->hasSession()) {
                return;
            }
            if ($levelId = session('active_academic_level_id')) {
                $builder->whereHas('schoolClass', fn ($q) => $q->where('level_id', $levelId));
            }
        });
    }
}
