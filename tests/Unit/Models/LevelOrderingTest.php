<?php

use App\Models\Level;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('orders levels as sd smp sma for display', function () {
    Level::factory()->create(['name' => 'SMA']);
    Level::factory()->create(['name' => 'SD']);
    Level::factory()->create(['name' => 'SMP']);

    expect(Level::query()->orderedForDisplay()->pluck('name')->all())
        ->toBe(['SD', 'SMP', 'SMA']);
});
