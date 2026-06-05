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
    $envPath = tempnam(sys_get_temp_dir(), 'deploy-env-');

    try {
        file_put_contents($envPath, "APP_NAME=Laravel\nDEPLOY_SECRET=test-deploy-secret-123\n");

        expect(DeployRouteCacheClearer::readDeploySecretFromEnv($envPath))
            ->toBe('test-deploy-secret-123');
    } finally {
        if (is_file($envPath)) {
            unlink($envPath);
        }
    }
});

test('deploy route cache clearer returns null when deploy secret is missing', function () {
    $envPath = tempnam(sys_get_temp_dir(), 'deploy-env-');

    try {
        file_put_contents($envPath, "APP_NAME=Laravel\n");

        expect(DeployRouteCacheClearer::readDeploySecretFromEnv($envPath))->toBeNull();
    } finally {
        if (is_file($envPath)) {
            unlink($envPath);
        }
    }
});
