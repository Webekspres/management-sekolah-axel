<?php

use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\Subject;

test('subject factory menggunakan level yang sudah ada', function () {
    Level::factory()->create(['name' => 'SD']);
    Level::factory()->create(['name' => 'SMP']);
    Level::factory()->create(['name' => 'SMA']);

    Subject::factory()->create();

    expect(Level::query()->count())->toBe(3);
});

test('school class factory menggunakan level yang sudah ada', function () {
    Level::factory()->create(['name' => 'SD']);
    Level::factory()->create(['name' => 'SMP']);
    Level::factory()->create(['name' => 'SMA']);

    SchoolClass::factory()->create();

    expect(Level::query()->count())->toBe(3);
});
