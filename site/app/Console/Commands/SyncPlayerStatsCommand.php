<?php

namespace App\Console\Commands;

use App\Models\PlayerPlaytimeDaily;
use App\Models\PlayerStats;
use App\Models\User;
use App\Services\PlayerStatsService;
use Illuminate\Console\Command;

class SyncPlayerStatsCommand extends Command
{
    protected $signature = 'player-stats:sync {--user= : Sync only for user ID}';

    protected $description = 'Sync player playtime from server files to database';

    public function handle(PlayerStatsService $playerStats): int
    {
        $userId = $this->option('user');
        $query = User::query()->whereNotNull('name')->where('name', '!=', '');

        if ($userId !== null) {
            $query->where('id', $userId);
        }

        $users = $query->get();
        $synced = 0;

        foreach ($users as $user) {
            $username = trim($user->name ?? $user->nickname ?? '');
            if ($username === '') {
                continue;
            }

            try {
                $data = $playerStats->getPlaytimeForPlayer($username);

                PlayerStats::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'total_playtime_minutes' => $data['totalPlaytimeMinutes'],
                        'last10_days_playtime_minutes' => $data['last10DaysPlaytimeMinutes'],
                        'synced_at' => now(),
                    ]
                );

                $cutoff = now()->subDays(10)->format('Y-m-d');
                PlayerPlaytimeDaily::where('user_id', $user->id)->where('date', '<', $cutoff)->delete();

                foreach ($data['dailyPlaytime'] ?? [] as $day) {
                    PlayerPlaytimeDaily::updateOrCreate(
                        ['user_id' => $user->id, 'date' => $day['date']],
                        ['minutes' => $day['minutes']]
                    );
                }

                $synced++;
                $this->info("Synced: {$username} (user #{$user->id})");
            } catch (\Throwable $e) {
                $this->error("Failed for {$username}: {$e->getMessage()}");
            }
        }

        $this->info("Synced {$synced} users.");
        return Command::SUCCESS;
    }
}
