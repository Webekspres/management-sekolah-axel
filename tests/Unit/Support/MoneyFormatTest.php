<?php

use App\Support\MoneyFormat;

it('formats integers with Indonesian thousand separators', function () {
    expect(MoneyFormat::formatForInput(1500000))->toBe('1.500.000')
        ->and(MoneyFormat::formatForInput('250000'))->toBe('250.000');
});

it('parses plain and masked Indonesian money strings', function () {
    expect(MoneyFormat::parse(1500000))->toBe(1500000.0)
        ->and(MoneyFormat::parse('1.500.000'))->toBe(1500000.0)
        ->and(MoneyFormat::parse('250.000'))->toBe(250000.0)
        ->and(MoneyFormat::parse(''))->toBeNull();
});

it('round-trips format and parse', function () {
    $original = 350000.0;

    expect(MoneyFormat::parse(MoneyFormat::formatForInput($original)))->toBe($original);
});

it('parses one and a half million in Indonesian notation', function () {
    expect(MoneyFormat::parse('1.500.000'))->toBe(1500000.0);
});

it('does not truncate Indonesian notation like php floatval', function () {
    expect((float) '1.500.000')->toBe(1.5)
        ->and(MoneyFormat::parse('1.500.000'))->toBe(1500000.0);
});
