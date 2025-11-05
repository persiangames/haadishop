<?php

return [
    'zarinpal' => [
        'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
    ],
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],
    'kavenegar' => [
        'api_key' => env('KAVENEGAR_API_KEY'),
    ],
];

