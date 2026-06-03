<?php

use App\Enums\PaymentMethod;

it('has non-empty labels for all methods', function () {
    foreach (PaymentMethod::cases() as $method) {
        expect($method->label())->not->toBeEmpty();
    }
});

it('identifies gateway methods correctly', function () {
    expect(PaymentMethod::Qris->requiresGateway())->toBeTrue()
        ->and(PaymentMethod::Transfer->requiresGateway())->toBeFalse()
        ->and(PaymentMethod::Cash->requiresGateway())->toBeFalse()
        ->and(PaymentMethod::Cash->allowsStudentConfirmation())->toBeFalse()
        ->and(PaymentMethod::Transfer->allowsStudentConfirmation())->toBeTrue();
});
