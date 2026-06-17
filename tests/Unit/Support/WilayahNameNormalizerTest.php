<?php

use App\Support\Import\WilayahNameNormalizer;

test('normalizer strips common wilayah prefixes before matching', function () {
    $normalizer = new WilayahNameNormalizer;

    expect($normalizer->matches('Kab. Bandung', 'Kabupaten Bandung'))->toBeTrue()
        ->and($normalizer->matches('Kota Bandung', 'Kota Bandung'))->toBeTrue()
        ->and($normalizer->matches('Kec. Coblong', 'Coblong'))->toBeTrue()
        ->and($normalizer->matches('Prov. Jawa Barat', 'Jawa Barat'))->toBeTrue();
});

test('normalizer distinguishes kota from kabupaten with the same base name', function () {
    $normalizer = new WilayahNameNormalizer;

    expect($normalizer->matches('Kota Bandung', 'Kabupaten Bandung'))->toBeFalse()
        ->and($normalizer->matches('Kab. Bandung', 'Kota Bandung'))->toBeFalse()
        ->and($normalizer->matches('Kabupaten Bandung', 'Kota Bandung'))->toBeFalse();
});

test('normalizer requires same canonical name after normalization', function () {
    $normalizer = new WilayahNameNormalizer;

    expect($normalizer->matches('Bandung', 'Surabaya'))->toBeFalse();
});
