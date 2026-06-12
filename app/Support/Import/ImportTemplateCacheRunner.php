<?php

namespace App\Support\Import;

use App\Models\Level;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ImportTemplateCacheRunner
{
    private const LOCK_FILENAME = '.caching.lock';

    private const LOG_FILENAME = 'import-template-cache.log';

    private const STALE_LOCK_HOURS = 3;

    private const COMPLETED_LOCK_GRACE_SECONDS = 60;

    public static function lockPath(): string
    {
        return storage_path('app/import-templates/'.self::LOCK_FILENAME);
    }

    public static function logPath(): string
    {
        return storage_path('logs/'.self::LOG_FILENAME);
    }

    public static function isRunning(): bool
    {
        $lockPath = self::lockPath();

        if (! is_file($lockPath)) {
            return false;
        }

        $startedAt = self::lockStartedAt();

        if ($startedAt !== null && $startedAt->lt(now()->subHours(self::STALE_LOCK_HOURS))) {
            self::releaseLock();

            return false;
        }

        return true;
    }

    public static function acquireLock(): bool
    {
        if (self::isRunning()) {
            return false;
        }

        File::ensureDirectoryExists(dirname(self::lockPath()));

        File::put(self::lockPath(), json_encode([
            'started_at' => now()->toIso8601String(),
            'pid' => \getmypid(),
        ], JSON_THROW_ON_ERROR));

        return true;
    }

    public static function releaseLock(): void
    {
        $lockPath = self::lockPath();

        if (is_file($lockPath)) {
            @unlink($lockPath);
        }
    }

    /**
     * @return array{status: 'success'|'error', exit_code: int, output: string, mode: 'sync', target: string|null}
     */
    public static function runSynchronously(?string $target = null): array
    {
        File::ensureDirectoryExists(storage_path('app/import-templates'));

        $exitCode = self::runCacheCommand($target);

        return [
            'status' => $exitCode === 0 ? 'success' : 'error',
            'exit_code' => $exitCode,
            'output' => trim(Artisan::output()),
            'mode' => 'sync',
            'target' => $target,
        ];
    }

    /**
     * @return array{
     *     status: 'started'|'running'|'error',
     *     mode: 'after_response',
     *     message: string,
     *     status_path: string,
     *     target: string|null
     * }
     */
    public static function dispatchInBackground(?string $target = null): array
    {
        $statusPath = '/deploy/'.config('app.deploy_secret').'/cache-import-templates/status';

        if (self::isRunning()) {
            return [
                'status' => 'running',
                'mode' => 'after_response',
                'message' => 'Proses cache template impor sedang berjalan di server.',
                'status_path' => $statusPath,
                'target' => $target,
            ];
        }

        self::scheduleAfterResponse($target);

        $message = filled($target)
            ? 'Cache template "'.$target.'" dijadwalkan setelah response HTTP. Cek status endpoint dalam 1-2 menit.'
            : 'Proses cache template impor dijadwalkan setelah response HTTP. Cek status endpoint dalam beberapa menit.';

        return [
            'status' => 'started',
            'mode' => 'after_response',
            'message' => $message,
            'status_path' => $statusPath,
            'target' => $target,
        ];
    }

    public static function scheduleAfterResponse(?string $target = null): void
    {
        File::ensureDirectoryExists(storage_path('app/import-templates'));
        File::ensureDirectoryExists(dirname(self::logPath()));

        $targetLabel = filled($target) ? ' ('.$target.')' : '';

        self::appendLog('Menjadwalkan cache template impor'.$targetLabel.' setelah response HTTP dikirim...');

        app()->terminating(function () use ($target, $targetLabel): void {
            if (function_exists('set_time_limit')) {
                @set_time_limit(0);
            }

            if (function_exists('ignore_user_abort')) {
                @ignore_user_abort(true);
            }

            self::appendLog('Worker melanjutkan cache template impor'.$targetLabel.'.');

            try {
                $exitCode = self::runCacheCommand($target);
                $output = trim(Artisan::output());

                if ($output !== '') {
                    self::appendLog($output);
                }

                self::appendLog($exitCode === 0
                    ? 'Cache template impor'.$targetLabel.' selesai.'
                    : 'Cache template impor'.$targetLabel.' gagal (exit code '.$exitCode.').');
            } catch (\Throwable $exception) {
                self::appendLog('Cache template impor'.$targetLabel.' gagal: '.$exception->getMessage());
            }
        });
    }

    public static function runCacheCommand(?string $target = null): int
    {
        $parameters = [];

        if (filled($target)) {
            $parameters['--target'] = $target;
        }

        return Artisan::call('personalia:cache-import-templates', $parameters);
    }

    public static function appendLog(string $message): void
    {
        File::ensureDirectoryExists(dirname(self::logPath()));

        File::append(
            self::logPath(),
            '['.now()->toIso8601String().'] '.$message.PHP_EOL,
        );
    }

    /**
     * @return array{
     *     running: bool,
     *     completed: bool,
     *     started_at: string|null,
     *     cached: array<string, bool>,
     *     log_tail: list<string>
     * }
     */
    public static function status(ImportTemplateExporter $exporter): array
    {
        $cached = self::cachedTargets($exporter);
        $completed = self::allTargetsCached($cached);
        $running = self::isRunning();

        if ($running && $completed) {
            $startedAt = self::lockStartedAt();

            if ($startedAt?->lt(now()->subSeconds(self::COMPLETED_LOCK_GRACE_SECONDS))) {
                self::releaseLock();
                $running = false;
            }
        }

        return [
            'running' => $running,
            'completed' => $completed,
            'started_at' => self::lockStartedAt()?->toIso8601String(),
            'cached' => $cached,
            'log_tail' => self::readLogTail(),
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function cachedTargets(ImportTemplateExporter $exporter): array
    {
        $cached = [
            'teacher' => $exporter->isCached('teacher'),
        ];

        foreach (Level::query()->orderedForDisplay()->get() as $level) {
            $cached['student_'.str($level->name)->slug()] = $exporter->isCached('student', $level->id);
        }

        return $cached;
    }

    /**
     * @param  array<string, bool>  $cached
     */
    public static function allTargetsCached(array $cached): bool
    {
        return $cached !== [] && collect($cached)->every(fn (bool $isCached): bool => $isCached);
    }

    public static function lockStartedAt(): ?Carbon
    {
        $lockPath = self::lockPath();

        if (! is_file($lockPath)) {
            return null;
        }

        $payload = json_decode((string) file_get_contents($lockPath), true);

        if (! is_array($payload) || ! isset($payload['started_at'])) {
            return null;
        }

        try {
            return Carbon::parse($payload['started_at']);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    private static function readLogTail(int $lines = 30): array
    {
        $logPath = self::logPath();

        if (! is_file($logPath)) {
            return [];
        }

        $contents = (string) file_get_contents($logPath);

        if ($contents === '') {
            return [];
        }

        $rows = preg_split('/\R/', trim($contents)) ?: [];

        return array_values(array_slice($rows, -$lines));
    }
}
