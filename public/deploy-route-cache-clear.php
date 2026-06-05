<?php

/**
 * Clear Laravel route cache without SSH or Composer (cPanel / browser).
 * GET /deploy-route-cache-clear.php?token={DEPLOY_SECRET}
 */

declare(strict_types=1);

header('Content-Type: application/json');

$token = (string) ($_GET['token'] ?? '');
$envPath = dirname(__DIR__).'/.env';

if (! is_file($envPath)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => '.env not found']);
    exit;
}

$deploySecret = null;

foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), 'DEPLOY_SECRET=')) {
        $deploySecret = trim(substr(trim($line), strlen('DEPLOY_SECRET=')), " \t\"'");
        break;
    }
}

if ($deploySecret === null || $deploySecret === '') {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DEPLOY_SECRET not configured in .env']);
    exit;
}

if (! hash_equals($deploySecret, $token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid deploy token']);
    exit;
}

$cacheDir = dirname(__DIR__).'/bootstrap/cache';
$removed = [];

foreach (glob($cacheDir.'/routes*.php') ?: [] as $file) {
    if (is_file($file) && unlink($file)) {
        $removed[] = basename($file);
    }
}

// #region agent log
$logFile = dirname(__DIR__).'/debug-08c1bb.log';
file_put_contents($logFile, json_encode([
    'sessionId' => '08c1bb',
    'runId' => 'route-cache-clear',
    'hypothesisId' => 'D',
    'location' => 'public/deploy-route-cache-clear.php',
    'message' => 'route cache cleared',
    'data' => ['removed' => $removed],
    'timestamp' => (int) (microtime(true) * 1000),
]).PHP_EOL, FILE_APPEND);
// #endregion

echo json_encode([
    'status' => 'success',
    'removed' => $removed,
]);
