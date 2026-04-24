<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasScheduleWithAcademicLevel
{
    protected static function bootHasScheduleWithAcademicLevel(): void
    {
        static::addGlobalScope('academic_level', function (Builder $builder) {
            if (! request()->hasSession()) {
                return;
            }

            if ($levelId = session('active_academic_level_id')) {
                $builder->whereHas('schedule.schoolClass', fn (Builder $query): Builder => $query->where('level_id', $levelId));
            }
        });
    }
}
