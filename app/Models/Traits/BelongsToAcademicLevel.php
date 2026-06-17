<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToAcademicLevel
{
    protected static function bootBelongsToAcademicLevel(): void
    {
        static::addGlobalScope('academic_level', function (Builder $builder) {
            if (! request()->hasSession()) {
                return;
            }
            if ($levelId = session('active_academic_level_id')) {
                $builder->where($builder->getModel()->getTable().'.level_id', $levelId);
            }
        });
    }
}
