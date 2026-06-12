<?php

namespace App\Support\Import;

use App\Models\Level;
use App\Support\ComposerInstallRunner;
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
     * @return array{status: 'success'|'error', exit_code: int, output: string, mode: 'sync'}
     */
    public static function runSynchronously(): array
    {
        File::ensureDirectoryExists(storage_path('app/import-templates'));

        $exitCode = Artisan::call('personalia:cache-import-templates');

        return [
            'status' => $exitCode === 0 ? 'success' : 'error',
            'exit_code' => $exitCode,
            'output' => trim(Artisan::output()),
            'mode' => 'sync',
        ];
    }

    /**
     * @return array{
     *     status: 'started'|'running'|'error',
     *     mode: 'background',
     *     message: string,
     *     status_path: string
     * }
     */
    public static function dispatchInBackground(): array
    {
        $statusPath = '/deploy/'.config('app.deploy_secret').'/cache-import-templates/status';

        if (self::isRunning()) {
            return [
                'status' => 'running',
                'mode' => 'background',
                'message' => 'Proses cache template impor sedang berjalan di server.',
                'status_path' => $statusPath,
            ];
        }

        if (! self::startInBackground()) {
            return [
                'status' => 'error',
                'mode' => 'background',
                'message' => 'Gagal memulai proses cache template impor di background.',
                'status_path' => $statusPath,
            ];
        }

        return [
            'status' => 'started',
            'mode' => 'background',
            'message' => 'Proses cache template impor dimulai di background. Cek status beberapa menit lagi.',
            'status_path' => $statusPath,
        ];
    }

    public static function startInBackground(): bool
    {
        if (self::isRunning()) {
            return false;
        }

        File::ensureDirectoryExists(storage_path('app/import-templates'));
        File::ensureDirectoryExists(dirname(self::logPath()));

        $php = \escapeshellarg(ComposerInstallRunner::phpBinary());
        $artisan = \escapeshellarg(base_path('artisan'));
        $log = \escapeshellarg(self::logPath());

        $command = sprintf(
            '%s %s personalia:cache-import-templates >> %s 2>&1',
            $php,
            $artisan,
            $log,
        );

        if (PHP_OS_FAMILY === 'Windows') {
            \pclose(\popen('start /B '.$command, 'r'));
        } else {
            \exec($command.' &');
        }

        return true;
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
