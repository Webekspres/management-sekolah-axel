<?php

use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\InvoiceService;

it('resolves amount from custom_spp when set', function () {
    $level = Level::factory()->create(['default_spp' => 250000]);
    $class = SchoolClass::factory()->create(['level_id' => $level->id]);
    $student = Student::factory()->create([
        'class_id' => $class->id,
        'custom_spp' => 175000,
    ]);

    $amount = app(InvoiceService::class)->resolveAmountForStudent($student);

    expect($amount)->toBe(175000.0);
});

it('resolves amount from level default_spp when custom_spp is null', function () {
    $level = Level::factory()->create(['default_spp' => 300000]);
    $class = SchoolClass::factory()->create(['level_id' => $level->id]);
    $student = Student::factory()->create([
        'class_id' => $class->id,
        'custom_spp' => null,
    ]);

    $amount = app(InvoiceService::class)->resolveAmountForStudent($student);

    expect($amount)->toBe(300000.0);
});

it('generates unique invoice numbers', function () {
    $service = app(InvoiceService::class);

    $first = $service->generateInvoiceNumber();
    $second = $service->generateInvoiceNumber();

    expect($first)->not->toBe($second)
        ->and($first)->toStartWith('INV-');
});
