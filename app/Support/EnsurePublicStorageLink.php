<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Memastikan symlink public/storage → storage/app/public ada.
 * Diperlukan agar URL /storage/... dapat dilayani web server (RPP, materi, rapor, dll.).
 *
 * Di shared hosting, public/storage sering dibuat sebagai folder biasa (bukan symlink).
 * Perintah artisan storage:link --force tidak mengganti folder tersebut.
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
            self::logAttempt($link, $target, false, 'blocking_path_remains');

            return false;
        }

        $linked = self::createLink($link, $target);

        self::logAttempt($link, $target, $linked, $linked ? 'symlink_created' : 'symlink_failed');

        return $linked;
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
            if (@symlink($target, $link)) {
                return self::isValidLink($link, $target);
            }
        } catch (Throwable) {
            // Fall through to artisan.
        }

        Artisan::call('storage:link');

        return self::isValidLink($link, $target);
    }

    private static function logAttempt(string $link, string $target, bool $linked, string $outcome): void
    {
        // #region agent log
        file_put_contents(
            base_path('debug-0f345b.log'),
            json_encode([
                'sessionId' => '0f345b',
                'runId' => 'post-fix',
                'hypothesisId' => 'A',
                'location' => 'EnsurePublicStorageLink.php:run',
                'message' => 'Public storage link repair attempted',
                'data' => [
                    'outcome' => $outcome,
                    'linked' => $linked,
                    'publicSymlinkIsLink' => is_link($link),
                    'publicStorageIsDir' => is_dir($link) && ! is_link($link),
                    'resolvedLink' => file_exists($link) ? realpath($link) : null,
                    'resolvedTarget' => realpath($target),
                    'artisanOutput' => Artisan::output(),
                ],
                'timestamp' => (int) (microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES)."\n",
            FILE_APPEND
        );
        // #endregion
    }
}
