<?php

return [
    /*
    | EasyDonate — альтернативная платёжная система для Minecraft-серверов.
    | Документация: https://docs.easydonate.ru/
    |
    | server_id и product_id берутся из БД (minecraft_servers.easydonate_server_id,
    | privileges.easydonate_product_id).
    */
    'enabled' => filter_var(env('EASYDONATE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'shop_key' => env('EASYDONATE_SHOP_KEY', ''),
    'api_url' => 'https://easydonate.ru/api/v3/shop/payment/create',

    /*
    | Публичный URL сайта для EasyDonate (success_url, callback).
    | Обязательно укажите, если сайт за NAT/локальной сетью (192.168.x.x).
    | EasyDonate должен иметь доступ к этому URL (callback после оплаты).
    | Пример: https://grindzone.ru или https://xxx.ngrok.io
    */
    'public_url' => rtrim((string) env('EASYDONATE_PUBLIC_URL', env('APP_URL', '')), '/'),

    /*
    | server_id для пополнения баланса (продукты монет в EasyDonate).
    | Если 0 — используется первый сервер из БД.
    */
    'balance_server_id' => (int) env('EASYDONATE_BALANCE_SERVER_ID', 0),
];
