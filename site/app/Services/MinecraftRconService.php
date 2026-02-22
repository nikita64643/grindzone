<?php

namespace App\Services;

use App\Models\MinecraftServer;
use Illuminate\Support\Facades\Log;
use Thedudeguy\Rcon;

class MinecraftRconService
{
    /**
     * Отправляет команду на сервер по RCON.
     *
     * @return bool Успешность выполнения (подключение + отправка)
     */
    public function sendCommand(MinecraftServer $server, string $command): bool
    {
        $config = config('donate.luckperms.rcon');
        $password = $config['password'] ?? '';
        if ($password === '') {
            Log::warning('MinecraftRconService: RCON пароль не задан (MINECRAFT_RCON_PASSWORD в .env)');

            return false;
        }

        $host = $config['host'] ?? '127.0.0.1';
        $rconPort = $server->port + ($config['port_offset'] ?? 10000);
        $timeout = $config['timeout'] ?? 5;

        $rcon = new Rcon($host, $rconPort, $password, $timeout);
        if (! $rcon->connect()) {
            Log::warning('MinecraftRconService: не удалось подключиться по RCON', [
                'server' => $server->slug,
                'port' => $rconPort,
            ]);

            return false;
        }

        $result = $rcon->sendCommand($command);
        $rcon->disconnect();

        if ($result === false) {
            Log::warning('MinecraftRconService: ошибка выполнения команды', [
                'server' => $server->slug,
                'command' => $command,
            ]);

            return false;
        }

        Log::info('MinecraftRconService: команда выполнена', [
            'server' => $server->slug,
            'command' => $command,
        ]);

        return true;
    }
}
