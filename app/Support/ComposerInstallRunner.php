<?php

namespace App\Support;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class ComposerInstallRunner
{
    /**
     * @return list<string>
     */
    public static function phpBinary(): string
    {
        $configured = config('app.deploy_php_cli');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        foreach (['84', '83', '82', '81'] as $version) {
            $candidate = "/opt/cpanel/ea-php{$version}/root/usr/bin/php";

            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        foreach (['/usr/local/bin/php', '/usr/bin/php'] as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        if (defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '') {
            return PHP_BINARY;
        }

        return 'php';
    }

    /**
     * @return list<string>
     */
    public static function phpInvocation(): array
    {
        return [static::phpBinary()];
    }

    /**
     * @return array<string, string>
     */
    public static function environment(): array
    {
        $home = storage_path();
        $composerHome = storage_path('.composer');

        return [
            'HOME' => $home,
            'COMPOSER_HOME' => $composerHome,
        ];
    }

    /**
     * @return list<string>
     */
    public static function command(): array
    {
        $installArgs = [
            'install',
            '--no-dev',
            '--no-interaction',
            '--prefer-dist',
            '--optimize-autoloader',
        ];

        $composerPhar = base_path('composer.phar');

        if (is_file($composerPhar)) {
            return [
                ...static::phpInvocation(),
                $composerPhar,
                ...$installArgs,
            ];
        }

        $cPanelComposer = '/opt/cpanel/composer/bin/composer';

        if (is_executable($cPanelComposer)) {
            return [
                ...static::phpInvocation(),
                $cPanelComposer,
                ...$installArgs,
            ];
        }

        return [
            ...static::phpInvocation(),
            '-r',
            'fwrite(STDERR, "composer.phar not found on server. Push to dev and wait for FTP deploy."); exit(1);',
        ];
    }

    public static function usesBundledPhar(): bool
    {
        return is_file(base_path('composer.phar'));
    }

    public static function run(): ProcessResult
    {
        File::ensureDirectoryExists(storage_path('.composer'));

        return Process::timeout(900)
            ->path(base_path())
            ->env(static::environment())
            ->run(static::command());
    }
}
