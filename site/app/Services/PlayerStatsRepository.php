<?php

namespace App\Services;

use App\Models\PlayerPlaytimeDaily;
use App\Models\PlayerStats;
use App\Models\User;

class PlayerStatsRepository
{
    /**
     * Get playtime stats for a user from database.
     *
     * @return array{totalPlaytimeMinutes: int, last10DaysPlaytimeMinutes: int|null, dailyPlaytime: array<int, array{date: string, minutes: int}>}
     */
    public function getStatsForUser(User $user): array
    {
        $stats = $user->playerStats;

        if ($stats === null) {
            return [
                'totalPlaytimeMinutes' => 0,
                'last10DaysPlaytimeMinutes' => null,
                'dailyPlaytime' => [],
            ];
        }

        $cutoff = now()->subDays(10)->format('Y-m-d');
        $daily = PlayerPlaytimeDaily::query()
            ->where('user_id', $user->id)
            ->where('date', '>=', $cutoff)
            ->orderBy('date')
            ->get()
            ->map(fn($r) => ['date' => $r->date->format('Y-m-d'), 'minutes' => (int) $r->minutes])
            ->values()
            ->all();

        $last10 = array_sum(array_column($daily, 'minutes'));

        return [
            'totalPlaytimeMinutes' => (int) $stats->total_playtime_minutes,
            'last10DaysPlaytimeMinutes' => $last10 > 0 ? $last10 : null,
            'dailyPlaytime' => $daily,
        ];
    }
}
