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

        expect($command[1])->toEndWith('composer.phar')
            ->and($command[2])->toBe('install');
    } finally {
        if (! $hadPhar && is_file($pharPath)) {
            unlink($pharPath);
        }
    }
});

test('composer install command falls back to system composer without phar', function () {
    $pharPath = base_path('composer.phar');
    $backup = null;

    if (is_file($pharPath)) {
        $backup = file_get_contents($pharPath);
        unlink($pharPath);
    }

    try {
        expect(ComposerInstallRunner::usesBundledPhar())->toBeFalse();
        expect(ComposerInstallRunner::command()[0])->toBe('composer');
    } finally {
        if ($backup !== null) {
            file_put_contents($pharPath, $backup);
        }
    }
});
