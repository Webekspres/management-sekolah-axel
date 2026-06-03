<?php

namespace App\Support\Import;

class WilayahNameNormalizer
{
    /**
     * @var array<int, string>
     */
    private const PREFIXES = [
        'provinsi',
        'prov.',
        'prov',
        'kabupaten',
        'kab.',
        'kab',
        'kota',
        'kecamatan',
        'kec.',
        'kec',
        'kelurahan',
        'kel.',
        'kel',
        'desa',
    ];

    public function normalize(string $name): string
    {
        $normalized = mb_strtolower(preg_replace('/\s+/', ' ', trim($name)) ?? '');

        foreach (self::PREFIXES as $prefix) {
            if (str_starts_with($normalized, $prefix.' ')) {
                $normalized = trim(substr($normalized, strlen($prefix)));
            }
        }

        return preg_replace('/\s+/', ' ', $normalized) ?? '';
    }

    public function matches(string $input, string $canonical): bool
    {
        return $this->normalize($input) === $this->normalize($canonical);
    }
}
