<?php

use App\Filament\Clusters\Academic\Resources\Schedules\Widgets\JadwalKalenderWidget;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\ValueObjects\ViewDidMountInfo;

test('backToMonthView resets day view state', function () {
    $widget = new JadwalKalenderWidget;
    $widget->isInDayView = true;

    $widget->backToMonthView();

    expect($widget->isInDayView)->toBeFalse();
});

test('onViewDidMount marks day view when calendar uses timeGridDay', function () {
    $widget = new JadwalKalenderWidget;
    $method = new ReflectionMethod($widget, 'onViewDidMount');

    $method->invoke($widget, new ViewDidMountInfo([
        'view' => [
            'type' => CalendarViewType::TimeGridDay->value,
            'title' => '2 Juni 2026',
            'currentStart' => '2026-06-02T00:00:00Z',
            'currentEnd' => '2026-06-03T00:00:00Z',
            'activeStart' => '2026-06-02T00:00:00Z',
            'activeEnd' => '2026-06-03T00:00:00Z',
        ],
        'tzOffset' => 0,
    ], false));

    expect($widget->isInDayView)->toBeTrue();
});

test('onViewDidMount marks month view when calendar uses dayGridMonth', function () {
    $widget = new JadwalKalenderWidget;
    $widget->isInDayView = true;
    $method = new ReflectionMethod($widget, 'onViewDidMount');

    $method->invoke($widget, new ViewDidMountInfo([
        'view' => [
            'type' => CalendarViewType::DayGridMonth->value,
            'title' => 'Juni 2026',
            'currentStart' => '2026-06-01T00:00:00Z',
            'currentEnd' => '2026-07-01T00:00:00Z',
            'activeStart' => '2026-06-01T00:00:00Z',
            'activeEnd' => '2026-07-01T00:00:00Z',
        ],
        'tzOffset' => 0,
    ], false));

    expect($widget->isInDayView)->toBeFalse();
});
