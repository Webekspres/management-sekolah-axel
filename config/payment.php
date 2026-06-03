<?php

use App\Services\PaymentGateways\LogPaymentGateway;

return [
    'default_driver' => env('PAYMENT_GATEWAY_DRIVER', 'log'),

    'drivers' => [
        'log' => LogPaymentGateway::class,
    ],
];
