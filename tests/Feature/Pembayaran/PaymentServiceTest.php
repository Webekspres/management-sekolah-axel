<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\AcademicYear;
use App\Models\Invoice;
use App\Models\Level;
use App\Models\Payment;
use App\Models\Student;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('confirms transfer and sets pending status', function () {
    $student = Student::factory()->create();
    $year = AcademicYear::factory()->create();
    $invoice = Invoice::factory()->unpaid()->create([
        'student_id' => $student->id,
        'academic_year_id' => $year->id,
    ]);

    $payment = app(PaymentService::class)->confirmOfflineTransfer($invoice, $student);

    expect($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->payment_method)->toBe(PaymentMethod::Transfer)
        ->and($invoice->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('verifies pending payment and marks invoice paid', function () {
    $invoice = Invoice::factory()->create(['status' => PaymentStatus::Pending]);
    $payment = Payment::factory()->pending()->create([
        'invoice_id' => $invoice->id,
        'payment_method' => PaymentMethod::Transfer->value,
    ]);

    app(PaymentService::class)->verifyPayment($payment);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->fresh()->paid_at)->not->toBeNull()
        ->and($invoice->fresh()->status)->toBe(PaymentStatus::Paid);
});

it('rejects pending payment', function () {
    $invoice = Invoice::factory()->create(['status' => PaymentStatus::Pending]);
    $payment = Payment::factory()->pending()->create(['invoice_id' => $invoice->id]);

    app(PaymentService::class)->rejectPayment($payment);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Failed)
        ->and($invoice->fresh()->status)->toBe(PaymentStatus::Failed);
});

it('rejects payment and updates invoice even when academic level scope hides invoice', function () {
    $student = Student::factory()->create();
    $invoice = Invoice::factory()->create([
        'student_id' => $student->id,
        'status' => PaymentStatus::Pending,
    ]);
    $payment = Payment::factory()->pending()->create(['invoice_id' => $invoice->id]);

    $wrongLevel = Level::factory()->create();
    session(['active_academic_level_id' => (string) $wrongLevel->id]);

    app(PaymentService::class)->rejectPayment($payment);

    expect(Invoice::query()->withoutGlobalScopes()->find($invoice->id)?->status)
        ->toBe(PaymentStatus::Failed);
});

it('allows student to retry transfer after rejection', function () {
    $student = Student::factory()->create();
    $invoice = Invoice::factory()->unpaid()->create(['student_id' => $student->id]);
    $service = app(PaymentService::class);

    $payment = $service->confirmOfflineTransfer($invoice, $student);
    $service->rejectPayment($payment);

    $service->confirmOfflineTransfer($invoice->fresh(), $student);

    expect($invoice->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('records manual payment and marks invoice paid', function () {
    $invoice = Invoice::factory()->unpaid()->create();

    $payment = app(PaymentService::class)->recordManualPayment(
        $invoice,
        PaymentMethod::Cash,
        PaymentStatus::Paid,
    );

    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->payment_method)->toBe(PaymentMethod::Cash)
        ->and($invoice->fresh()->status)->toBe(PaymentStatus::Paid);
});

it('rejects manual payment on already paid invoice', function () {
    $invoice = Invoice::factory()->paid()->create();

    app(PaymentService::class)->recordManualPayment($invoice, PaymentMethod::Cash);
})->throws(DomainException::class);

it('rejects manual payment with invalid status', function () {
    $invoice = Invoice::factory()->unpaid()->create();

    app(PaymentService::class)->recordManualPayment(
        $invoice,
        PaymentMethod::Cash,
        PaymentStatus::Unpaid,
    );
})->throws(DomainException::class);

it('cancels pending payments when recording manual paid', function () {
    $invoice = Invoice::factory()->create(['status' => PaymentStatus::Pending]);
    $pending = Payment::factory()->pending()->create(['invoice_id' => $invoice->id]);

    app(PaymentService::class)->recordManualPayment(
        $invoice,
        PaymentMethod::Transfer,
        PaymentStatus::Paid,
    );

    expect($pending->fresh()->status)->toBe(PaymentStatus::Failed)
        ->and($invoice->fresh()->status)->toBe(PaymentStatus::Paid);
});

it('throws when initiating online payment while gateway is disabled', function () {
    config(['payment.student_gateway_enabled' => false]);

    $student = Student::factory()->create();
    $invoice = Invoice::factory()->unpaid()->create(['student_id' => $student->id]);

    app(PaymentService::class)->initiateOnlinePayment($invoice, $student, PaymentMethod::Qris);
})->throws(DomainException::class);
