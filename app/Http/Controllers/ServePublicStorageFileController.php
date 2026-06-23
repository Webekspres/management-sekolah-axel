<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Fallback untuk shared hosting tanpa symlink/exec: layani file dari disk public
 * ketika /storage/... tidak tersedia sebagai file statis di public/storage.
 */
final class ServePublicStorageFileController
{
    public function __invoke(Request $request, string $path): StreamedResponse
    {
        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        if ($normalized === '' || str_contains($normalized, '..')) {
            abort(404);
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($normalized)) {
            abort(404);
        }

        return $disk->response($normalized);
    }
}
