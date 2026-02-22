<?php

use App\Services\LuckPermsSync;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('luckperms:grant-admin {user=nikita64643}', function () {
    $user = $this->argument('user');
    $sync = app(LuckPermsSync::class);
    $ports = [25570];
    $commands = [
        "lp user {$user} permission set * true",
        "lp user {$user} permission set luckperms.* true",
        "op {$user}",
    ];
    foreach ($ports as $port) {
        $ok = true;
        foreach ($commands as $cmd) {
            if (! $sync->sendRconCommand($port, $cmd)) {
                $ok = false;
            }
        }
        $this->info("Port {$port}: " . ($ok ? 'OK' : 'FAIL'));
    }
    $this->info("Готово. Пользователю {$user} выданы все права и OP на 1.21.");
})->purpose('Grant all permissions and OP to a user on all 1.21 servers via RCON');

Artisan::command('luckperms:grant-essentials-admin {user=nikita64643}', function () {
    $user = $this->argument('user');
    $sync = app(LuckPermsSync::class);
    $perms = ['essentials.setspawn', 'essentials.spawn', 'essentials.tpr', 'essentials.rtp', 'ajleaderboards.use'];
    $ports = [25570];
    foreach ($ports as $port) {
        $ok = true;
        foreach ($perms as $perm) {
            if (! $sync->sendRconCommand($port, "lp user {$user} permission set {$perm} true")) {
                $ok = false;
            }
        }
        $this->info("Port {$port}: " . ($ok ? 'OK' : 'FAIL'));
    }
    $this->info("Готово. Пользователю {$user} выданы права Essentials и ajleaderboards.use (таблички с топами).");
})->purpose('Выдать пользователю права на /setspawn, /ajlb (таблички с топами) на всех 1.21');

Artisan::command('donate:apply-tab-prefixes', function () {
    $sync = app(LuckPermsSync::class);
    $servers121 = collect(config('minecraft.servers', []))
        ->filter(fn($s) => str_contains($s['version'] ?? '', '1.21'))
        ->values();

    foreach ($servers121 as $s) {
        $port = (int) ($s['port'] ?? 0);
        $label = ($s['name'] ?? '') . ' ' . ($s['version'] ?? '') . ':' . $port;
        $out = $sync->applyTabPrefixes($port);
        $status = $out['ok'] ? 'OK' : 'FAIL';
        $this->info("{$label} — {$status}");
    }

    // Отозвать axafkzone.tier.default у всех донатеров (чтобы они попадали в свою tier-зону, а не в 4_default)
    $donorNicks = \App\Models\User::whereHas('donations', function ($q) {
        $q->whereIn('privilege_key', ['vip', 'premium', 'legend']);
    })->pluck('name')->filter()->unique()->values();
    foreach ($servers121 as $s) {
        $port = (int) ($s['port'] ?? 0);
        foreach ($donorNicks as $nick) {
            $sync->sendRconCommand($port, "lp user {$nick} permission set axafkzone.tier.default false");
        }
    }
    if ($donorNicks->isNotEmpty()) {
        $this->info('Отозван axafkzone.tier.default у ' . $donorNicks->count() . ' донатеров.');
    }

    $this->info('Готово. Префиксы и веса групп применены на всех серверах 1.21.');
})->purpose('Применить префиксы и веса групп LuckPerms на всех серверах 1.21 по RCON');

Artisan::command('servers:reload-essentials', function () {
    $sync = app(LuckPermsSync::class);
    $servers121 = collect(config('minecraft.servers', []))
        ->filter(fn($s) => str_contains($s['version'] ?? '', '1.21'))
        ->values();

    foreach ($servers121 as $s) {
        $port = (int) ($s['port'] ?? 0);
        $label = ($s['name'] ?? '') . ' ' . ($s['version'] ?? '') . ':' . $port;
        $ok = $sync->sendRconCommand($port, 'ess reload');
        $this->info($label . ' — ' . ($ok ? 'OK' : 'FAIL'));
    }
    $this->info('Готово. Essentials (в т.ч. kits.yml) перезагружен на всех серверах 1.21.');
})->purpose('Перезагрузить Essentials на всех серверах 1.21 по RCON (подхватывает kits.yml)');

Artisan::command('servers:default-essentials-permissions', function () {
    $sync = app(LuckPermsSync::class);
    $servers121 = collect(config('minecraft.servers', []))
        ->filter(fn($s) => str_contains($s['version'] ?? '', '1.21'))
        ->values();

    $permissions = ['essentials.spawn', 'essentials.tpr', 'essentials.rtp'];
    foreach ($servers121 as $s) {
        $port = (int) ($s['port'] ?? 0);
        $label = ($s['name'] ?? '') . ' ' . ($s['version'] ?? '') . ':' . $port;
        $ok = true;
        foreach ($permissions as $perm) {
            if (! $sync->sendRconCommand($port, "lp group default permission set {$perm} true")) {
                $ok = false;
            }
        }
        $this->info($label . ' — ' . ($ok ? 'OK' : 'FAIL'));
    }
    $this->info('Готово. Группе default выданы права: spawn, tpr, rtp.');
})->purpose('Выдать группе default на всех 1.21 серверах права на /spawn и /tpr (/rtp)');

Artisan::command('servers:install-papi-expansions', function () {
    $sync = app(LuckPermsSync::class);
    $servers121 = collect(config('minecraft.servers', []))
        ->filter(fn($s) => str_contains($s['version'] ?? '', '1.21'))
        ->values();

    $commands = [
        'papi ecloud download Vault',
        'papi ecloud download Essentials',
        'papi ecloud download WorldGuard',
        'papi reload',
        'ajlb add %vault_eco_balance%',
        'ajlb add %mcmmo_power_level%',
        'ajlb add %mcmmo_level_mining%',
        'ajlb add %mcmmo_level_woodcutting%',
        'ajlb add %mcmmo_level_excavation%',
        'ajlb add %mcmmo_level_herbalism%',
        'ajlb add %mcmmo_level_fishing%',
        'ajlb add %mcmmo_level_combat%',
        'ajlb add %PTM_afktime%',
        'ajlb add %PTM_playtime%',
        'ajlb reload',
    ];
    foreach ($servers121 as $s) {
        $port = (int) ($s['port'] ?? 0);
        $label = ($s['name'] ?? '') . ' ' . ($s['version'] ?? '') . ':' . $port;
        $ok = true;
        foreach ($commands as $cmd) {
            if (! $sync->sendRconCommand($port, $cmd)) {
                $ok = false;
            }
        }
        $this->info($label . ' — ' . ($ok ? 'OK' : 'FAIL'));
    }
    $this->info('Готово. Установлены экспаншены Vault и Essentials, зарегистрирован топ по балансу (ajlb). Баланс в TAB и таблички с топами работают.');
})->purpose('Установить PlaceholderAPI экспаншены, топ по монетам и mcMMO на всех 1.21 по RCON');
