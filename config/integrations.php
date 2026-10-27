<?php

return [
    'payments' => ['driver' => env('PAYMENT_DRIVER', 'mock')],
    'shipping' => ['driver' => env('SHIPPING_DRIVER', 'mock')],
    'turnstile' => ['site_key' => env('TURNSTILE_SITE_KEY'), 'secret_key' => env('TURNSTILE_SECRET_KEY')],
];
