<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(fn (Model $model) => static::writeActivityLog($model, 'created'));
        static::updated(fn (Model $model) => static::writeActivityLog($model, 'updated'));
        static::deleted(fn (Model $model) => static::writeActivityLog($model, 'deleted'));
    }

    private static function writeActivityLog(Model $model, string $action): void
    {
        try {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'entity_type' => static::class,
                'entity_id' => (string) $model->getKey(),
                'log_name' => static::getActivityLogName(),
                'description' => $action.' '.class_basename(static::class),
                'properties' => $action === 'updated'
                    ? ['old' => $model->getOriginal(), 'new' => $model->getDirty()]
                    : null,
            ]);
        } catch (\Throwable) {
            logger()->warning('ActivityLog write failed', [
                'model' => static::class,
                'action' => $action,
            ]);
        }
    }

    public static function getActivityLogName(): string
    {
        return 'general';
    }
}
