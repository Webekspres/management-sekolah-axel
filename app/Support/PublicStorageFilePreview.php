<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

final class PublicStorageFilePreview
{
    public static function render(?string $path): HtmlString|string
    {
        if ($path === null || $path === '') {
            return '-';
        }

        $url = PublicStorageUrl::fromPublicDiskPath($path);
        $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');

        // #region agent log
        file_put_contents(
            base_path('debug-0f345b.log'),
            json_encode([
                'sessionId' => '0f345b',
                'runId' => 'post-fix-verify',
                'hypothesisId' => 'A',
                'location' => 'PublicStorageFilePreview.php:render',
                'message' => 'RPP preview link generated',
                'data' => [
                    'dbPath' => $path,
                    'generatedUrl' => $url,
                    'publicDiskExists' => Storage::disk('public')->exists($normalizedPath),
                    'publicDiskServeEnabled' => (bool) config('filesystems.disks.public.serve'),
                    'localDiskServeEnabled' => (bool) config('filesystems.disks.local.serve'),
                    'publicStoragePathExists' => file_exists(public_path('storage')),
                    'servedViaLaravelRoute' => ! file_exists(public_path('storage/'.$normalizedPath))
                        && Storage::disk('public')->exists($normalizedPath)
                        && (bool) config('filesystems.disks.public.serve'),
                ],
                'timestamp' => (int) (microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES)."\n",
            FILE_APPEND
        );
        // #endregion

        $fileName = basename($path);
        $escapedUrl = e($url);
        $escapedFileName = e($fileName);

        $linkHtml = '<a href="'.$escapedUrl.'" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:underline">Buka file: '.$escapedFileName.'</a>';

        if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) !== 'pdf') {
            return new HtmlString($linkHtml);
        }

        // Hindari <iframe src="...pdf"> di dalam halaman Livewire/Filament SPA: morfing DOM + muat ulang PDF
        // berulang sering memicu layar kosong; pratinjau cukup di tab terpisah (stabil di semua browser).
        $previewInNewTab = '<a href="'.$escapedUrl.'" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:underline">Pratinjau PDF di tab baru</a>';

        return new HtmlString(
            '<div class="space-y-3">'.
            '<div>'.$linkHtml.'</div>'.
            '<p class="text-sm text-gray-600 dark:text-gray-400">Pratinjau tidak ditampilkan inline di sini agar halaman edit RPP tetap andal.</p>'.
            '<div>'.$previewInNewTab.'</div>'.
            '</div>'
        );
    }

    /**
     * Render pratinjau file secara inline — digunakan di panel preview samping form.
     * PDF ditampilkan via <iframe> dengan tinggi penuh; file non-PDF ditampilkan sebagai link.
     */
    public static function renderInline(?string $path): HtmlString|string
    {
        if ($path === null || $path === '') {
            return new HtmlString(
                '<p class="text-sm text-gray-500 dark:text-gray-400">Belum ada file yang diunggah.</p>'
            );
        }

        $url = PublicStorageUrl::fromPublicDiskPath($path);
        $fileName = basename($path);
        $escapedUrl = e($url);
        $escapedFileName = e($fileName);
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $openLink = '<a href="'.$escapedUrl.'" target="_blank" rel="noopener noreferrer" '
            .'class="inline-flex items-center gap-1 text-sm text-primary-600 hover:underline mb-3">'
            .'<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>'
            .$escapedFileName
            .'</a>';

        if ($extension === 'pdf') {
            return new HtmlString(
                '<div class="flex flex-col h-full">'
                .$openLink
                .'<iframe src="'.$escapedUrl.'" class="w-full rounded border border-gray-200 dark:border-gray-700" style="height: 70vh; min-height: 500px;" title="Pratinjau '.$escapedFileName.'"></iframe>'
                .'</div>'
            );
        }

        return new HtmlString(
            '<div class="space-y-2">'
            .$openLink
            .'<p class="text-sm text-gray-500 dark:text-gray-400">Pratinjau hanya tersedia untuk file PDF. Klik link di atas untuk membuka file.</p>'
            .'</div>'
        );
    }
}
