<?php

use App\Models\Attendance;
use App\Services\AttendanceSummaryService;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->service = new AttendanceSummaryService;
});

test('calculatePercentage returns correct value', function (): void {
    expect($this->service->calculatePercentage(30, 40))->toBe(75.0);
});

test('calculatePercentage returns 0.0 when total is zero', function (): void {
    expect($this->service->calculatePercentage(0, 0))->toBe(0.0);
});

test('calculatePercentage rounds to one decimal place', function (): void {
    // 1/3 * 100 = 33.333... → 33.3
    expect($this->service->calculatePercentage(1, 3))->toBe(33.3);

    // 2/3 * 100 = 66.666... → 66.7
    expect($this->service->calculatePercentage(2, 3))->toBe(66.7);
});

test('isBelowWarningThreshold returns true when percentage is below 75', function (): void {
    expect($this->service->isBelowWarningThreshold(74.9))->toBeTrue();
    expect($this->service->isBelowWarningThreshold(0.0))->toBeTrue();
    expect($this->service->isBelowWarningThreshold(50.0))->toBeTrue();
});

test('isBelowWarningThreshold returns false when percentage is exactly 75', function (): void {
    expect($this->service->isBelowWarningThreshold(75.0))->toBeFalse();
});

test('isBelowWarningThreshold returns false when percentage is above 75', function (): void {
    expect($this->service->isBelowWarningThreshold(75.1))->toBeFalse();
    expect($this->service->isBelowWarningThreshold(100.0))->toBeFalse();
});

test('calculateStats returns correct counts per status', function (): void {
    $attendances = Collection::make([
        new Attendance(['status' => 'HADIR']),
        new Attendance(['status' => 'HADIR']),
        new Attendance(['status' => 'HADIR']),
        new Attendance(['status' => 'SAKIT']),
        new Attendance(['status' => 'IZIN']),
        new Attendance(['status' => 'ALPA']),
        new Attendance(['status' => 'ALPA']),
    ]);

    $stats = $this->service->calculateStats($attendances);

    expect($stats['total'])->toBe(7)
        ->and($stats['hadir'])->toBe(3)
        ->and($stats['sakit'])->toBe(1)
        ->and($stats['izin'])->toBe(1)
        ->and($stats['alpa'])->toBe(2)
        ->and($stats['percentage'])->toBe(round((3 / 7) * 100, 1));
});

test('calculateStats returns zero percentage for empty collection', function (): void {
    $stats = $this->service->calculateStats(Collection::make([]));

    expect($stats['total'])->toBe(0)
        ->and($stats['hadir'])->toBe(0)
        ->and($stats['sakit'])->toBe(0)
        ->and($stats['izin'])->toBe(0)
        ->and($stats['alpa'])->toBe(0)
        ->and($stats['percentage'])->toBe(0.0);
});

// ─────────────────────────────────────────────────────────────────────────────
// Tests untuk metode baru: getSemesterMonths (pure logic, no DB)
// ─────────────────────────────────────────────────────────────────────────────

test('semester 1 menggunakan bulan Juli-Desember', function (): void {
    $service = new AttendanceSummaryService;
    $months = $service->getSemesterMonths(1);

    expect($months)->toBe([7, 8, 9, 10, 11, 12]);
});

test('semester 2 menggunakan bulan Januari-Juni', function (): void {
    $service = new AttendanceSummaryService;
    $months = $service->getSemesterMonths(2);

    expect($months)->toBe([1, 2, 3, 4, 5, 6]);
});
