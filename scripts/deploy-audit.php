<?php

/**
 * Audit ukuran payload FTP deploy — menulis NDJSON ke debug-08c1bb.log.
 */
$root = dirname(__DIR__);
$logFile = $root.'/debug-08c1bb.log';
$sessionId = '08c1bb';

$excludePatterns = [
    'vendor',
    'node_modules',
    '.git',
    'tests',
    'storage',
    '.kiro_tmp',
];

function shouldSkip(string $relativePath, array $excludePatterns): bool
{
    foreach ($excludePatterns as $pattern) {
        if (str_starts_with($relativePath, $pattern.'/') || $relativePath === $pattern) {
            return true;
        }
    }

    return false;
}

$counts = [
    'total_files' => 0,
    'vendor_files' => 0,
    'deploy_files' => 0,
    'junk_files' => 0,
    'total_bytes' => 0,
    'deploy_bytes' => 0,
];

$junkSamples = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (! $file->isFile()) {
        continue;
    }

    $absolute = $file->getPathname();
    $relative = str_replace('\\', '/', substr($absolute, strlen($root) + 1));
    $size = $file->getSize();

    $counts['total_files']++;
    $counts['total_bytes'] += $size;

    if (str_starts_with($relative, 'vendor/')) {
        $counts['vendor_files']++;
    }

    if (str_starts_with($relative, '.kiro_tmp/')) {
        $counts['junk_files']++;
        if (count($junkSamples) < 5) {
            $junkSamples[] = $relative;
        }
    }

    if (! shouldSkip($relative, $excludePatterns)) {
        $counts['deploy_files']++;
        $counts['deploy_bytes'] += $size;
    }
}

$payload = [
    'sessionId' => $sessionId,
    'runId' => getenv('GITHUB_RUN_ID') ?: 'local',
    'hypothesisId' => 'B',
    'location' => 'scripts/deploy-audit.php',
    'message' => 'deploy payload audit',
    'data' => array_merge($counts, ['junk_samples' => $junkSamples]),
    'timestamp' => (int) (microtime(true) * 1000),
];

file_put_contents($logFile, json_encode($payload).PHP_EOL, FILE_APPEND);

echo json_encode($payload, JSON_PRETTY_PRINT).PHP_EOL;
