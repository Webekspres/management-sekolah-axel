<?php

/**
 * Jalankan ulang test pada file yang mengandung kegagalan run Pest terakhir (pest-last.json).
 *
 * Workflow:
 *   php artisan test     # full suite, memperbarui pest-last.json
 *   composer test:failed # hanya file yang ada test gagal/error
 */
$cacheFile = dirname(__DIR__).'/pest-last.json';

if (! is_file($cacheFile)) {
    fwrite(STDERR, "pest-last.json tidak ditemukan. Jalankan dulu: php artisan test\n");
    exit(1);
}

$cache = json_decode(file_get_contents($cacheFile), true);

if (! is_array($cache)) {
    fwrite(STDERR, "pest-last.json tidak valid.\n");
    exit(1);
}

$issues = array_merge($cache['failures'] ?? [], $cache['error_details'] ?? []);
$failedCount = (int) ($cache['failed'] ?? 0) + (int) ($cache['errors'] ?? 0);

if ($failedCount === 0 || $issues === []) {
    echo "Tidak ada test gagal pada run terakhir ({$cache['passed']}/{$cache['tests']} lulus).\n";
    exit(0);
}

$files = [];

foreach ($issues as $issue) {
    $test = $issue['test'] ?? '';

    if (preg_match('/P\\\\Tests\\\\(.+)::/', $test, $matches)) {
        $files['tests/'.str_replace('\\', '/', $matches[1]).'.php'] = true;
    }
}

$files = array_keys($files);
$existingFiles = array_values(array_filter($files, is_file(...)));

if ($existingFiles === []) {
    fwrite(STDERR, "Tidak bisa menentukan file test dari pest-last.json.\n");
    exit(1);
}

chdir(dirname(__DIR__));

echo "Menjalankan {$failedCount} kegagalan pada ".count($existingFiles)." file test:\n";

foreach ($existingFiles as $file) {
    echo "  - {$file}\n";
}

echo "\n";

$command = escapeshellarg(PHP_BINARY).' artisan test --compact';

foreach ($existingFiles as $file) {
    $command .= ' '.escapeshellarg($file);
}

passthru($command, $exitCode);

exit($exitCode);
