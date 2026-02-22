<?php

$appUrl = rtrim(env('APP_URL', config('app.url', '')), '/');

return [
    'success' => $appUrl . '/payment/success',
    'fail' => $appUrl . '/payment/fail',
    'inprogress' => $appUrl . '/payment/inprogress',
    'return' => $appUrl . '/profile',
    'iframe_target' => '_parent',
];
