<?php

return [
    'id' => (int) env('MONETA_ACCOUNT_ID', 0),
    'secret' => env('MONETA_SECRET', ''),
    'demo_mode' => filter_var(env('MONETA_DEMO_MODE', true), FILTER_VALIDATE_BOOLEAN),
    'username' => env('MONETA_USERNAME', ''),
    'password' => env('MONETA_PASSWORD', ''),
    'payment_password' => env('MONETA_PAYMENT_PASSWORD', ''),
    'locale' => env('MONETA_LOCALE', 'ru'),
    'currency' => env('MONETA_CURRENCY', 'RUB'),
    'unit_id' => env('MONETA_UNIT_ID', ''),
    'limit_ids' => env('MONETA_LIMIT_IDS', ''),
];
