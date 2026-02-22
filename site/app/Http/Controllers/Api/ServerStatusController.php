<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MinecraftServer;
use App\Services\MinecraftPing;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ServerStatusController extends Controller
{
    /**
     * Servers to ping: from DB if not empty, else from config.
     *
     * @return array<int, array{name: string, version: string, port: int}>
     */
    private static function serversList(): array
    {
        $fromDb = MinecraftServer::query()->orderBy('version')->orderBy('sort_order')->orderBy('id')->get();
        if ($fromDb->isNotEmpty()) {
            return $fromDb->map(fn($s) => ['name' => $s->name, 'version' => $s->version, 'port' => $s->port])->all();
        }
        return config('minecraft.servers', []);
    }

    /**
     * Cache key for server status (same key used for API and initial page data).
     */
    public static function cacheKey(): string
    {
        $host = config('minecraft.ping_host', '127.0.0.1');
        $servers = self::serversList();
        $ports = array_map(fn($s) => $s['port'] ?? $s['port'], $servers);

        return 'minecraft_servers_status_' . md5($host . implode(',', $ports));
    }

    /**
     * Return cached status only (no ping). Used for initial page load.
     *
     * @return array<int, array{online: bool, players_online: int, players_max: int}> port => status
     */
    public static function getCachedStatus(): array
    {
        $raw = Cache::get(self::cacheKey());
        if (! is_array($raw)) {
            return [];
        }
        $byPort = [];
        foreach ($raw as $row) {
            $port = (int) ($row['port'] ?? 0);
            if ($port > 0) {
                $maxPlayers = (int) ($row['players_max'] ?? 0);
                $cap = config('minecraft.display_max_players', 30);
                $byPort[$port] = [
                    'online' => (bool) ($row['online'] ?? false),
                    'players_online' => (int) ($row['players_online'] ?? 0),
                    'players_max' => $cap > 0 ? min($maxPlayers, $cap) : $maxPlayers,
                ];
            }
        }

        return $byPort;
    }

    /**
     * Return status of all configured Minecraft servers (online/max, online flag).
     * Cached so reload shows status immediately without waiting for ping.
     */
    public function index(): JsonResponse
    {
        $ttl = config('minecraft.status_cache_ttl', 15);
        $host = config('minecraft.ping_host', '127.0.0.1');
        $timeout = config('minecraft.ping_timeout', 2);
        $servers = self::serversList();

        $status = Cache::remember(self::cacheKey(), $ttl, function () use ($servers, $host, $timeout) {
            $result = [];
            foreach ($servers as $server) {
                $port = (int) $server['port'];
                $ping = new MinecraftPing($host, $port, $timeout);
                $data = $ping->ping();
                $maxPlayers = (int) ($data['max'] ?? 0);
                $cap = config('minecraft.display_max_players', 30);
                $result[] = [
                    'name' => $server['name'],
                    'version' => $server['version'] ?? null,
                    'port' => $port,
                    'online' => $data !== null,
                    'players_online' => $data['online'] ?? 0,
                    'players_max' => $cap > 0 ? min($maxPlayers, $cap) : $maxPlayers,
                ];
            }

            return $result;
        });

        return response()->json(['servers' => $status]);
    }
}
