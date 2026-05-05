<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\HtmlString;

final class PublicStorageFilePreview
{
    public static function render(?string $path): HtmlString|string
    {
        if ($path === null || $path === '') {
            return '-';
        }

        $url = PublicStorageUrl::fromPublicDiskPath($path);
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
}
