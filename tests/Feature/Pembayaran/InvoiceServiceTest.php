<?php

use App\Models\AcademicYear;
use App\Models\Invoice;
use App\Models\Level;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

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

it('throws when creating duplicate billing period invoice', function () {
    $student = Student::factory()->create();
    $year = AcademicYear::factory()->create();
    $dueDate = Carbon::parse('2026-03-15');
    $service = app(InvoiceService::class);

    $service->createForStudent($student, $year, 'SPP Maret 2026', $dueDate);

    $service->createForStudent($student, $year, 'SPP Bulan Maret', $dueDate);
})->throws(ValidationException::class);

it('throws when invoice amount is zero', function () {
    $level = Level::factory()->create(['default_spp' => 0]);
    $class = SchoolClass::factory()->create(['level_id' => $level->id]);
    $student = Student::factory()->create(['class_id' => $class->id, 'custom_spp' => null]);
    $year = AcademicYear::factory()->create();

    app(InvoiceService::class)->createForStudent(
        $student,
        $year,
        'SPP Test',
        Carbon::parse('2026-04-01'),
    );
})->throws(ValidationException::class);

it('bulk generate skips duplicate billing periods', function () {
    $year = AcademicYear::factory()->create();
    $class = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
    $student = Student::factory()->create(['class_id' => $class->id]);
    $dueDate = Carbon::parse('2026-05-10');

    Invoice::factory()->unpaid()->create([
        'student_id' => $student->id,
        'academic_year_id' => $year->id,
        'billing_period' => '2026-05',
        'due_date' => $dueDate->toDateString(),
    ]);

    $result = app(InvoiceService::class)->bulkGenerateForSchoolClass(
        $class->fresh(),
        $year,
        'SPP Mei 2026',
        $dueDate,
    );

    expect($result)->toBe(['created' => 0, 'skipped' => 1]);
});

it('detects locked invoice when paid or has payments', function () {
    $paid = Invoice::factory()->paid()->create();
    $withPayment = Invoice::factory()->unpaid()->create();
    Payment::factory()->pending()->create(['invoice_id' => $withPayment->id]);
    $fresh = Invoice::factory()->unpaid()->create();

    expect($paid->isLockedForEditing())->toBeTrue()
        ->and($withPayment->fresh()->isLockedForEditing())->toBeTrue()
        ->and($fresh->isLockedForEditing())->toBeFalse();
});
