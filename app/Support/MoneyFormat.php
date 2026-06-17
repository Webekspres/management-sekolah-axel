<?php

namespace App\Support;

class MoneyFormat
{
    /**
     * Format a numeric amount for Indonesian money input display (e.g. 1500000 → "1.500.000").
     */
    public static function formatForInput(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $parsed = self::parse($value);

        if ($parsed === null) {
            return null;
        }

        return number_format($parsed, 0, ',', '.');
    }

    /**
     * Parse user or masked input into a float suitable for database storage.
     */
    public static function parse(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $string = trim((string) $value);

        $string = preg_replace('/[^\d,.]/', '', $string) ?? '';

        if ($string === '') {
            return null;
        }

        if (str_contains($string, ',')) {
            $string = str_replace('.', '', $string);
            $string = str_replace(',', '.', $string);
        } else {
            $string = str_replace('.', '', $string);
        }

        return (float) $string;
    }
}
