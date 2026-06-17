<?php

namespace App\Support\Import;

class WilayahNameNormalizer
{
    /**
     * @var array<string, string>
     */
    private const CITY_TYPE_PREFIXES = [
        'kabupaten' => 'kabupaten',
        'kab.' => 'kabupaten',
        'kab' => 'kabupaten',
        'kota' => 'kota',
    ];

    /**
     * @var array<int, string>
     */
    private const OTHER_PREFIXES = [
        'provinsi',
        'prov.',
        'prov',
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
        return $this->parse($name)['base'];
    }

    /**
     * @return array{type: ?string, base: string}
     */
    public function parse(string $name): array
    {
        $normalized = mb_strtolower(preg_replace('/\s+/', ' ', trim($name)) ?? '');

        $type = null;

        foreach (self::CITY_TYPE_PREFIXES as $prefix => $typeValue) {
            if (str_starts_with($normalized, $prefix.' ')) {
                $type = $typeValue;
                $normalized = trim(substr($normalized, strlen($prefix)));
                break;
            }
        }

        foreach (self::OTHER_PREFIXES as $prefix) {
            if (str_starts_with($normalized, $prefix.' ')) {
                $normalized = trim(substr($normalized, strlen($prefix)));
            }
        }

        return [
            'type' => $type,
            'base' => preg_replace('/\s+/', ' ', $normalized) ?? '',
        ];
    }

    public function matches(string $input, string $canonical): bool
    {
        if (mb_strtolower(trim($input)) === mb_strtolower(trim($canonical))) {
            return true;
        }

        $inputParts = $this->parse($input);
        $canonicalParts = $this->parse($canonical);

        if ($inputParts['base'] !== $canonicalParts['base']) {
            return false;
        }

        if ($inputParts['type'] !== null && $canonicalParts['type'] !== null) {
            return $inputParts['type'] === $canonicalParts['type'];
        }

        return true;
    }
}
