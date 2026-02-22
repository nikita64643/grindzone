<?php

return [
    /*
    | Absolute path to grindzone servers directory (parent of site/).
    | Used by admin panel for logs and restart.
    */
    'servers_path' => env('MINECRAFT_SERVERS_PATH', base_path('../servers')),

    /*
    | Map version label to folder name (e.g. "1.16.5 Paper" => "1.16.5").
    */
    'version_folders' => [
        '1.16.5 Paper' => '1.16.5',
        '1.21.10 Paper' => '1.21.10',
    ],

    /*
    | Host for Minecraft servers (e.g. 127.0.0.1 or your public IP).
    | Used when pinging servers for online status.
    */
    'ping_host' => env('MINECRAFT_PING_HOST', '127.0.0.1'),

    /*
    | Address shown to players for connection (e.g. play.example.com or localhost).
    | Used on the HowToPlay page. Falls back to ping_host if not set.
    */
    'connect_address' => env('MINECRAFT_CONNECT_ADDRESS', env('MINECRAFT_PING_HOST', 'localhost')),

    /*
    | Timeout in seconds when pinging a server.
    */
    'ping_timeout' => (float) env('MINECRAFT_PING_TIMEOUT', 2),

    /*
    | Cache TTL in seconds for server status (to avoid hammering servers).
    */
    'status_cache_ttl' => (int) env('MINECRAFT_STATUS_CACHE_TTL', 15),

    /*
    | Максимум игроков для отображения на сайте (слоты). Реальный лимит сервера может быть выше.
    */
    'display_max_players' => (int) env('MINECRAFT_DISPLAY_MAX_PLAYERS', 30),

    /*
    | List of servers to show and ping. По одному "Выживание" на каждую версию.
    | name — отображаемое название, folder — имя папки сервера (для статистики онлайна и логов).
    | Ports: 1.16.5 → 25566, 1.21.10 → 25570.
    */
    'servers' => [
        ['name' => 'Выживание', 'version' => '1.16.5 Paper', 'port' => 25566, 'folder' => 'SandBox'],
        ['name' => 'Выживание', 'version' => '1.21.10 Paper', 'port' => 25570, 'folder' => 'SandBox'],
    ],

    /*
    | Доп. серверы для синка статистики (уб. мобов, время и т.д.). Не показываются на сайте.
    | dir — папка относительно servers_path (например lobby, 1.21.10/SandBox).
    */
    'stats_servers_extra' => [],

    /*
    | Доп. контент по версии (для детальной страницы сервера).
    | name — название, description — краткое описание.
    */
    'mods_by_version' => [
        '1.16.5 Paper' => [],
        '1.21.10 Paper' => [
            ['name' => 'mcMMO', 'description' => 'RPG-скиллы: майнинг, рубка деревьев, бой, фермерство и др.'],
            ['name' => 'uJobs', 'description' => 'Работа: награды за добычу блоков, рыбалку, убийство мобов.'],
            ['name' => 'PlayTimeManager', 'description' => 'Статистика онлайна и AFK: топ по времени в игре и времени в AFK.'],
        ],
    ],
];
