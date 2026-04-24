<?php

namespace App\Support;

use Illuminate\Support\Str;

class RichText
{
    public static function toPlainText(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        $normalizedHtml = (string) str($html)
            ->replace(['<br>', '<br/>', '<br />'], "\n")
            ->replaceMatches('/<\/(p|div|h[1-6]|tr)>/i', "\n")
            ->replaceMatches('/<li[^>]*>/i', '• ')
            ->replaceMatches('/<\/li>/i', "\n");

        $text = strip_tags($normalizedHtml);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\r\n|\r/", "\n", $text) ?? $text;
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    public static function display(?string $html, string $fallback = '-'): string
    {
        $text = self::toPlainText($html);

        return $text !== '' ? $text : $fallback;
    }

    public static function excerpt(?string $html, int $limit = 80, string $fallback = '-'): string
    {
        $text = self::toPlainText($html);

        if ($text === '') {
            return $fallback;
        }

        return Str::limit($text, $limit);
    }
}
