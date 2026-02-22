<?php

namespace App\Console\Commands;

use App\Models\PlayerServerStats;
use App\Models\User;
use App\Services\PlayerServerStatsService;
use Illuminate\Console\Command;

class SyncPlayerServerStatsCommand extends Command
{
    protected $signature = 'player-server-stats:sync';

    protected $description = 'Parse stats from Minecraft servers and save to player_server_stats table';

    public function handle(PlayerServerStatsService $service): int
    {
        $usersByNick = User::query()
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->get()
            ->keyBy(fn (User $u) => strtolower(trim($u->name)));

        $configs = $service->getServerConfigs();
        if (empty($configs)) {
            $this->warn('No server dirs found in config.');

            return Command::SUCCESS;
        }

        $synced = 0;

        foreach ($configs as $config) {
            $slug = $config['slug'];
            $dir = $config['dir'];

            try {
                $stats = $service->parseServerStats($dir);
            } catch (\Throwable $e) {
                $this->error("Failed to parse {$slug}: {$e->getMessage()}");

                continue;
            }

            foreach ($stats as $username => $data) {
                $user = $usersByNick->get(strtolower($username));
                if ($user === null) {
                    continue;
                }

                PlayerServerStats::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'server_slug' => $slug,
                    ],
                    [
                        'playtime_minutes' => $data['playtime_minutes'],
                        'mob_kills' => $data['mob_kills'],
                        'votes' => $data['votes'],
                        'silver' => $data['silver'],
                        'synced_at' => now(),
                    ]
                );
                $synced++;
            }

            $this->info("Synced {$slug}: " . count($stats) . ' players');
        }

        $this->info("Total records synced: {$synced}.");

        return Command::SUCCESS;
    }
}
