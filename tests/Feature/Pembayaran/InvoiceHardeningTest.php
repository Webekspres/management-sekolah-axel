<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('invoice billing period is derived from due date', function () {
    $date = Carbon::parse('2026-08-20');

    expect(Invoice::billingPeriodFromDate($date))->toBe('2026-08');
});

it('prevents second manual payment on paid invoice via service', function () {
    $invoice = Invoice::factory()->paid()->create();
    $service = app(PaymentService::class);

    $service->recordManualPayment($invoice, PaymentMethod::Cash);
})->throws(DomainException::class);

it('marks invoice as locked when payment exists', function () {
    $invoice = Invoice::factory()->unpaid()->create();
    Payment::factory()->pending()->create(['invoice_id' => $invoice->id]);

    expect($invoice->fresh()->isLockedForEditing())->toBeTrue();
});

it('invoice payable only when unpaid or failed', function () {
    expect(Invoice::factory()->unpaid()->create()->isPayableByStudent())->toBeTrue()
        ->and(Invoice::factory()->create(['status' => PaymentStatus::Failed])->isPayableByStudent())->toBeTrue()
        ->and(Invoice::factory()->paid()->create()->isPayableByStudent())->toBeFalse()
        ->and(Invoice::factory()->create(['status' => PaymentStatus::Pending])->isPayableByStudent())->toBeFalse();
});
