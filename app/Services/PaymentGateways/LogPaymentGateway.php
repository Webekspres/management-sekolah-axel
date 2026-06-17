<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGateway;
use App\Data\PaymentChargeResult;
use App\Models\Payment;
use App\Models\PaymentGatewayLog;
use Illuminate\Support\Str;

class LogPaymentGateway implements PaymentGateway
{
    public function createCharge(Payment $payment): PaymentChargeResult
    {
        $transactionId = 'LOG-'.Str::upper(Str::random(12));

        PaymentGatewayLog::query()->create([
            'payment_id' => $payment->id,
            'driver' => 'log',
            'event' => 'charge.created',
            'payload' => [
                'amount' => $payment->amount_paid,
                'invoice_id' => $payment->invoice_id,
            ],
        ]);

        return new PaymentChargeResult(
            success: false,
            redirectUrl: null,
            message: __('pembayaran.pay_modal.online_unavailable'),
            transactionId: $transactionId,
        );
    }

    public function handleWebhook(array $payload): void
    {
        PaymentGatewayLog::query()->create([
            'payment_id' => $payload['payment_id'] ?? null,
            'driver' => 'log',
            'event' => 'webhook.received',
            'payload' => $payload,
        ]);
    }
}
