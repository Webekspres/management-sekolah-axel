<?php

require dirname(__DIR__).'/../bootstrap/deploy-standalone.php';

test('deploy standalone reads deploy secret from env file', function () {
    $envPath = tempnam(sys_get_temp_dir(), 'deploy-env-');

    try {
        file_put_contents($envPath, "APP_NAME=Laravel\nDEPLOY_SECRET=test-deploy-secret-123\n");

        expect(deploy_standalone_deploy_secret($envPath))
            ->toBe('test-deploy-secret-123');
    } finally {
        if (is_file($envPath)) {
            unlink($envPath);
        }
    }
});

test('deploy standalone returns null when deploy secret is missing', function () {
    $envPath = tempnam(sys_get_temp_dir(), 'deploy-env-');

    try {
        file_put_contents($envPath, "APP_NAME=Laravel\n");

        expect(deploy_standalone_deploy_secret($envPath))->toBeNull();
    } finally {
        if (is_file($envPath)) {
            unlink($envPath);
        }
    }
});

test('deploy standalone token validation rejects invalid token', function () {
    $envPath = tempnam(sys_get_temp_dir(), 'deploy-env-');

    try {
        file_put_contents($envPath, "DEPLOY_SECRET=correct-secret\n");

        $error = deploy_standalone_token_error('wrong-secret', $envPath);

        expect($error)->not->toBeNull()
            ->and($error['http_code'])->toBe(403)
            ->and($error['payload']['message'])->toBe('Invalid deploy token');
    } finally {
        if (is_file($envPath)) {
            unlink($envPath);
        }
    }
});

test('deploy standalone uses configured php cli binary from env', function () {
    $envPath = tempnam(sys_get_temp_dir(), 'deploy-env-');

    try {
        file_put_contents($envPath, "DEPLOY_PHP_CLI=/custom/php-cli\n");

        expect(deploy_standalone_php_binary($envPath))->toBe('/custom/php-cli');
    } finally {
        if (is_file($envPath)) {
            unlink($envPath);
        }
    }
});

test('deploy standalone composer command uses bundled phar when present', function () {
    $pharPath = base_path('composer.phar');
    $hadPhar = is_file($pharPath);

    if (! $hadPhar) {
        file_put_contents($pharPath, '<?php // test stub');
    }

    try {
        $command = deploy_standalone_composer_install_command(base_path(), '/custom/php-cli');

        expect($command[0])->toBe('/custom/php-cli')
            ->and($command[1])->toEndWith('composer.phar')
            ->and($command[2])->toBe('install')
            ->and($command)->toContain('--no-scripts');
    } finally {
        if (! $hadPhar && is_file($pharPath)) {
            unlink($pharPath);
        }
    }
});

test('deploy standalone composer command reports missing phar when not bundled', function () {
    $pharPath = base_path('composer.phar');
    $backup = null;

    if (is_file($pharPath)) {
        $backup = file_get_contents($pharPath);
        unlink($pharPath);
    }

    try {
        $command = deploy_standalone_composer_install_command(base_path(), 'php');

        expect(implode(' ', $command))->toContain('composer.phar not found on server');
    } finally {
        if ($backup !== null) {
            file_put_contents($pharPath, $backup);
        }
    }
});
