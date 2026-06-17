<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasKbmWithAcademicLevel
{
    protected static function bootHasKbmWithAcademicLevel(): void
    {
        static::addGlobalScope('academic_level', function (Builder $builder) {
            if (! request()->hasSession()) {
                return;
            }

            if ($levelId = session('active_academic_level_id')) {
                $builder->whereHas('kbm.schedule.schoolClass', fn (Builder $query): Builder => $query->where('level_id', $levelId));
            }
        });
    }
}
