<?php

use App\Support\Import\WilayahNameNormalizer;

test('normalizer strips common wilayah prefixes before matching', function () {
    $normalizer = new WilayahNameNormalizer;

    expect($normalizer->matches('Kab. Bandung', 'Kota Bandung'))->toBeTrue()
        ->and($normalizer->matches('Kec. Coblong', 'Coblong'))->toBeTrue()
        ->and($normalizer->matches('Prov. Jawa Barat', 'Jawa Barat'))->toBeTrue();
});

test('normalizer requires same canonical name after normalization', function () {
    $normalizer = new WilayahNameNormalizer;

    expect($normalizer->matches('Bandung', 'Surabaya'))->toBeFalse();
});
