<?php

use App\Enums\PaymentStatus;
use App\Models\AcademicYear;
use App\Models\Invoice;
use App\Models\Level;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;

function bootstrapAcademicLevelSession(string $levelId): void
{
    $admin = User::factory()->asAdmin()->create();

    test()->actingAs($admin)
        ->withSession(['active_academic_level_id' => $levelId])
        ->get(route('filament.admin.pages.activity-log'))
        ->assertSuccessful();
}

it('scopes invoices to active academic level', function () {
    $year = AcademicYear::factory()->create();
    $levelSd = Level::factory()->create(['name' => 'SD Scope']);
    $levelSmp = Level::factory()->create(['name' => 'SMP Scope']);

    $classSd = SchoolClass::factory()->create([
        'level_id' => $levelSd->id,
        'academic_year_id' => $year->id,
    ]);
    $classSmp = SchoolClass::factory()->create([
        'level_id' => $levelSmp->id,
        'academic_year_id' => $year->id,
    ]);

    $studentSd = Student::factory()->create(['class_id' => $classSd->id]);
    $studentSmp = Student::factory()->create(['class_id' => $classSmp->id]);

    $invoiceSd = Invoice::factory()->unpaid()->create([
        'student_id' => $studentSd->id,
        'academic_year_id' => $year->id,
    ]);
    $invoiceSmp = Invoice::factory()->unpaid()->create([
        'student_id' => $studentSmp->id,
        'academic_year_id' => $year->id,
    ]);

    bootstrapAcademicLevelSession((string) $levelSd->id);

    $visibleIds = Invoice::query()->pluck('id');

    expect($visibleIds)->toContain($invoiceSd->id)
        ->and($visibleIds)->not->toContain($invoiceSmp->id);
});

it('scopes payments to active academic level via invoice', function () {
    $year = AcademicYear::factory()->create();
    $levelSd = Level::factory()->create(['name' => 'SD Pay']);
    $levelSmp = Level::factory()->create(['name' => 'SMP Pay']);

    $classSd = SchoolClass::factory()->create([
        'level_id' => $levelSd->id,
        'academic_year_id' => $year->id,
    ]);
    $classSmp = SchoolClass::factory()->create([
        'level_id' => $levelSmp->id,
        'academic_year_id' => $year->id,
    ]);

    $studentSd = Student::factory()->create(['class_id' => $classSd->id]);
    $studentSmp = Student::factory()->create(['class_id' => $classSmp->id]);

    $invoiceSd = Invoice::factory()->create([
        'student_id' => $studentSd->id,
        'academic_year_id' => $year->id,
        'status' => PaymentStatus::Pending,
    ]);
    $invoiceSmp = Invoice::factory()->create([
        'student_id' => $studentSmp->id,
        'academic_year_id' => $year->id,
        'status' => PaymentStatus::Pending,
    ]);

    $paymentSd = Payment::query()->withoutInvoiceAcademicLevelScope()->create([
        'invoice_id' => $invoiceSd->id,
        'amount_paid' => $invoiceSd->amount,
        'status' => PaymentStatus::Pending,
    ]);
    $paymentSmp = Payment::query()->withoutInvoiceAcademicLevelScope()->create([
        'invoice_id' => $invoiceSmp->id,
        'amount_paid' => $invoiceSmp->amount,
        'status' => PaymentStatus::Pending,
    ]);

    bootstrapAcademicLevelSession((string) $levelSd->id);

    $visiblePaymentIds = Payment::query()->pluck('id');

    expect($visiblePaymentIds)->toContain($paymentSd->id)
        ->and($visiblePaymentIds)->not->toContain($paymentSmp->id);
});

it('eager loads student and class for scoped invoice', function () {
    $year = AcademicYear::factory()->create();
    $level = Level::factory()->create();
    $class = SchoolClass::factory()->create([
        'level_id' => $level->id,
        'academic_year_id' => $year->id,
        'name' => 'Kelas 7A',
    ]);
    $student = Student::factory()->create(['class_id' => $class->id]);

    Invoice::factory()->unpaid()->create([
        'student_id' => $student->id,
        'academic_year_id' => $year->id,
    ]);

    bootstrapAcademicLevelSession((string) $level->id);

    $invoice = Invoice::query()->with(['student.user', 'student.schoolClass'])->first();

    expect($invoice?->student?->user?->name)->not->toBeEmpty()
        ->and($invoice?->student?->schoolClass?->name)->toBe('Kelas 7A');
});
