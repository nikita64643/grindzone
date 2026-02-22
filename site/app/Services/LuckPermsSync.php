<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Thedudeguy\Rcon;

class LuckPermsSync
{
    /**
     * После доната отправить команду LuckPerms на сервер по RCON (только для 1.21.x).
     * Затем отправить уведомление на сервер (say) — чтобы игроки видели выдачу.
     *
     * @param  string|null  $privilegeName  Название привилегии для уведомления (напр. «VIP»). Если null — из ключа.
     */
    public function syncDonation(string $serverSlug, int $gamePort, string $privilegeKey, string $minecraftNickname, ?string $privilegeName = null): bool
    {
        $config = config('donate.luckperms');
        $enabled = $config['enabled'] ?? false;
        $hasPassword = ! empty($config['rcon']['password']);
        if (! $enabled || ! $hasPassword) {
            Log::warning('LuckPermsSync: отключено или не задан пароль RCON', [
                'enabled' => $enabled,
                'enabled_raw' => $config['enabled'] ?? 'null',
                'password_set' => $hasPassword,
                'server_slug' => $serverSlug,
                'hint' => 'Выполните: php artisan config:clear и проверьте .env (DONATE_LUCKPERMS_SYNC=true, MINECRAFT_RCON_PASSWORD=...)',
            ]);
            return false;
        }

        // Только для 1.21 (Paper + LuckPerms). Slug может быть "sandbox-1-21-10-..." или "sandbox-12110-vanilla" (точка в 1.21.10 убирается)
        $is121 = str_contains($serverSlug, '1-21') || str_contains($serverSlug, '12110');
        if (! $is121) {
            Log::debug('LuckPermsSync: только для серверов 1.21', ['server_slug' => $serverSlug]);
            return false;
        }

        $nick = trim($minecraftNickname);
        if ($nick === '') {
            Log::warning('LuckPermsSync: пустой ник Minecraft');
            return false;
        }

        $group = strtolower($privilegeKey);
        $host = $config['rcon']['host'];
        $rconPort = $gamePort + $config['rcon']['port_offset'];
        $password = $config['rcon']['password'];
        $timeout = $config['rcon']['timeout'];

        Log::info('LuckPermsSync: попытка подключения по RCON', [
            'host' => $host,
            'game_port' => $gamePort,
            'rcon_port' => $rconPort,
            'server_slug' => $serverSlug,
        ]);

        $rcon = new Rcon($host, $rconPort, $password, $timeout);
        if (! $rcon->connect()) {
            Log::warning('LuckPermsSync: не удалось подключиться к RCON', [
                'host' => $host,
                'rcon_port' => $rconPort,
                'server_slug' => $serverSlug,
                'hint' => 'Проверьте: сервер запущен, в server.properties enable-rcon=true, rcon.port=' . $rconPort . ', rcon.password совпадает с MINECRAFT_RCON_PASSWORD в .env',
            ]);
            return false;
        }

        $command = "lp user {$nick} parent add {$group}";
        $result = $rcon->sendCommand($command);

        if ($result === false) {
            $rcon->disconnect();
            Log::warning('LuckPermsSync: ошибка отправки команды', [
                'command' => $command,
                'server_slug' => $serverSlug,
            ]);
            return false;
        }

        // Выдать права из lp_permissions (essentials.nick, essentials.kit.* и т.д.), чтобы команды /nick, /kit работали
        $permissions = $this->getLpPermissionsForPrivilege($privilegeKey);
        foreach ($permissions as $perm) {
            $perm = trim((string) $perm);
            if ($perm !== '') {
                $rcon->sendCommand("lp user {$nick} permission set {$perm} true");
            }
        }

        // Отозвать axafkzone.tier.default — чтобы донатер не попадал в зону 20 мин (4_default)
        $rcon->sendCommand("lp user {$nick} permission set axafkzone.tier.default false");

        $displayName = $privilegeName !== null && $privilegeName !== '' ? $privilegeName : ucfirst($group);
        $sayMessage = "[Донат] Игроку {$nick} выдана привилегия {$displayName}.";
        $rcon->sendCommand('say ' . $this->escapeSayMessage($sayMessage));
        $rcon->disconnect();

        Log::info('LuckPermsSync: группа выдана', [
            'nick' => $nick,
            'group' => $group,
            'server_slug' => $serverSlug,
        ]);

        return true;
    }

