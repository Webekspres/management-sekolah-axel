<?php

/**
 * Clear Laravel route cache without SSH or Composer (cPanel / browser).
 * GET /deploy-route-cache-clear.php?token={DEPLOY_SECRET}
 */

declare(strict_types=1);

require dirname(__DIR__).'/bootstrap/deploy-standalone.php';

$token = (string) ($_GET['token'] ?? '');
$basePath = deploy_standalone_base_path();
$envPath = $basePath.'/.env';

$tokenError = deploy_standalone_token_error($token, $envPath);

if ($tokenError !== null) {
    deploy_standalone_json_response($tokenError['http_code'], $tokenError['payload']);
}

$cacheDir = $basePath.'/bootstrap/cache';
$removed = [];

foreach (glob($cacheDir.'/routes*.php') ?: [] as $file) {
    if (is_file($file) && unlink($file)) {
        $removed[] = basename($file);
    }
}

deploy_standalone_json_response(200, [
    'status' => 'success',
    'removed' => $removed,
]);
