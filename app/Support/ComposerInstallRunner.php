<?php

namespace App\Support;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Process;

class ComposerInstallRunner
{
    /**
     * @return list<string>
     */
    public static function command(): array
    {
        $composerPhar = base_path('composer.phar');

        if (is_file($composerPhar)) {
            return [
                defined('PHP_BINARY') ? PHP_BINARY : 'php',
                $composerPhar,
                'install',
                '--no-dev',
                '--no-interaction',
                '--prefer-dist',
                '--optimize-autoloader',
            ];
        }

        return [
            'composer',
            'install',
            '--no-dev',
            '--no-interaction',
            '--prefer-dist',
            '--optimize-autoloader',
        ];
    }

    public static function usesBundledPhar(): bool
    {
        return is_file(base_path('composer.phar'));
    }

    public static function run(): ProcessResult
    {
        $command = static::command();

        // #region agent log
        $logFile = base_path('debug-08c1bb.log');
        file_put_contents($logFile, json_encode([
            'sessionId' => '08c1bb',
            'runId' => 'release',
            'hypothesisId' => 'C',
            'location' => 'ComposerInstallRunner::run',
            'message' => 'composer install command',
            'data' => [
                'uses_phar' => static::usesBundledPhar(),
                'command' => $command,
            ],
            'timestamp' => (int) (microtime(true) * 1000),
        ]).PHP_EOL, FILE_APPEND);
        // #endregion

        return Process::timeout(900)
            ->path(base_path())
            ->run($command);
    }
}
