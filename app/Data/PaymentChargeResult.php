<?php

namespace App\Data;

readonly class PaymentChargeResult
{
    public function __construct(
        public bool $success,
        public ?string $redirectUrl = null,
        public ?string $message = null,
        public ?string $transactionId = null,
    ) {}
}
