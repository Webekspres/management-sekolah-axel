<?php

use App\Filament\Clusters\Academic\Resources\Schedules\Widgets\JadwalKalenderWidget;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

// ─────────────────────────────────────────────────────────────────────────────
// buildEventTitle()
// ─────────────────────────────────────────────────────────────────────────────

test('buildEventTitle menghasilkan format yang benar untuk satu kelas', function () {
    $widget = new JadwalKalenderWidget;
    $method = new ReflectionMethod($widget, 'buildEventTitle');

    $title = $method->invoke($widget, '08:00:00', 'Matematika', ['X-A']);

    expect($title)->toBe('08:00: Matematika - X-A');
});

test('buildEventTitle menghasilkan format yang benar untuk beberapa kelas', function () {
    $widget = new JadwalKalenderWidget;
    $method = new ReflectionMethod($widget, 'buildEventTitle');

    $title = $method->invoke($widget, '09:30:00', 'Bahasa Indonesia', ['X-C', 'X-A', 'X-B']);

    expect($title)->toBe('09:30: Bahasa Indonesia - X-A, X-B, X-C');
});

test('buildEventTitle mengurutkan nama kelas secara alfabetis', function () {
    $widget = new JadwalKalenderWidget;
    $method = new ReflectionMethod($widget, 'buildEventTitle');

    $title = $method->invoke($widget, '07:00:00', 'IPA', ['XII-B', 'X-A', 'XI-C']);

    expect($title)->toBe('07:00: IPA - X-A, XI-C, XII-B');
});

test('buildEventTitle memformat jam dengan benar dari berbagai format waktu', function (string $startTime, string $expectedTime) {
    $widget = new JadwalKalenderWidget;
    $method = new ReflectionMethod($widget, 'buildEventTitle');

    $title = $method->invoke($widget, $startTime, 'Fisika', ['X-A']);

    expect($title)->toStartWith($expectedTime.': Fisika - X-A');
})->with([
    'jam pagi' => ['07:30:00', '07:30'],
    'jam siang' => ['13:00:00', '13:00'],
    'jam sore' => ['15:45:00', '15:45'],
]);

// ─────────────────────────────────────────────────────────────────────────────
// resolveConcreteDate()
// ─────────────────────────────────────────────────────────────────────────────

test('resolveConcreteDate mengembalikan tanggal yang benar untuk hari yang ada dalam periode', function () {
    $widget = new JadwalKalenderWidget;
    $method = new ReflectionMethod($widget, 'resolveConcreteDate');

    // Monday = 1 in Carbon convention
    $monday = Carbon::parse('next monday');
    $period = CarbonPeriod::create($monday->copy()->subDay(), $monday->copy()->addDay());

    $result = $method->invoke($widget, 1, $period);

    expect($result)->not->toBeNull()
        ->and($result->dayOfWeek)->toBe(1);
});

test('resolveConcreteDate mengembalikan null jika hari tidak ada dalam periode', function () {
    $widget = new JadwalKalenderWidget;
    $method = new ReflectionMethod($widget, 'resolveConcreteDate');

    // Period of only 1 day (Monday), ask for Sunday (0)
    $monday = Carbon::parse('next monday');
    $period = CarbonPeriod::create($monday, $monday);

    $result = $method->invoke($widget, 0, $period);

    expect($result)->toBeNull();
});

test('resolveConcreteDate mengembalikan null untuk dayOfWeek yang tidak valid', function (int $invalidDay) {
    $widget = new JadwalKalenderWidget;
    $method = new ReflectionMethod($widget, 'resolveConcreteDate');

    $period = CarbonPeriod::create(Carbon::today(), Carbon::today()->addWeek());

    $result = $method->invoke($widget, $invalidDay, $period);

    expect($result)->toBeNull();
})->with([
    'negatif' => [-1],
    'terlalu besar' => [7],
    'sangat besar' => [100],
]);

test('resolveConcreteDate mengembalikan tanggal yang berada dalam periode', function () {
    $widget = new JadwalKalenderWidget;
    $method = new ReflectionMethod($widget, 'resolveConcreteDate');

    $start = Carbon::parse('2025-01-06'); // Monday
    $end = Carbon::parse('2025-01-12');   // Sunday
    $period = CarbonPeriod::create($start, $end);

    // Wednesday = 3
    $result = $method->invoke($widget, 3, $period);

    expect($result)->not->toBeNull()
        ->and($result->dayOfWeek)->toBe(3)
        ->and($result->toDateString())->toBe('2025-01-08');
});

test('resolveConcreteDate mengembalikan tanggal pertama yang cocok dalam periode', function () {
    $widget = new JadwalKalenderWidget;
    $method = new ReflectionMethod($widget, 'resolveConcreteDate');

    // Two-week period — should return the FIRST Monday
    $start = Carbon::parse('2025-01-06'); // Monday
    $end = Carbon::parse('2025-01-19');   // Sunday
    $period = CarbonPeriod::create($start, $end);

    $result = $method->invoke($widget, 1, $period);

    expect($result)->not->toBeNull()
        ->and($result->toDateString())->toBe('2025-01-06');
});
