<?php

namespace App\Contracts;

use App\Data\PaymentChargeResult;
use App\Models\Payment;

interface PaymentGateway
{
    public function createCharge(Payment $payment): PaymentChargeResult;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): void;
}
