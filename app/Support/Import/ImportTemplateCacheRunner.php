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

    private const STALE_RUNNING_WITHOUT_OUTPUT_MINUTES = 35;

    private const STEP_STATE_FILENAME = '.cache-step-state.json';

    public const STEP_DEFAULT_ROW_BUDGET = 10000;

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

        if ($running && ! $completed) {
            $startedAt = self::lockStartedAt();
            $cachedCount = collect($cached)->filter()->count();

            if ($cachedCount === 0 && $startedAt?->lt(now()->subMinutes(self::STALE_RUNNING_WITHOUT_OUTPUT_MINUTES))) {
                self::appendLog('Lock dilepas otomatis: tidak ada file template terbentuk setelah '.self::STALE_RUNNING_WITHOUT_OUTPUT_MINUTES.' menit (proses kemungkinan macet).');
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

    public static function stepStatePath(): string
    {
        return storage_path('app/import-templates/'.self::STEP_STATE_FILENAME);
    }

    /**
     * Performs one small, bounded unit of work per call so the whole cache can
     * be built across many short HTTP requests (shared hosting safe):
     * first the wilayah CSV is exported in chunks, then one template per call.
     *
     * @return array{
     *     done: bool,
     *     busy: bool,
     *     phase: 'regions'|'template'|'done'|'busy',
     *     message: string,
     *     regions: array{exported: int, total: int, done: bool},
     *     cached: array<string, bool>
     * }
     */
    public static function step(ImportTemplateExporter $exporter, int $rowBudget = self::STEP_DEFAULT_ROW_BUDGET): array
    {
        File::ensureDirectoryExists(storage_path('app/import-templates'));

        if (! self::acquireLock()) {
            return self::stepResult($exporter, busy: true, phase: 'busy', message: 'Proses lain sedang berjalan. Halaman akan mencoba lagi otomatis.');
        }

        try {
            $state = self::readStepState();

            if (! $state['regions_done'] && $exporter->hasRegionsCsv()) {
                $state['regions_done'] = true;
                self::writeStepState($state);
            }

            if (! $state['regions_done']) {
                $written = $exporter->appendRegionsCsvChunk($state['regions_offset'], $rowBudget);
                $state['regions_offset'] += $written;

                if ($written < $rowBudget) {
                    $exporter->finalizeRegionsCsv();
                    $state['regions_done'] = true;
                }

                self::writeStepState($state);

                self::appendLog(sprintf(
                    'Langkah wilayah: %s baris diekspor%s.',
                    number_format($state['regions_offset']),
                    $state['regions_done'] ? ' (selesai)' : '',
                ));

                return self::stepResult($exporter, phase: 'regions', message: sprintf(
                    'Mengekspor data wilayah: %s baris selesai.',
                    number_format($state['regions_offset']),
                ));
            }

            foreach ($exporter->cacheTargets() as $target) {
                if ($exporter->isCached($target['type'], $target['level_id'])) {
                    continue;
                }

                $label = $target['type'] === 'teacher'
                    ? 'Guru'
                    : 'Siswa ('.(Level::query()->find($target['level_id'])?->name ?? 'semua').')';

                self::appendLog('Langkah template: membangun '.$label.'...');

                $exporter->warm($target['type'], $target['level_id']);

                self::appendLog('Langkah template: '.$label.' selesai.');

                return self::stepResult($exporter, phase: 'template', message: 'Template "'.$label.'" selesai dibangun.');
            }

            self::appendLog('Semua template impor selesai dibangun (mode bertahap).');

            return self::stepResult($exporter, done: true, phase: 'done', message: 'Semua template selesai. Silakan unduh template dari halaman admin.');
        } catch (\Throwable $exception) {
            self::appendLog('Langkah cache gagal: '.$exception->getMessage());

            throw $exception;
        } finally {
            self::releaseLock();
        }
    }

    public static function resetStepProgress(ImportTemplateExporter $exporter): void
    {
        self::releaseLock();
        $exporter->clearRegionsCsv();

        if (is_file(self::stepStatePath())) {
            @unlink(self::stepStatePath());
        }

        foreach ($exporter->cacheTargets() as $target) {
            $path = $exporter->cachedPath($target['type'], $target['level_id']);

            if (is_file($path)) {
                @unlink($path);
            }
        }

        self::appendLog('Progres cache bertahap direset.');
    }

    /**
     * @return array{regions_offset: int, regions_done: bool}
     */
    private static function readStepState(): array
    {
        $default = ['regions_offset' => 0, 'regions_done' => false];

        if (! is_file(self::stepStatePath())) {
            return $default;
        }

        $payload = json_decode((string) file_get_contents(self::stepStatePath()), true);

        if (! is_array($payload)) {
            return $default;
        }

        return [
            'regions_offset' => max(0, (int) ($payload['regions_offset'] ?? 0)),
            'regions_done' => (bool) ($payload['regions_done'] ?? false),
        ];
    }

    /**
     * @param  array{regions_offset: int, regions_done: bool}  $state
     */
    private static function writeStepState(array $state): void
    {
        File::ensureDirectoryExists(dirname(self::stepStatePath()));

        File::put(self::stepStatePath(), json_encode($state, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{
     *     done: bool,
     *     busy: bool,
     *     phase: 'regions'|'template'|'done'|'busy',
     *     message: string,
     *     regions: array{exported: int, total: int, done: bool},
     *     cached: array<string, bool>
     * }
     */
    private static function stepResult(
        ImportTemplateExporter $exporter,
        string $phase,
        string $message,
        bool $done = false,
        bool $busy = false,
    ): array {
        $state = self::readStepState();

        return [
            'done' => $done,
            'busy' => $busy,
            'phase' => $phase,
            'message' => $message,
            'regions' => [
                'exported' => $state['regions_offset'],
                'total' => $exporter->countRegionRows(),
                'done' => $state['regions_done'] || $exporter->hasRegionsCsv(),
            ],
            'cached' => self::cachedTargets($exporter),
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
