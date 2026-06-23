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
        $result = $text !== '' ? $text : $fallback;

        // #region agent log
        file_put_contents(
            base_path('debug-cb6ab0.log'),
            json_encode([
                'sessionId' => 'cb6ab0',
                'runId' => 'pre-fix',
                'hypothesisId' => 'C,E',
                'location' => 'RichText.php:display',
                'message' => 'RichText::display called',
                'data' => [
                    'inputContainsHtml' => is_string($html) && $html !== strip_tags($html),
                    'inputPreview' => is_string($html) ? mb_substr($html, 0, 120) : null,
                    'outputPreview' => mb_substr($result, 0, 120),
                    'outputIsPlainText' => $result === strip_tags($result),
                ],
                'timestamp' => (int) (microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES)."\n",
            FILE_APPEND
        );
        // #endregion

        return $result;
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
