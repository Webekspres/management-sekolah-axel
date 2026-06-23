<?php

use App\Support\EnsurePublicStorageLink;
use Illuminate\Support\Facades\File;

test('public storage symlink exists after ensure runs', function () {
    $link = public_path('storage');
    $target = storage_path('app/public');

    File::ensureDirectoryExists($target);

    expect(EnsurePublicStorageLink::run())->toBeTrue()
        ->and(realpath($link))->toBe(realpath($target));
});

test('ensure replaces a regular public storage directory with a symlink', function () {
    if (PHP_OS_FAMILY === 'Windows') {
        test()->markTestSkipped('Symlink replacement test is Linux/staging focused.');
    }

    $link = public_path('storage');
    $target = storage_path('app/public');

    File::ensureDirectoryExists($target);

    if (is_link($link)) {
        unlink($link);
    } elseif (is_dir($link)) {
        File::deleteDirectory($link);
    } elseif (file_exists($link)) {
        unlink($link);
    }

    File::ensureDirectoryExists($link);
    File::put($link.'/orphan.txt', 'orphan');

    expect(is_dir($link) && ! is_link($link))->toBeTrue();

    expect(EnsurePublicStorageLink::run())->toBeTrue()
        ->and(realpath($link))->toBe(realpath($target))
        ->and(file_exists($target.'/orphan.txt'))->toBeTrue();
});

test('ensure returns true when symlink already points to public disk', function () {
    EnsurePublicStorageLink::run();

    expect(EnsurePublicStorageLink::run())->toBeTrue();
});
