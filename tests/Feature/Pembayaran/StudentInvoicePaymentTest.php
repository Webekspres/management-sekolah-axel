<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Student\Resources\Invoices\Pages\ListInvoices;
use App\Models\Invoice;
use App\Models\Student;
use App\Services\PaymentService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('student only sees own invoices', function () {
    $studentA = Student::factory()->create();
    $studentB = Student::factory()->create();

    $ownInvoice = Invoice::factory()->unpaid()->create(['student_id' => $studentA->id]);
    Invoice::factory()->unpaid()->create(['student_id' => $studentB->id]);

    $this->actingAs($studentA->user);
    Filament::setCurrentPanel(Filament::getPanel('student'));

    Livewire::test(ListInvoices::class)
        ->assertCanSeeTableRecords([$ownInvoice])
        ->assertCanNotSeeTableRecords(Invoice::query()->where('student_id', $studentB->id)->get());
});

it('student can confirm transfer payment from table action', function () {
    $student = Student::factory()->create();
    $invoice = Invoice::factory()->unpaid()->create([
        'student_id' => $student->id,
        'amount' => 250000,
    ]);

    $this->actingAs($student->user);
    Filament::setCurrentPanel(Filament::getPanel('student'));

    Livewire::test(ListInvoices::class)
        ->callAction(TestAction::make('bayar_tagihan')->table($invoice), [
            'payment_method' => PaymentMethod::Transfer->value,
        ])
        ->assertNotified();

    $invoice->refresh();

    expect($invoice->status)->toBe(PaymentStatus::Pending)
        ->and($invoice->payments)->toHaveCount(1)
        ->and($invoice->payments->first()->payment_method)->toBe(PaymentMethod::Transfer);
});

it('student can retry transfer after admin rejection', function () {
    $student = Student::factory()->create();
    $invoice = Invoice::factory()->unpaid()->create(['student_id' => $student->id]);
    $service = app(PaymentService::class);

    $payment = $service->confirmOfflineTransfer($invoice, $student);
    $service->rejectPayment($payment);

    $this->actingAs($student->user);
    Filament::setCurrentPanel(Filament::getPanel('student'));

    $invoice->update(['status' => PaymentStatus::Failed, 'description' => 'SPP Uji Bayar Ulang']);

    Livewire::test(ListInvoices::class)
        ->callAction(TestAction::make('bayar_tagihan')->table($invoice), [
            'payment_method' => PaymentMethod::Transfer->value,
        ])
        ->assertNotified()
        ->assertSee('Menunggu verifikasi')
        ->assertDontSee('Gagal — coba lagi');

    expect($invoice->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('cash payment does not create payment record', function () {
    $student = Student::factory()->create();
    $invoice = Invoice::factory()->unpaid()->create(['student_id' => $student->id]);

    $this->actingAs($student->user);
    Filament::setCurrentPanel(Filament::getPanel('student'));

    Livewire::test(ListInvoices::class)
        ->callAction(TestAction::make('bayar_tagihan')->table($invoice), [
            'payment_method' => PaymentMethod::Cash->value,
        ])
        ->assertNotified();

    expect($invoice->fresh()->status)->toBe(PaymentStatus::Unpaid)
        ->and($invoice->payments)->toHaveCount(0);
});
