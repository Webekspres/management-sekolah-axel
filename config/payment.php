<?php

use App\Services\PaymentGateways\LogPaymentGateway;

return [
    'default_driver' => env('PAYMENT_GATEWAY_DRIVER', 'log'),

    'student_gateway_enabled' => env('PAYMENT_STUDENT_GATEWAY_ENABLED', false),

    'drivers' => [
        'log' => LogPaymentGateway::class,
    ],
];
