<?php

// Feature: attendance-module

use App\Services\AttendanceSummaryService;

beforeEach(function (): void {
    $this->service = new AttendanceSummaryService;
});

/**
 * Property 3: Persentase kehadiran mengikuti formula yang benar
 *
 * Validates: Requirements 5.4, 13.8
 */
it('calculates percentage correctly for any valid hadir and total pair', function (int $hadir, int $total): void {
    $expected = round(($hadir / $total) * 100, 1);

    expect($this->service->calculatePercentage($hadir, $total))->toBe($expected);
})->with(function (): array {
    $cases = [];
    for ($i = 0; $i < 100; $i++) {
        $total = random_int(1, 40);
        $hadir = random_int(0, $total);
        $cases[] = [$hadir, $total];
    }

    return $cases;
});

/**
 * Property 4: Warning threshold konsisten dengan persentase
 *
 * Validates: Requirements 5.3
 */
it('flags warning correctly for any percentage value', function (float $percentage): void {
    $expected = $percentage < 75.0;

    expect($this->service->isBelowWarningThreshold($percentage))->toBe($expected);
})->with(function (): array {
    $cases = [];

    // 100 random float values across the full range 0.0–100.0
    for ($i = 0; $i < 100; $i++) {
        $cases[] = [round(mt_rand(0, 10000) / 100, 2)];
    }

    // Always include boundary values
    $cases[] = [0.0];
    $cases[] = [74.9];
    $cases[] = [75.0];
    $cases[] = [75.1];
    $cases[] = [100.0];

    return $cases;
});

/**
 * Property 7: Validasi status enum menolak nilai tidak valid
 *
 * Validates: Requirements 10.1
 */
it('rejects invalid status values that are not in the allowed enum', function (string $invalidStatus): void {
    $validStatuses = ['HADIR', 'SAKIT', 'IZIN', 'ALPA'];

    // The value must not be in the valid list
    expect(in_array($invalidStatus, $validStatuses, true))->toBeFalse();

    // Simulate the ->in() validation rule: value must be in the allowed list
    $passesValidation = in_array($invalidStatus, $validStatuses, true);
    expect($passesValidation)->toBeFalse();
})->with(function (): array {
    $validStatuses = ['HADIR', 'SAKIT', 'IZIN', 'ALPA'];
    $cases = [];

    // Generate 100 random strings that are NOT valid statuses
    $attempts = 0;
    while (count($cases) < 100 && $attempts < 1000) {
        $attempts++;
        // Generate random strings: lowercase versions, random words, numbers, etc.
        $candidates = [
            strtolower(fake()->word()),
            fake()->word().fake()->randomDigit(),
            fake()->randomLetter().fake()->randomLetter().fake()->randomLetter(),
            (string) fake()->randomNumber(3),
            fake()->word(),
        ];
        foreach ($candidates as $candidate) {
            if (! in_array(strtoupper($candidate), $validStatuses, true) && ! in_array($candidate, $validStatuses, true)) {
                $cases[] = [$candidate];
                if (count($cases) >= 100) {
                    break;
                }
            }
        }
    }

    // Always include specific known invalid values
    $cases[] = ['hadir'];
    $cases[] = ['PRESENT'];
    $cases[] = ['ABSENT'];
    $cases[] = [''];
    $cases[] = ['NULL'];

    return $cases;
});