    /**
     * Список прав LuckPerms для привилегии (из config или БД).
     * Нужны для работы команд EssentialsX: /nick, /kit, /home и т.д.
     */
    private function getLpPermissionsForPrivilege(string $privilegeKey): array
    {
        $key = strtolower($privilegeKey);
        if (\Illuminate\Support\Facades\Schema::hasTable('privileges')) {
            $p = \App\Models\Privilege::where('key', $key)->first();
            if ($p && is_array($p->lp_permissions) && count($p->lp_permissions) > 0) {
                return $p->lp_permissions;
            }
        }
        $fromConfig = config('donate.privileges', [])[$key]['lp_permissions'] ?? null;
        return is_array($fromConfig) ? $fromConfig : [];
    }

    /**
     * Подготовка сообщения для команды say (убрать символы, ломающие команду, ограничить длину).
     */
    private function escapeSayMessage(string $message): string
    {
        $message = str_replace(["\r", "\n"], ' ', $message);
        if (strlen($message) > 240) {
            $message = substr($message, 0, 237) . '...';
        }
        return $message;
    }

    /**
     * Установить префиксы групп для таба (список игроков). Ники будут вида [VIP] Nick, [Premium] Nick.
     * Вызывать для каждого сервера 1.21 по его game port.
     *
     * @return array{ok: bool, results: array<string, bool>}
     */
    public function applyTabPrefixes(int $gamePort): array
    {
        $prefixes = config('donate.luckperms.tab_prefixes', []);
        $privileges = config('donate.privileges', []);
        $results = [];
        $prefixPriorities = config('donate.luckperms.prefix_priorities', []);
        $weights = config('donate.luckperms.group_weights', []);
        $defaultWeight = 10;
        // Выдать axafkzone.tier.default группе default — для зоны 20 мин (только обычные игроки)
        $this->sendRconCommand($gamePort, 'lp group default permission set axafkzone.tier.default true');

        foreach ($prefixes as $group => $prefix) {
            $group = strtolower($group);
            $this->sendRconCommand($gamePort, 'lp creategroup ' . $group);
            $weight = isset($weights[$group]) ? (int) $weights[$group] : $defaultWeight;
            $this->sendRconCommand($gamePort, 'lp group ' . $group . ' setweight ' . $weight);
            $priority = isset($prefixPriorities[$group]) ? (int) $prefixPriorities[$group] : 100;
            $safe = str_replace('"', '\"', $prefix);
            $cmd = 'lp group ' . $group . ' meta setprefix ' . $priority . ' "' . $safe . '"';
            $ok = $this->sendRconCommand($gamePort, $cmd);
            if ($ok && isset($privileges[$group]['lp_permissions'])) {
                foreach ($privileges[$group]['lp_permissions'] as $perm) {
                    $perm = trim((string) $perm);
                    if ($perm !== '') {
                        $ok = $this->sendRconCommand($gamePort, 'lp group ' . $group . ' permission set ' . $perm . ' true') && $ok;
                    }
                }
            }
            $results[$group] = $ok;
        }
        return [
            'ok' => ! in_array(false, $results, true),
            'results' => $results,
        ];
    }

    /**
     * Отправить произвольную команду на сервер по RCON (для настройки LuckPerms).
     */
    public function sendRconCommand(int $gamePort, string $command): bool
    {
        $config = config('donate.luckperms');
        if (empty($config['rcon']['password'])) {
            return false;
        }

        $host = $config['rcon']['host'];
        $rconPort = $gamePort + $config['rcon']['port_offset'];
        $password = $config['rcon']['password'];
        $timeout = $config['rcon']['timeout'];

        try {
            $rcon = new Rcon($host, $rconPort, $password, $timeout);
            if (! $rcon->connect()) {
                return false;
            }

            $result = $rcon->sendCommand($command);
            $rcon->disconnect();

            return $result !== false;
        } catch (\Throwable $e) {
            Log::debug('LuckPermsSync RCON', ['port' => $gamePort, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
