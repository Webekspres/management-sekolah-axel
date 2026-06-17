<?php

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows super admin to verify and reject payments', function () {
    $admin = User::factory()->asAdmin()->create();
    $payment = Payment::factory()->pending()->create();

    expect($admin->can('verify', $payment))->toBeTrue()
        ->and($admin->can('reject', $payment))->toBeTrue();
});

it('denies kepala sekolah from verifying payments', function () {
    $kepsek = User::factory()->asKepalaSekolah()->create();
    $payment = Payment::factory()->pending()->create();

    expect($kepsek->can('verify', $payment))->toBeFalse()
        ->and($kepsek->can('reject', $payment))->toBeFalse();
});

it('allows super admin to record manual payment on unpaid invoice', function () {
    $admin = User::factory()->asAdmin()->create();
    $invoice = Invoice::factory()->unpaid()->create();

    expect($admin->can('recordManual', $invoice))->toBeTrue();
});

it('denies manual payment on paid invoice', function () {
    $admin = User::factory()->asAdmin()->create();
    $invoice = Invoice::factory()->paid()->create();

    expect($admin->can('recordManual', $invoice))->toBeFalse();
});

it('denies deleting paid invoices', function () {
    $admin = User::factory()->asAdmin()->create();
    $invoice = Invoice::factory()->paid()->create();

    expect($admin->can('delete', $invoice))->toBeFalse();
});

it('denies deleting invoices with payment history', function () {
    $admin = User::factory()->asAdmin()->create();
    $invoice = Invoice::factory()->unpaid()->create();
    Payment::factory()->pending()->create(['invoice_id' => $invoice->id]);

    expect($admin->can('delete', $invoice))->toBeFalse();
});

it('allows deleting unpaid invoice without payments', function () {
    $admin = User::factory()->asAdmin()->create();
    $invoice = Invoice::factory()->unpaid()->create();

    expect($admin->can('delete', $invoice))->toBeTrue();
});
