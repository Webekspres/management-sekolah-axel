<?php

namespace App\Support;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ForeignKeyDeleteGuard
{
    /**
     * @var array<string, array<int, array{table: string, column: string, delete_rule: string}>>
     */
    private static array $constraintCache = [];

    public static function ensureDeletable(Model $model): void
    {
        if (! $model->exists) {
            return;
        }

        /** @var array<class-string, string> $usedTraits */
        $usedTraits = class_uses_recursive($model);
        $usesSoftDeletes = in_array(SoftDeletes::class, $usedTraits, true);

        if ($usesSoftDeletes && method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
            return;
        }

        $blocked = [];

        foreach (self::foreignKeyConstraintsForModel($model) as $constraint) {
            $count = DB::table($constraint['table'])
                ->where($constraint['column'], $model->getKey())
                ->count();

            if ($count > 0) {
                $blocked[] = [
                    'resource' => self::resourceLabel($constraint['table']),
                    'count' => $count,
                ];
            }
        }

        if ($blocked === []) {
            return;
        }

        $details = collect($blocked)
            ->map(fn (array $item): string => "{$item['resource']} ({$item['count']})")
            ->implode(', ');

        throw ValidationException::withMessages([
            'delete' => "Data tidak dapat dihapus karena masih dipakai pada: {$details}.",
        ]);
    }

    /**
     * @return array<int, array{table: string, column: string, delete_rule: string}>
     */
    private static function foreignKeyConstraintsForModel(Model $model): array
    {
        $connection = DB::connection($model->getConnectionName());
        $driver = $connection->getDriverName();
        $table = $model->getTable();
        $cacheKey = "{$driver}:{$connection->getDatabaseName()}:{$table}";

        if (array_key_exists($cacheKey, self::$constraintCache)) {
            return self::$constraintCache[$cacheKey];
        }

        $constraints = match ($driver) {
            'mysql', 'mariadb' => self::mysqlConstraints($connection, $table),
            'sqlite' => self::sqliteConstraints($connection, $table),
            default => [],
        };

        self::$constraintCache[$cacheKey] = $constraints;

        return $constraints;
    }

    /**
     * @return array<int, array{table: string, column: string, delete_rule: string}>
     */
    private static function mysqlConstraints(Connection $connection, string $table): array
    {
        $database = $connection->getDatabaseName();

        return $connection->table('information_schema.KEY_COLUMN_USAGE as kcu')
            ->join('information_schema.REFERENTIAL_CONSTRAINTS as rc', function ($join): void {
                $join->on('rc.CONSTRAINT_SCHEMA', '=', 'kcu.CONSTRAINT_SCHEMA')
                    ->on('rc.TABLE_NAME', '=', 'kcu.TABLE_NAME')
                    ->on('rc.CONSTRAINT_NAME', '=', 'kcu.CONSTRAINT_NAME');
            })
            ->where('kcu.REFERENCED_TABLE_SCHEMA', $database)
            ->where('kcu.REFERENCED_TABLE_NAME', $table)
            ->whereIn('rc.DELETE_RULE', ['RESTRICT', 'NO ACTION'])
            ->select([
                'kcu.TABLE_NAME as table',
                'kcu.COLUMN_NAME as column',
                'rc.DELETE_RULE as delete_rule',
            ])
            ->get()
            ->map(fn ($item): array => [
                'table' => (string) $item->table,
                'column' => (string) $item->column,
                'delete_rule' => (string) $item->delete_rule,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{table: string, column: string, delete_rule: string}>
     */
    private static function sqliteConstraints(Connection $connection, string $table): array
    {
        $constraints = [];
        $tables = $connection->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");

        foreach ($tables as $sqliteTable) {
            $tableName = (string) $sqliteTable->name;

            if (! preg_match('/^[A-Za-z0-9_]+$/', $tableName)) {
                continue;
            }

            $foreignKeys = $connection->select("PRAGMA foreign_key_list('{$tableName}')");

            foreach ($foreignKeys as $foreignKey) {
                $referencedTable = (string) $foreignKey->table;
                $deleteRule = strtoupper((string) $foreignKey->on_delete);

                if ($referencedTable !== $table || ! in_array($deleteRule, ['RESTRICT', 'NO ACTION'], true)) {
                    continue;
                }

                $constraints[] = [
                    'table' => $tableName,
                    'column' => (string) $foreignKey->from,
                    'delete_rule' => $deleteRule,
                ];
            }
        }

        return $constraints;
    }

    private static function resourceLabel(string $table): string
    {
        return Str::headline(Str::singular($table));
    }
}
