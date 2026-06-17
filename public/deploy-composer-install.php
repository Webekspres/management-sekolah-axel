<?php

/**
 * Install Composer dependencies without SSH or a booted Laravel app (cPanel / browser).
 * GET /deploy-composer-install.php?token={DEPLOY_SECRET}
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

$phpBinaries = deploy_standalone_php_binary_candidates($envPath);
$environment = deploy_standalone_composer_environment($basePath);

if (! is_dir($environment['COMPOSER_HOME'])) {
    mkdir($environment['COMPOSER_HOME'], 0775, true);
}

$result = deploy_standalone_run_composer_install($basePath, $phpBinaries, $environment);

$success = $result['exit_code'] === 0;

deploy_standalone_json_response($success ? 200 : 500, [
    'status' => $success ? 'success' : 'error',
    'command' => 'composer install --no-dev',
    'php_binary' => $result['php_binary'],
    'php_sapi' => php_sapi_name(),
    'php_binary_constant' => defined('PHP_BINARY') ? PHP_BINARY : null,
    'uses_bundled_phar' => is_file($basePath.'/composer.phar'),
    'composer_phar' => $basePath.'/composer.phar',
    'exit_code' => $result['exit_code'],
    'output' => $result['output'],
    'attempts' => $result['attempts'],
]);
