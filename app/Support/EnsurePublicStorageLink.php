<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Memastikan symlink public/storage → storage/app/public ada bila memungkinkan.
 *
 * Di shared hosting (cPanel), symlink() dan exec() sering dinonaktifkan.
 * Bila symlink gagal, folder public/storage yang salah dihapus agar permintaan
 * /storage/... ditangani rute Laravel (disk public dengan serve => true).
 */
final class EnsurePublicStorageLink
{
    public static function run(): bool
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        if (! is_dir($target)) {
            return false;
        }

        if (self::isValidLink($link, $target)) {
            return true;
        }

        self::removeBlockingPublicStoragePath($link, $target);

        if (file_exists($link)) {
            return false;
        }

        if (! function_exists('symlink')) {
            return false;
        }

        return self::createLink($link, $target);
    }

    private static function isValidLink(string $link, string $target): bool
    {
        if (! file_exists($link)) {
            return false;
        }

        $resolvedLink = realpath($link);
        $resolvedTarget = realpath($target);

        return $resolvedLink !== false
            && $resolvedTarget !== false
            && $resolvedLink === $resolvedTarget;
    }

    private static function removeBlockingPublicStoragePath(string $link, string $target): void
    {
        if (! file_exists($link)) {
            return;
        }

        if (self::isValidLink($link, $target)) {
            return;
        }

        if (is_link($link)) {
            unlink($link);

            return;
        }

        $junctionTarget = self::readLinkTarget($link);

        if ($junctionTarget !== null) {
            if (PHP_OS_FAMILY === 'Windows' && is_dir($link)) {
                @rmdir($link);
            } else {
                unlink($link);
            }

            return;
        }

        if (is_dir($link)) {
            self::mergeDirectoryInto($link, $target);
            File::deleteDirectory($link);

            return;
        }

        unlink($link);
    }

    private static function readLinkTarget(string $link): ?string
    {
        $target = @readlink($link);

        if ($target === false || $target === '') {
            return null;
        }

        return $target;
    }

    private static function mergeDirectoryInto(string $source, string $target): void
    {
        if (! is_dir($source)) {
            return;
        }

        File::ensureDirectoryExists($target);

        foreach (File::allFiles($source) as $file) {
            $relativePath = $file->getRelativePathname();
            $destination = $target.DIRECTORY_SEPARATOR.$relativePath;

            if (file_exists($destination)) {
                continue;
            }

            File::ensureDirectoryExists(dirname($destination));
            File::move($file->getPathname(), $destination);
        }
    }

    private static function createLink(string $link, string $target): bool
    {
        try {
            return @symlink($target, $link) && self::isValidLink($link, $target);
        } catch (Throwable) {
            return false;
        }
    }
}
