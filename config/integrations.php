<?php

return [
    'payments' => ['driver' => env('PAYMENT_DRIVER', 'mock')],
    'shipping' => ['driver' => env('SHIPPING_DRIVER', 'mock')],
    'turnstile' => ['site_key' => env('TURNSTILE_SITE_KEY'), 'secret_key' => env('TURNSTILE_SECRET_KEY')],
    'demo_seeding' => env('AURA_DEMO_SEEDING', false),
    'e2e_admin' => [
        'email' => env('PLAYWRIGHT_ADMIN_EMAIL'),
        'password' => env('PLAYWRIGHT_ADMIN_PASSWORD'),
    ],
];
