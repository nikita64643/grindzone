<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Parses player stats from Minecraft server files.
 * Data is meant to be saved to player_server_stats by SyncPlayerServerStatsCommand.
 */
class PlayerServerStatsService
{
    /**
     * @return array<string, array{playtime_minutes: int, mob_kills: int, silver: float, votes: int}>
     *   Key: lowercase Minecraft username (login)
     *   Aggregates stats from all UUIDs that map to the same username.
     */
    public function parseServerStats(string $serverDir): array
    {
        $uuidToUsername = $this->buildUuidToUsernameMap($serverDir);

        $stats = [];

        $worldPath = $this->resolveWorldPath($serverDir);
        if ($worldPath) {
            $statsDir = $worldPath . DIRECTORY_SEPARATOR . 'stats';
            if (File::isDirectory($statsDir)) {
                foreach (File::files($statsDir) as $file) {
                    if ($file->getExtension() !== 'json') {
                        continue;
                    }
                    $uuid = str_replace('-', '', strtolower($file->getFilenameWithoutExtension()));
                    $username = $uuidToUsername[$uuid] ?? null;
                    if ($username === null) {
                        continue;
                    }
                    $username = strtolower($username);

                    $data = json_decode(File::get($file->getPathname()), true);
                    if (! is_array($data)) {
                        continue;
                    }

                    $playTime = (int) ($data['stats']['minecraft:custom']['minecraft:play_time'] ?? 0);
                    $playtimeMinutes = (int) floor($playTime / 20 / 60);

                    $mobKills = (int) ($data['stats']['minecraft:custom']['minecraft:mob_kills'] ?? 0);
                    if ($mobKills === 0) {
                        $killed = $data['stats']['minecraft:killed'] ?? null;
                        if (is_array($killed)) {
                            foreach ($killed as $count) {
                                $mobKills += (int) $count;
                            }
                        }
                    }

                    if (! isset($stats[$username])) {
                        $stats[$username] = [
                            'playtime_minutes' => 0,
                            'mob_kills' => 0,
                            'silver' => 0.0,
                            'votes' => 0,
                        ];
                    }
                    $stats[$username]['playtime_minutes'] += $playtimeMinutes;
                    $stats[$username]['mob_kills'] += $mobKills;
                }
            }
        }

        $essentialsDir = $serverDir . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'Essentials' . DIRECTORY_SEPARATOR . 'userdata';
        if (File::isDirectory($essentialsDir)) {
            foreach (File::files($essentialsDir) as $file) {
                if (! in_array($file->getExtension(), ['yml', 'yaml'], true)) {
                    continue;
                }
                $uuid = str_replace('-', '', strtolower($file->getFilenameWithoutExtension()));
                $username = $uuidToUsername[$uuid] ?? $this->getUsernameFromEssentialsFile($file->getPathname());
                if ($username === null) {
                    continue;
                }
                $username = strtolower($username);

                $balance = $this->readEssentialsBalance($file->getPathname());
                if (! isset($stats[$username])) {
                    $stats[$username] = [
                        'playtime_minutes' => 0,
                        'mob_kills' => 0,
                        'silver' => 0.0,
                        'votes' => 0,
                    ];
                }
                $stats[$username]['silver'] = max($stats[$username]['silver'], $balance);
            }
        }

        $votesPath = $serverDir . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'VoteSync' . DIRECTORY_SEPARATOR . 'votes.json';
        if (! File::exists($votesPath)) {
            $votesPath = $serverDir . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'votes.json';
        }
        if (File::exists($votesPath)) {
            $votesData = json_decode(File::get($votesPath), true);
            if (is_array($votesData)) {
                foreach ($votesData as $nick => $count) {
                    if (is_string($nick) && (is_int($count) || is_numeric($count))) {
                        $key = strtolower($nick);
                        if (! isset($stats[$key])) {
                            $stats[$key] = [
                                'playtime_minutes' => 0,
                                'mob_kills' => 0,
                                'silver' => 0.0,
                                'votes' => 0,
                            ];
                        }
                        $stats[$key]['votes'] += (int) $count;
                    }
                }
            }
        }

        return $stats;
    }

