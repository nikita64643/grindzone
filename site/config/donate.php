<?php

return [
    /*
    | Синхронизация с LuckPerms (только для серверов 1.21.x с Paper).
    | После доната на сайте команда "lp user <ник> parent add <группа>" отправляется по RCON.
    | Группы в LuckPerms должны называться так же: vip, premium, legend (нижний регистр).
    | RCON: включите в server.properties enable-rcon=true, задайте rcon.port (уникальный, напр. game_port+10000), rcon.password.
    */
    'luckperms' => [
        'enabled' => filter_var(env('DONATE_LUCKPERMS_SYNC', false), FILTER_VALIDATE_BOOLEAN),
        'rcon' => [
            'host' => env('MINECRAFT_RCON_HOST', '127.0.0.1'),
            'password' => env('MINECRAFT_RCON_PASSWORD', ''),
            'timeout' => (int) env('MINECRAFT_RCON_TIMEOUT', 5),
            // RCON-порт = game server port + offset (у каждого сервера свой server-port, rcon.port должен быть уникален)
            'port_offset' => (int) env('MINECRAFT_RCON_PORT_OFFSET', 10000),
        ],
        // Префиксы в табе и чате. Приоритет префикса — у каждой группы свой (больше = выигрывает при нескольких группах).
        // Цвета: &a зелёный, &6 золотой, &d светло-фиолетовый, &f белый
        'tab_prefixes' => [
            'vip' => '&a[VIP] &f',
            'premium' => '&6[Premium] &f',
            'legend' => '&d[Legend] &f',
        ],
        // Приоритет префикса в LuckPerms: при нескольких группах показывается префикс с большим приоритетом. Новые группы без записи получают 100.
        'prefix_priorities' => [
            'vip' => 100,
            'premium' => 200,
            'legend' => 300,
        ],
        // Веса групп: основная группа = с наибольшим весом. default без веса = 0.
        // Группы не из списка получают вес 10 автоматически при применении префиксов.
        'group_weights' => [
            'vip' => 10,
            'premium' => 20,
            'legend' => 30,
        ],
    ],

    /*
    | Привилегии для доната. Ключ = имя группы в LuckPerms (vip, premium, legend).
    | name, description — на сайте. price — списание с баланса.
    | features — список возможностей для отображения на странице доната.
    | lp_permissions — права в LuckPerms (плагины: EssentialsX, GriefPrevention и т.д.).
    */
    'privileges' => [
        'vip' => [
            'name' => 'VIP',
            'description' => 'Базовый набор привилегий для комфортной игры.',
            'price' => 299,
            'features' => [
                'Цветной ник в чате',
                'Приоритет при входе на сервер (очередь)',
                'Команда /hat (надеть блок на голову)',
                'Команда /nick (сменить отображаемый ник)',
            ],
            'lp_permissions' => [
                'essentials.hat',
                'essentials.priority',
                'essentials.nick',
                'essentials.nick.color',
                'essentials.nick.format',
                'essentials.color',
                'essentials.kit',
                'essentials.chat.color',
                'essentials.chat.format',
                'essentials.chat.*',
                'axafkzone.tier.vip',
            ],
        ],
        'premium' => [
            'name' => 'Premium',
            'description' => 'Всё из VIP плюс дом и защита участков.',
            'price' => 599,
            'features' => [
                'Всё из VIP',
                'Личные точки дома: /sethome, /home (телепорт)',
                'Набор предметов при входе (кит Premium)',
                'Защита участков: 5 участков (блоков защиты)',
            ],
            'lp_permissions' => [
                'essentials.hat',
                'essentials.priority',
                'essentials.nick',
                'essentials.nick.color',
                'essentials.nick.format',
                'essentials.color',
                'essentials.chat.color',
                'essentials.chat.format',
                'essentials.chat.*',
                'essentials.sethome',
                'essentials.home',
                'essentials.kit',
                'essentials.kit.premium',
                'griefprevention.claimblocks.amount.500',
                'axafkzone.tier.premium',
            ],
        ],
        'legend' => [
            'name' => 'Legend',
            'description' => 'Максимальный набор: всё из Premium и расширенные возможности.',
            'price' => 999,
            'features' => [
                'Всё из Premium',
                'Защита участков: 20 участков',
                'Улучшенный кит при входе (Legend)',
                'Отдельный цвет в табе (список игроков)',
                'Бонусы в ивентах (на усмотрение администрации)',
            ],
            'lp_permissions' => [
                'essentials.hat',
                'essentials.priority',
                'essentials.nick',
                'essentials.nick.color',
                'essentials.nick.format',
                'essentials.color',
                'essentials.chat.color',
                'essentials.chat.format',
                'essentials.chat.*',
                'essentials.sethome',
                'essentials.home',
                'essentials.kit',
                'essentials.kit.legend',
                'griefprevention.claimblocks.amount.2000',
                'axafkzone.tier.legend',
            ],
        ],
    ],
];
