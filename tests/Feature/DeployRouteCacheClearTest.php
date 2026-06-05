<?php

use App\Support\DeployRouteCacheClearer;

test('deploy route cache clearer removes route cache files', function () {
    $cacheDir = base_path('bootstrap/cache');
    $cacheFile = $cacheDir.'/routes-test-clear.php';

    file_put_contents($cacheFile, '<?php // test');

    try {
        $removed = DeployRouteCacheClearer::clearRouteCacheFiles($cacheDir);

        expect($removed)->toContain('routes-test-clear.php')
            ->and(is_file($cacheFile))->toBeFalse();
    } finally {
        if (is_file($cacheFile)) {
            unlink($cacheFile);
        }
    }
});

test('deploy route cache clearer reads deploy secret from env file', function () {
    $envPath = base_path('.env');

    expect(DeployRouteCacheClearer::readDeploySecretFromEnv($envPath))->not->toBeNull();
});
