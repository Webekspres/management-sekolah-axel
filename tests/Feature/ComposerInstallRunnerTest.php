<?php

use App\Support\ComposerInstallRunner;

test('composer install command uses bundled phar when present', function () {
    $pharPath = base_path('composer.phar');
    $hadPhar = is_file($pharPath);

    if (! $hadPhar) {
        file_put_contents($pharPath, '<?php // test stub');
    }

    try {
        expect(ComposerInstallRunner::usesBundledPhar())->toBeTrue();

        $command = ComposerInstallRunner::command();

        expect($command[0])->toBe(ComposerInstallRunner::phpBinary())
            ->and($command[1])->toEndWith('composer.phar')
            ->and($command[2])->toBe('install');
    } finally {
        if (! $hadPhar && is_file($pharPath)) {
            unlink($pharPath);
        }
    }
});

test('composer install command uses configured php cli binary', function () {
    config(['app.deploy_php_cli' => '/custom/php-cli']);

    $pharPath = base_path('composer.phar');
    $hadPhar = is_file($pharPath);

    if (! $hadPhar) {
        file_put_contents($pharPath, '<?php // test stub');
    }

    try {
        expect(ComposerInstallRunner::phpBinary())->toBe('/custom/php-cli')
            ->and(ComposerInstallRunner::command()[0])->toBe('/custom/php-cli');
    } finally {
        config(['app.deploy_php_cli' => null]);

        if (! $hadPhar && is_file($pharPath)) {
            unlink($pharPath);
        }
    }
});

test('composer install command reports missing phar when not bundled', function () {
    $pharPath = base_path('composer.phar');
    $backup = null;

    if (is_file($pharPath)) {
        $backup = file_get_contents($pharPath);
        unlink($pharPath);
    }

    try {
        expect(ComposerInstallRunner::usesBundledPhar())->toBeFalse();

        $command = ComposerInstallRunner::command();

        expect(implode(' ', $command))->toContain('composer.phar not found on server');
    } finally {
        if ($backup !== null) {
            file_put_contents($pharPath, $backup);
        }
    }
});
