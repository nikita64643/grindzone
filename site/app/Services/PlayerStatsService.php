<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class PlayerStatsService
{
    /**
     * Get playtime statistics for a player by their Minecraft username.
     *
     * @return array{totalPlaytimeMinutes: int, last10DaysPlaytimeMinutes: int|null, dailyPlaytime: array<int, array{date: string, minutes: int}>}
     */
    public function getPlaytimeForPlayer(string $username): array
    {
        $basePath = config('minecraft.servers_path', base_path('../servers'));
        $servers = config('minecraft.servers', []);

        $totalTicks = 0;
        $uuids = $this->resolveUuidsForUsername($basePath, $servers, $username);

        foreach ($servers as $server) {
            $versionFolder = $this->getVersionFolder($server['version']);
            $serverFolder = $server['folder'] ?? $server['name'];
            $serverDir = $basePath . DIRECTORY_SEPARATOR . $versionFolder . DIRECTORY_SEPARATOR . $serverFolder;

            foreach ($uuids as $uuid) {
                $playTime = $this->readPlayTimeTicks($serverDir, $uuid);
                $totalTicks += $playTime;
            }
        }

        $totalMinutes = (int) floor($totalTicks / 20 / 60);

        $daily = $this->parseDailyPlaytimeFromPlayerLogs($basePath, $servers, $username)
            ?: $this->parseDailyPlaytimeFromLogs($basePath, $servers, $username);
        $last10 = array_sum(array_column($daily, 'minutes'));

        return [
            'totalPlaytimeMinutes' => $totalMinutes,
            'last10DaysPlaytimeMinutes' => $last10 > 0 ? $last10 : null,
            'dailyPlaytime' => $daily,
        ];
    }

    /**
     * Parse PlayerLogs plugin join_leave.log files.
     * Folder: plugins/PlayerLogs/<username>/join_leave.log (or playerlogs lowercase).
     *
     * @param  array<int, array{name: string, version: string, port: int}>  $servers
     * @return array<int, array{date: string, minutes: int}>
     */
    private function parseDailyPlaytimeFromPlayerLogs(string $basePath, array $servers, string $username): array
    {
        $sessions = [];
        $pluginNames = ['PlayerLogs', 'playerlogs'];

        foreach ($servers as $server) {
            $versionFolder = $this->getVersionFolder($server['version']);
            $serverFolder = $server['folder'] ?? $server['name'];
            $serverDir = $basePath . DIRECTORY_SEPARATOR . $versionFolder . DIRECTORY_SEPARATOR . $serverFolder;
            $pluginsDir = $serverDir . DIRECTORY_SEPARATOR . 'plugins';

            if (! File::isDirectory($pluginsDir)) {
                continue;
            }

            $playerLogPath = null;
            foreach ($pluginNames as $pn) {
                $base = $pluginsDir . DIRECTORY_SEPARATOR . $pn;
                if (! File::isDirectory($base)) {
                    continue;
                }
                $direct = $base . DIRECTORY_SEPARATOR . $username . DIRECTORY_SEPARATOR . 'join_leave.log';
                if (File::exists($direct)) {
                    $playerLogPath = $direct;
                    break;
                }
                foreach (File::directories($base) as $dir) {
                    if (strcasecmp(basename($dir), $username) === 0) {
                        $joinLeave = $dir . DIRECTORY_SEPARATOR . 'join_leave.log';
                        if (File::exists($joinLeave)) {
                            $playerLogPath = $joinLeave;
                            break 2;
                        }
                    }
                }
            }

            if ($playerLogPath === null) {
                continue;
            }

            $lines = explode("\n", File::get($playerLogPath));
            foreach ($lines as $line) {
                if (preg_match('/\[(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})\].*?(?:join|подключ)/i', $line, $m)) {
                    $date = "{$m[1]}-{$m[2]}-{$m[3]}";
                    $mins = (int) $m[4] * 60 + (int) $m[5] + (int) $m[6] / 60.0;
                    $sessions[$date][] = ['type' => 'join', 'mins' => $mins];
                } elseif (preg_match('/\[(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})\].*?(?:leave|quit|отключ|вышел)/i', $line, $m)) {
                    $date = "{$m[1]}-{$m[2]}-{$m[3]}";
                    $mins = (int) $m[4] * 60 + (int) $m[5] + (int) $m[6] / 60.0;
                    $sessions[$date][] = ['type' => 'leave', 'mins' => $mins];
                } elseif (preg_match('/\[(\d{2}):(\d{2}):(\d{2})\].*?(?:join|подключ)/i', $line, $m)) {
                    $date = date('Y-m-d');
                    $mins = (int) $m[1] * 60 + (int) $m[2] + (int) $m[3] / 60.0;
                    $sessions[$date][] = ['type' => 'join', 'mins' => $mins];
                } elseif (preg_match('/\[(\d{2}):(\d{2}):(\d{2})\].*?(?:leave|quit|отключ|вышел)/i', $line, $m)) {
                    $date = date('Y-m-d');
                    $mins = (int) $m[1] * 60 + (int) $m[2] + (int) $m[3] / 60.0;
                    $sessions[$date][] = ['type' => 'leave', 'mins' => $mins];
                }
            }
        }

        return $this->aggregateSessionsToDaily($sessions);
    }

    /**
     * @param  array<string, array<int, array{type: string, mins: float}>>  $sessions
     * @return array<int, array{date: string, minutes: int}>
     */
    private function aggregateSessionsToDaily(array $sessions): array
    {
        $daily = [];
        $cutoff = date('Y-m-d', strtotime('-10 days'));

        foreach ($sessions as $date => $events) {
            if ($date < $cutoff) {
                continue;
            }
            usort($events, fn($a, $b) => $a['mins'] <=> $b['mins']);
            $totalMins = 0;
            $joinMins = null;
            foreach ($events as $ev) {
                if ($ev['type'] === 'join') {
                    $joinMins = $ev['mins'];
                } elseif ($ev['type'] === 'leave' && $joinMins !== null) {
                    $totalMins += max(0, (int) ceil($ev['mins'] - $joinMins));
                    $joinMins = null;
                }
            }
            if ($totalMins > 0) {
                $daily[] = ['date' => $date, 'minutes' => (int) round($totalMins)];
            }
        }

        usort($daily, fn($a, $b) => $a['date'] <=> $b['date']);

        return $daily;
    }

    /**
     * Parse server logs to extract playtime per day (join/leave sessions).
     *
     * @param  array<int, array{name: string, version: string, port: int}>  $servers
     * @return array<int, array{date: string, minutes: int}>
     */
    private function parseDailyPlaytimeFromLogs(string $basePath, array $servers, string $username): array
    {
        $username = preg_quote($username, '/');
        $sessions = [];

        foreach ($servers as $server) {
            $versionFolder = $this->getVersionFolder($server['version']);
            $serverFolder = $server['folder'] ?? $server['name'];
            $serverDir = $basePath . DIRECTORY_SEPARATOR . $versionFolder . DIRECTORY_SEPARATOR . $serverFolder;
            $logsDir = $serverDir . DIRECTORY_SEPARATOR . 'logs';

            if (! File::isDirectory($logsDir)) {
                continue;
            }

            $today = date('Y-m-d');
            $files = [];

            if (File::exists($logsDir . DIRECTORY_SEPARATOR . 'latest.log')) {
                $files[] = ['path' => $logsDir . DIRECTORY_SEPARATOR . 'latest.log', 'date' => $today];
            }

            $cutoff = date('Y-m-d', strtotime('-10 days'));
            foreach (File::glob($logsDir . DIRECTORY_SEPARATOR . '*.log.gz') as $gzPath) {
                if (preg_match('/(\d{4})-(\d{2})-(\d{2})-\d+\.log\.gz$/', $gzPath, $m)) {
                    $fileDate = "{$m[1]}-{$m[2]}-{$m[3]}";
                    if ($fileDate >= $cutoff) {
                        $files[] = ['path' => $gzPath, 'date' => $fileDate];
                    }
                }
            }

            foreach ($files as $file) {
                $lines = $this->readLogLines($file['path']);
                $fileDate = $file['date'];

                foreach ($lines as $line) {
                    if (preg_match('/\[(\d{2}):(\d{2}):(\d{2})\].*?\b' . $username . '\b.*?(?:logged in with entity id|joined the game)/i', $line, $m)) {
                        $mins = (int) $m[1] * 60 + (int) $m[2] + (int) $m[3] / 60.0;
                        $sessions[$fileDate][] = ['type' => 'join', 'mins' => $mins];
                    } elseif (preg_match('/\[(\d{2}):(\d{2}):(\d{2})\].*?\b' . $username . '\b.*?(?:lost connection|left the game)/i', $line, $m)) {
                        $mins = (int) $m[1] * 60 + (int) $m[2] + (int) $m[3] / 60.0;
                        $sessions[$fileDate][] = ['type' => 'leave', 'mins' => $mins];
                    }
                }
            }
        }

        return $this->aggregateSessionsToDaily($sessions);
    }

    private function readLogLines(string $path, int $maxBytes = 5 * 1024 * 1024): array
    {
        if (! File::exists($path)) {
            return [];
        }

        if (str_ends_with($path, '.gz')) {
            $gz = @gzopen($path, 'rb');
            if ($gz === false) {
                return [];
            }
            $content = '';
            $totalRead = 0;
            while (! gzeof($gz) && $totalRead < $maxBytes) {
                $chunk = gzread($gz, 65536);
                if ($chunk === false || $chunk === '') {
                    break;
                }
                $content .= $chunk;
                $totalRead += strlen($chunk);
            }
            gzclose($gz);
        } else {
            $size = File::size($path);
            if ($size > $maxBytes) {
                $handle = fopen($path, 'rb');
                if ($handle === false) {
                    return [];
                }
                fseek($handle, -$maxBytes, SEEK_END);
                $content = stream_get_contents($handle);
                fclose($handle);
            } else {
                $content = File::get($path);
            }
        }

        return explode("\n", $content);
    }

    /**
     * Resolve Minecraft UUIDs for a username from usercache.json across servers.
     *
     * @param  array<int, array{name: string, version: string, port: int}>  $servers
     * @return array<int, string> UUIDs (without dashes, lowercase)
     */
    private function resolveUuidsForUsername(string $basePath, array $servers, string $username): array
    {
        $username = strtolower($username);
        $uuids = [];

        foreach ($servers as $server) {
            $versionFolder = $this->getVersionFolder($server['version']);
            $serverFolder = $server['folder'] ?? $server['name'];
            $serverDir = $basePath . DIRECTORY_SEPARATOR . $versionFolder . DIRECTORY_SEPARATOR . $serverFolder;
            $usercachePath = $serverDir . DIRECTORY_SEPARATOR . 'usercache.json';

            if (! File::exists($usercachePath)) {
                continue;
            }

            $content = File::get($usercachePath);
            $decoded = json_decode($content, true);

            if (! is_array($decoded)) {
                continue;
            }

            foreach ($decoded as $entry) {
                if (isset($entry['name']) && strtolower($entry['name']) === $username && isset($entry['uuid'])) {
                    $uuid = str_replace('-', '', strtolower($entry['uuid']));
                    if (! in_array($uuid, $uuids, true)) {
                        $uuids[] = $uuid;
                    }
                }
            }
        }

        if (empty($uuids)) {
            $uuids = $this->findUuidsFromEssentialsUserdata($basePath, $servers, $username);
        }

        return $uuids;
    }

    /**
     * Try to find UUID from Essentials userdata by last-account-name.
     *
     * @param  array<int, array{name: string, version: string, port: int}>  $servers
     * @return array<int, string>
     */
    private function findUuidsFromEssentialsUserdata(string $basePath, array $servers, string $username): array
    {
        $username = strtolower($username);
        $uuids = [];

        foreach ($servers as $server) {
            $versionFolder = $this->getVersionFolder($server['version']);
            $serverFolder = $server['folder'] ?? $server['name'];
            $serverDir = $basePath . DIRECTORY_SEPARATOR . $versionFolder . DIRECTORY_SEPARATOR . $serverFolder;
            $userdataDir = $serverDir . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'Essentials' . DIRECTORY_SEPARATOR . 'userdata';

            if (! File::isDirectory($userdataDir)) {
                continue;
            }

            foreach (File::files($userdataDir) as $file) {
                if ($file->getExtension() !== 'yml') {
                    continue;
                }
                $content = File::get($file->getPathname());
                if (! preg_match('/last-account-name:\s*(.+)/mi', $content, $m)) {
                    continue;
                }
                $name = strtolower(trim($m[1], " \t\n\r\"'"));
                if ($name === $username) {
                    $uuid = str_replace('-', '', strtolower($file->getFilenameWithoutExtension()));
                    if (! in_array($uuid, $uuids, true)) {
                        $uuids[] = $uuid;
                    }
                }
            }
        }

        return $uuids;
    }

    private function getVersionFolder(string $version): string
    {
        $folders = config('minecraft.version_folders', []);
        return $folders[$version] ?? preg_replace('/\s.*$/', '', $version);
    }

    /**
     * Read play_time (ticks) from a player's stats file.
     * Paper/Spigot 1.21+ store stats in world/stats/UUID.json.
     */
    private function readPlayTimeTicks(string $serverDir, string $uuid): int
    {
        $worldPath = $this->resolveWorldPath($serverDir);
        if (! $worldPath) {
            return 0;
        }

        $statsPath = $worldPath . DIRECTORY_SEPARATOR . 'stats' . DIRECTORY_SEPARATOR . $uuid . '.json';
        if (! File::exists($statsPath)) {
            $uuidWithDashes = $this->uuidWithDashes($uuid);
            $statsPath = $worldPath . DIRECTORY_SEPARATOR . 'stats' . DIRECTORY_SEPARATOR . $uuidWithDashes . '.json';
        }
        if (! File::exists($statsPath)) {
            return 0;
        }

        $content = File::get($statsPath);
        $data = json_decode($content, true);

        if (! is_array($data)) {
            return 0;
        }

        $playTime = $data['stats']['minecraft:custom']['minecraft:play_time'] ?? 0;

        return (int) $playTime;
    }

    /**
     * Resolve the world directory path. Tries server.properties level-name, then common names.
     */
    private function resolveWorldPath(string $serverDir): ?string
    {
        $propertiesPath = $serverDir . DIRECTORY_SEPARATOR . 'server.properties';
        if (File::exists($propertiesPath)) {
            $content = File::get($propertiesPath);
            if (preg_match('/level-name=(.+)/', $content, $m)) {
                $worldName = trim($m[1]);
                $path = $serverDir . DIRECTORY_SEPARATOR . $worldName;
                if (File::isDirectory($path)) {
                    return $path;
                }
            }
        }

        foreach (['world_sandbox', 'world'] as $name) {
            $path = $serverDir . DIRECTORY_SEPARATOR . $name;
            if (File::isDirectory($path)) {
                return $path;
            }
        }

        return null;
    }

    private function uuidWithDashes(string $uuid): string
    {
        $uuid = str_replace('-', '', $uuid);
        if (strlen($uuid) !== 32) {
            return $uuid;
        }
        return substr($uuid, 0, 8) . '-' . substr($uuid, 8, 4) . '-' . substr($uuid, 12, 4) . '-' . substr($uuid, 16, 4) . '-' . substr($uuid, 20, 12);
    }
}
