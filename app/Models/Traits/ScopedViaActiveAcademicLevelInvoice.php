<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ScopedViaActiveAcademicLevelInvoice
{
    public const INVOICE_ACADEMIC_LEVEL_SCOPE = 'invoice_academic_level';

    protected static function bootScopedViaActiveAcademicLevelInvoice(): void
    {
        static::addGlobalScope(self::INVOICE_ACADEMIC_LEVEL_SCOPE, function (Builder $builder): void {
            if (! request()->hasSession()) {
                return;
            }

            $levelId = session('active_academic_level_id');

            if (! $levelId) {
                return;
            }

            $builder->whereHas('invoice', function (Builder $query) use ($levelId): void {
                $query->whereHas(
                    'student.schoolClass',
                    fn (Builder $classQuery): Builder => $classQuery->where('level_id', $levelId),
                );
            });
        });
    }

    public function scopeWithoutInvoiceAcademicLevelScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(self::INVOICE_ACADEMIC_LEVEL_SCOPE);
    }
}
