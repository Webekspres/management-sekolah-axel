<?php

declare(strict_types=1);

namespace App\Support;

/**
 * URL untuk file di storage/app/public yang dilayani lewat symlink public/storage.
 * Memakai path relatif ke domain saat ini agar cocok dengan Laragon / vhost (.test)
 * walau APP_URL masih http://localhost.
 */
final class PublicStorageUrl
{
    public static function fromPublicDiskPath(?string $path): string
    {
        if ($path === null || $path === '') {
            return '#';
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        return '/storage/'.$normalized;
    }
}