    /**
     * Map UUID -> username. Multiple UUIDs can map to same username (e.g. migrated account).
     * Uses last-account-name from Essentials as canonical login.
     */
    private function buildUuidToUsernameMap(string $serverDir): array
    {
        $result = [];
        $usercachePath = $serverDir . DIRECTORY_SEPARATOR . 'usercache.json';

        if (File::exists($usercachePath)) {
            $decoded = json_decode(File::get($usercachePath), true);
            if (is_array($decoded)) {
                foreach ($decoded as $entry) {
                    if (isset($entry['name'], $entry['uuid'])) {
                        $result[str_replace('-', '', strtolower($entry['uuid']))] = $entry['name'];
                    }
                }
            }
        }

        $essentialsDir = $serverDir . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'Essentials' . DIRECTORY_SEPARATOR . 'userdata';
        if (File::isDirectory($essentialsDir)) {
            foreach (File::files($essentialsDir) as $file) {
                if (! in_array($file->getExtension(), ['yml', 'yaml'], true)) {
                    continue;
                }
                $uuid = str_replace('-', '', strtolower($file->getFilenameWithoutExtension()));
                $login = $this->getUsernameFromEssentialsFile($file->getPathname());
                if ($login !== null) {
                    $result[$uuid] = $login;
                }
            }
        }

        return $result;
    }

    /**
     * Get server configs with dir paths for parsing.
     *
     * @return array<int, array{slug: string, name: string, version: string, dir: string}>
     */
    public function getServerConfigs(): array
    {
        $basePath = config('minecraft.servers_path', base_path('../servers'));
        $servers = config('minecraft.servers', []);
        $versionFolders = config('minecraft.version_folders', []);
        $extra = config('minecraft.stats_servers_extra', []);
        $result = [];

        foreach ($servers as $server) {
            $name = $server['name'] ?? 'Server';
            $version = $server['version'] ?? '1.0';
            $folder = $server['folder'] ?? $name;
            $slug = Str::slug($name . '-' . $version);
            $versionFolder = $versionFolders[$version] ?? preg_replace('/\s.*$/', '', $version);
            $dir = $basePath . DIRECTORY_SEPARATOR . $versionFolder . DIRECTORY_SEPARATOR . $folder;

            if (File::isDirectory($dir)) {
                $result[] = ['slug' => $slug, 'name' => $name, 'version' => $version, 'dir' => $dir];
            }
        }

        foreach ($extra as $item) {
            $slug = $item['slug'] ?? null;
            $dirRel = $item['dir'] ?? null;
            if ($slug === null || $dirRel === null) {
                continue;
            }
            $dir = $basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dirRel);
            if (File::isDirectory($dir)) {
                $result[] = ['slug' => $slug, 'name' => $slug, 'version' => '', 'dir' => $dir];
            }
        }

        return $result;
    }


    private function getUsernameFromEssentialsFile(string $path): ?string
    {
        $content = File::get($path);
        if (preg_match('/last-account-name:\s*["\']?([^"\'\n\r]+)["\']?/mi', $content, $m)) {
            return trim($m[1], " \t\n\r\"'");
        }
        return null;
    }

    private function readEssentialsBalance(string $path): float
    {
        $content = File::get($path);
        if (preg_match('/money:\s*([0-9.-]+)/mi', $content, $m)) {
            return (float) $m[1];
        }
        return 0.0;
    }

    private function resolveWorldPath(string $serverDir): ?string
    {
        $propertiesPath = $serverDir . DIRECTORY_SEPARATOR . 'server.properties';
        if (File::exists($propertiesPath)) {
            $content = File::get($propertiesPath);
            if (preg_match('/level-name=(.+)/', $content, $m)) {
                $path = $serverDir . DIRECTORY_SEPARATOR . trim($m[1]);
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
}
