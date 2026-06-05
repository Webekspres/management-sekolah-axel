<?php

namespace App\Support;

class DeployRouteCacheClearer
{
    public static function readDeploySecretFromEnv(string $envPath): ?string
    {
        if (! is_file($envPath)) {
            return null;
        }

        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), 'DEPLOY_SECRET=')) {
                return trim(substr(trim($line), strlen('DEPLOY_SECRET=')), " \t\"'");
            }
        }

        return null;
    }

    /**
     * @return list<string> basenames of removed route cache files
     */
    public static function clearRouteCacheFiles(string $cacheDir): array
    {
        $removed = [];

        foreach (glob($cacheDir.'/routes*.php') ?: [] as $file) {
            if (is_file($file) && unlink($file)) {
                $removed[] = basename($file);
            }
        }

        return $removed;
    }
}
