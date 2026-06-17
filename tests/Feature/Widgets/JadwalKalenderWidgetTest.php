<?php

use App\Filament\Clusters\Academic\Resources\Schedules\Widgets\JadwalKalenderWidget;

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
