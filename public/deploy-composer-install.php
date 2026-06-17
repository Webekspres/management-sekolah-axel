<?php

/**
 * Install Composer dependencies without SSH or a booted Laravel app (cPanel / browser).
 * GET /deploy-composer-install.php?token={DEPLOY_SECRET}
 *
 * Use when vendor/ is missing (FTP excludes vendor/). After success, open /up then /deploy/{token}/release.
 */

declare(strict_types=1);

require dirname(__DIR__).'/bootstrap/deploy-standalone.php';

ignore_user_abort(true);

if (function_exists('set_time_limit')) {
    set_time_limit(900);
}

$token = (string) ($_GET['token'] ?? '');
$basePath = deploy_standalone_base_path();
$envPath = $basePath.'/.env';

$tokenError = deploy_standalone_token_error($token, $envPath);

if ($tokenError !== null) {
    deploy_standalone_json_response($tokenError['http_code'], $tokenError['payload']);
}

$phpBinary = deploy_standalone_php_binary($envPath);
$command = deploy_standalone_composer_install_command($basePath, $phpBinary);
$environment = deploy_standalone_composer_environment($basePath);

if (! is_dir($environment['COMPOSER_HOME'])) {
    mkdir($environment['COMPOSER_HOME'], 0775, true);
}

$result = deploy_standalone_run_command($command, $environment, $basePath);

$success = $result['exit_code'] === 0;

deploy_standalone_json_response($success ? 200 : 500, [
    'status' => $success ? 'success' : 'error',
    'command' => 'composer install --no-dev',
    'php_binary' => $phpBinary,
    'uses_bundled_phar' => is_file($basePath.'/composer.phar'),
    'exit_code' => $result['exit_code'],
    'output' => $result['output'],
]);
