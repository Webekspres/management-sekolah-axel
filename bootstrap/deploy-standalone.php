<?php

/**
 * Shared helpers for browser deploy scripts that must run without vendor/ or Laravel.
 */

declare(strict_types=1);

function deploy_standalone_base_path(): string
{
    return dirname(__DIR__);
}

function deploy_standalone_read_env_value(string $envPath, string $key): ?string
{
    if (! is_file($envPath)) {
        return null;
    }

    $prefix = $key.'=';

    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (str_starts_with($line, $prefix)) {
            $value = trim(substr($line, strlen($prefix)), " \t\"'");

            return $value === '' ? null : $value;
        }
    }

    return null;
}

function deploy_standalone_deploy_secret(string $envPath): ?string
{
    return deploy_standalone_read_env_value($envPath, 'DEPLOY_SECRET');
}

function deploy_standalone_php_binary(string $envPath): string
{
    $configured = deploy_standalone_read_env_value($envPath, 'DEPLOY_PHP_CLI');

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
 * @return array{http_code: int, payload: array<string, mixed>}|null
 */
function deploy_standalone_token_error(string $token, string $envPath): ?array
{
    if (! is_file($envPath)) {
        return [
            'http_code' => 500,
            'payload' => ['status' => 'error', 'message' => '.env not found'],
        ];
    }

    $deploySecret = deploy_standalone_deploy_secret($envPath);

    if ($deploySecret === null || $deploySecret === '') {
        return [
            'http_code' => 500,
            'payload' => ['status' => 'error', 'message' => 'DEPLOY_SECRET not configured in .env'],
        ];
    }

    if (! hash_equals($deploySecret, $token)) {
        return [
            'http_code' => 403,
            'payload' => ['status' => 'error', 'message' => 'Invalid deploy token'],
        ];
    }

    return null;
}

/**
 * @return list<string>
 */
function deploy_standalone_composer_install_command(string $basePath, string $phpBinary): array
{
    $installArgs = [
        'install',
        '--no-dev',
        '--no-interaction',
        '--no-scripts',
        '--prefer-dist',
        '--optimize-autoloader',
    ];

    $composerPhar = $basePath.'/composer.phar';

    if (is_file($composerPhar)) {
        return [
            $phpBinary,
            $composerPhar,
            ...$installArgs,
        ];
    }

    $cPanelComposer = '/opt/cpanel/composer/bin/composer';

    if (is_executable($cPanelComposer)) {
        return [
            $phpBinary,
            $cPanelComposer,
            ...$installArgs,
        ];
    }

    return [
        $phpBinary,
        '-r',
        'fwrite(STDERR, "composer.phar not found on server. Push to main and wait for FTP deploy."); exit(1);',
    ];
}

/**
 * @return array<string, string>
 */
function deploy_standalone_composer_environment(string $basePath): array
{
    return [
        'HOME' => $basePath.'/storage',
        'COMPOSER_HOME' => $basePath.'/storage/.composer',
        'COMPOSER_ALLOW_SUPERUSER' => '1',
    ];
}

/**
 * @param  list<string>  $command
 * @param  array<string, string>  $environment
 * @return array{exit_code: int, output: string}
 */
function deploy_standalone_run_command(array $command, array $environment, string $workingDirectory, int $timeoutSeconds = 900): array
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open(
        $command,
        $descriptors,
        $pipes,
        $workingDirectory,
        $environment,
    );

    if (! is_resource($process)) {
        return [
            'exit_code' => 1,
            'output' => 'Unable to start composer process (proc_open unavailable).',
        ];
    }

    fclose($pipes[0]);

    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $output = '';
    $startedAt = time();

    while (true) {
        $output .= (string) stream_get_contents($pipes[1]);
        $output .= (string) stream_get_contents($pipes[2]);

        $status = proc_get_status($process);

        if (! $status['running']) {
            $output .= (string) stream_get_contents($pipes[1]);
            $output .= (string) stream_get_contents($pipes[2]);
            break;
        }

        if ((time() - $startedAt) >= $timeoutSeconds) {
            proc_terminate($process);
            $output .= "\nProcess timed out after {$timeoutSeconds} seconds.";

            return [
                'exit_code' => 1,
                'output' => $output,
            ];
        }

        usleep(200_000);
    }

    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    return [
        'exit_code' => $exitCode,
        'output' => $output,
    ];
}

/**
 * @param  array<string, mixed>  $payload
 */
function deploy_standalone_json_response(int $httpCode, array $payload): never
{
    http_response_code($httpCode);

    if (! headers_sent()) {
        header('Content-Type: application/json');
    }

    echo json_encode($payload);

    exit;
}
