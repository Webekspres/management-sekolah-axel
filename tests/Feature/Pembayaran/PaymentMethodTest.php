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

it('excludes gateway methods from student options when disabled', function () {
    config(['payment.student_gateway_enabled' => false]);

    $options = PaymentMethod::optionsForStudent();

    expect($options)->toHaveKeys(['transfer', 'cash'])
        ->and($options)->not->toHaveKey('qris')
        ->and($options)->not->toHaveKey('va_bni');
});

it('includes gateway methods in student options when enabled', function () {
    config(['payment.student_gateway_enabled' => true]);

    $options = PaymentMethod::optionsForStudent();

    expect($options)->toHaveKeys(['transfer', 'cash', 'qris', 'va_bni', 'va_bca', 'va_mandiri']);
});

it('asserts gateway method unavailable to students when disabled', function () {
    config(['payment.student_gateway_enabled' => false]);

    PaymentMethod::assertAvailableToStudent(PaymentMethod::Qris);
})->throws(DomainException::class);

it('allows transfer for students when gateway disabled', function () {
    config(['payment.student_gateway_enabled' => false]);

    PaymentMethod::assertAvailableToStudent(PaymentMethod::Transfer);

    expect(true)->toBeTrue();
});
