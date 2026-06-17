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

/**
 * @return list<string>
 */
function deploy_standalone_php_binary_candidates(string $envPath): array
{
    $candidates = [];

    $configured = deploy_standalone_read_env_value($envPath, 'DEPLOY_PHP_CLI');

    if (is_string($configured) && $configured !== '') {
        $candidates[] = $configured;
    }

    // When triggered from the browser, the PHP binary already running this script
    // is the most reliable choice on cPanel (ea-php paths often return exit 127).
    if (php_sapi_name() !== 'cli') {
        if (defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '') {
            $candidates[] = PHP_BINARY;
        }

        $bindir = defined('PHP_BINDIR') ? PHP_BINDIR : null;

        if (is_string($bindir) && $bindir !== '') {
            $candidates[] = rtrim($bindir, '/').'/php';
        }
    }

    foreach (['84', '83', '82', '81'] as $version) {
        $candidates[] = "/opt/alt/php{$version}/usr/bin/php";
        $candidates[] = "/usr/local/bin/alt-php{$version}";
        $candidates[] = "/usr/local/bin/ea-php{$version}";
        $candidates[] = "/opt/cpanel/ea-php{$version}/root/usr/bin/php";
    }

    $candidates[] = '/usr/local/bin/php';
    $candidates[] = '/usr/bin/php';
    $candidates[] = 'php';

    if (defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '') {
        $candidates[] = PHP_BINARY;
    }

    $unique = [];

    foreach ($candidates as $candidate) {
        if ($candidate === '' || in_array($candidate, $unique, true)) {
            continue;
        }

        $isConfiguredCli = $configured !== null && $configured !== '' && $candidate === $configured;

        if (! $isConfiguredCli && $candidate !== 'php' && ! is_executable($candidate)) {
            continue;
        }

        $unique[] = $candidate;
    }

    if ($unique === []) {
        return ['php'];
    }

    return $unique;
}

function deploy_standalone_php_binary(string $envPath): string
{
    return deploy_standalone_php_binary_candidates($envPath)[0];
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

function deploy_standalone_process_disabled(): bool
{
    if (! function_exists('proc_open')) {
        return true;
    }

    $disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));

    return in_array('proc_open', $disabled, true);
}

/**
 * @param  list<string>  $command
 * @param  array<string, string>  $environment
 * @return array{exit_code: int, output: string}
 */
function deploy_standalone_run_command(array $command, array $environment, string $workingDirectory, int $timeoutSeconds = 900): array
{
    if (deploy_standalone_process_disabled()) {
        return deploy_standalone_run_command_via_shell($command, $environment, $workingDirectory, $timeoutSeconds);
    }

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
        return deploy_standalone_run_command_via_shell($command, $environment, $workingDirectory, $timeoutSeconds);
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

    return [
        'exit_code' => proc_close($process),
        'output' => $output,
    ];
}

/**
 * @param  list<string>  $command
 * @param  array<string, string>  $environment
 * @return array{exit_code: int, output: string}
 */
function deploy_standalone_run_command_via_shell(array $command, array $environment, string $workingDirectory, int $timeoutSeconds = 900): array
{
    if (function_exists('set_time_limit')) {
        set_time_limit($timeoutSeconds);
    }

    $escaped = implode(' ', array_map(
        static fn (string $part): string => escapeshellarg($part),
        $command,
    ));

    $envPrefix = '';

    foreach ($environment as $key => $value) {
        $envPrefix .= escapeshellarg($key).'='.escapeshellarg($value).' ';
    }

    $shellCommand = 'cd '.escapeshellarg($workingDirectory).' && '.$envPrefix.$escaped.' 2>&1';

    if (function_exists('shell_exec') && ! in_array('shell_exec', array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions')))), true)) {
        $output = shell_exec($shellCommand);

        return [
            'exit_code' => $output === null ? 127 : 0,
            'output' => (string) $output,
        ];
    }

    return [
        'exit_code' => 127,
        'output' => 'Unable to run composer (proc_open and shell_exec unavailable).',
    ];
}

/**
 * @param  list<string>  $phpBinaries
 * @return array{exit_code: int, output: string, php_binary: string, attempts: list<array{php_binary: string, exit_code: int, output: string}>}
 */
function deploy_standalone_run_composer_install(string $basePath, array $phpBinaries, array $environment): array
{
    $attempts = [];
    $lastResult = [
        'exit_code' => 127,
        'output' => '',
        'php_binary' => $phpBinaries[0] ?? 'php',
    ];

    foreach ($phpBinaries as $phpBinary) {
        $command = deploy_standalone_composer_install_command($basePath, $phpBinary);
        $result = deploy_standalone_run_command($command, $environment, $basePath);

        $attempts[] = [
            'php_binary' => $phpBinary,
            'exit_code' => $result['exit_code'],
            'output' => $result['output'],
        ];

        $lastResult = [
            'exit_code' => $result['exit_code'],
            'output' => $result['output'],
            'php_binary' => $phpBinary,
        ];

        if ($result['exit_code'] === 0) {
            break;
        }

        // 127 = command not found — try the next PHP binary candidate.
        if ($result['exit_code'] !== 127) {
            break;
        }
    }

    return [
        ...$lastResult,
        'attempts' => $attempts,
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
