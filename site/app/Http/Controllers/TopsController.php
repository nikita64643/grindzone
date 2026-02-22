<?php

namespace App\Http\Controllers;

use App\Models\MinecraftServer;
use App\Models\Donation;
use App\Models\PlayerServerStats;
use App\Models\PlayerStats;
use App\Models\PrivilegePurchase;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TopsController extends Controller
{
    public function index(Request $request): Response
    {
        $serverSlug = $request->query('server');
        $servers = $this->getServersList();

        $tops = [
            'donators' => $this->getTopDonators($serverSlug),
            'playtime' => $this->getTopPlaytime($serverSlug),
            'mobKills' => $this->getTopMobKills($serverSlug),
            'votes' => $this->getTopVotes($serverSlug),
            'silver' => $this->getTopSilver($serverSlug),
        ];

        return Inertia::render('tops/Index', [
            'servers' => $servers,
            'currentServer' => $serverSlug,
            'tops' => $tops,
        ]);
    }

    private function getServersList(): array
    {
        $fromDb = MinecraftServer::query()
            ->orderBy('version')
            ->orderBy('sort_order')
            ->get(['name', 'slug', 'version']);

        $list = $fromDb->isNotEmpty()
            ? $fromDb->map(fn ($s) => [
                'name' => $s->name,
                'slug' => $s->slug,
                'version' => $s->version,
            ])->values()->all()
            : collect(config('minecraft.servers', []))
                ->map(fn ($s) => [
                    'name' => $s['name'],
                    'slug' => \Illuminate\Support\Str::slug(($s['name'] ?? '') . '-' . ($s['version'] ?? '')),
                    'version' => $s['version'] ?? '',
                ])
                ->values()
                ->all();

        $extra = config('minecraft.stats_servers_extra', []);
        foreach ($extra as $item) {
            $slug = $item['slug'] ?? null;
            if ($slug !== null && ! collect($list)->contains('slug', $slug)) {
                $list[] = ['name' => $slug, 'slug' => $slug, 'version' => ''];
            }
        }

        return array_values($list);
    }

    private function getTopDonators(?string $serverSlug): array
    {
        $union = collect();

        $donations = Donation::query()
            ->select('user_id', 'amount')
            ->when($serverSlug, fn ($q) => $q->where('server_slug', $serverSlug))
            ->get();

        $purchases = PrivilegePurchase::query()
            ->select('user_id', 'amount')
            ->where('status', 'completed')
            ->when($serverSlug, fn ($q) => $q->where('server_slug', $serverSlug))
            ->get();

        $byUser = [];
        foreach ($donations as $d) {
            $byUser[$d->user_id] = ($byUser[$d->user_id] ?? 0) + (float) $d->amount;
        }
        foreach ($purchases as $p) {
            $byUser[$p->user_id] = ($byUser[$p->user_id] ?? 0) + (float) $p->amount;
        }

        arsort($byUser);
        $userIds = array_slice(array_keys($byUser), 0, 50);
        $users = User::whereIn('id', $userIds)->pluck('name', 'id');

        $result = [];
        $rank = 1;
        foreach ($userIds as $uid) {
            $result[] = [
                'rank' => $rank++,
                'name' => $users[$uid] ?? 'Игрок',
                'amount' => round($byUser[$uid], 2),
            ];
        }
        return $result;
    }

    private function getTopPlaytime(?string $serverSlug): array
    {
        if ($serverSlug) {
            return PlayerServerStats::query()
                ->selectRaw('player_server_stats.user_id, users.name, SUM(player_server_stats.playtime_minutes) as minutes')
                ->where('player_server_stats.server_slug', $serverSlug)
                ->where('player_server_stats.playtime_minutes', '>', 0)
                ->join('users', 'player_server_stats.user_id', '=', 'users.id')
                ->groupBy('player_server_stats.user_id', 'users.name')
                ->orderByDesc('minutes')
                ->limit(50)
                ->get()
                ->map(fn ($r, $i) => [
                    'rank' => $i + 1,
                    'name' => $r->name ?: 'Игрок',
                    'minutes' => (int) $r->minutes,
                ])
                ->values()
                ->all();
        }

        return PlayerStats::query()
            ->where('total_playtime_minutes', '>', 0)
            ->join('users', 'player_stats.user_id', '=', 'users.id')
            ->orderByDesc('player_stats.total_playtime_minutes')
            ->limit(50)
            ->get(['player_stats.total_playtime_minutes as minutes', 'users.name'])
            ->map(fn ($r, $i) => [
                'rank' => $i + 1,
                'name' => $r->name ?: 'Игрок',
                'minutes' => (int) $r->minutes,
            ])
            ->values()
            ->all();
    }

    private function getTopMobKills(?string $serverSlug): array
    {
        $q = PlayerServerStats::query()
            ->selectRaw('player_server_stats.user_id, users.name, SUM(player_server_stats.mob_kills) as kills')
            ->join('users', 'player_server_stats.user_id', '=', 'users.id')
            ->where('player_server_stats.mob_kills', '>', 0)
            ->groupBy('player_server_stats.user_id', 'users.name')
            ->orderByDesc('kills')
            ->limit(50);

        if ($serverSlug) {
            $q->where('player_server_stats.server_slug', $serverSlug);
        }

        return $q->get()
            ->map(fn ($r, $i) => [
                'rank' => $i + 1,
                'name' => $r->name ?: 'Игрок',
                'kills' => (int) $r->kills,
            ])
            ->values()
            ->all();
    }

    private function getTopVotes(?string $serverSlug): array
    {
        $q = PlayerServerStats::query()
            ->selectRaw('player_server_stats.user_id, users.name, SUM(player_server_stats.votes) as votes')
            ->join('users', 'player_server_stats.user_id', '=', 'users.id')
            ->where('player_server_stats.votes', '>', 0)
            ->groupBy('player_server_stats.user_id', 'users.name')
            ->orderByDesc('votes')
            ->limit(50);

        if ($serverSlug) {
            $q->where('player_server_stats.server_slug', $serverSlug);
        }

        return $q->get()
            ->map(fn ($r, $i) => [
                'rank' => $i + 1,
                'name' => $r->name ?: 'Игрок',
                'votes' => (int) $r->votes,
            ])
            ->values()
            ->all();
    }

    private function getTopSilver(?string $serverSlug): array
    {
        if (! $serverSlug) {
            return [];
        }

        return PlayerServerStats::query()
            ->where('player_server_stats.server_slug', $serverSlug)
            ->where('silver', '>', 0)
            ->join('users', 'player_server_stats.user_id', '=', 'users.id')
            ->orderByDesc('player_server_stats.silver')
            ->limit(50)
            ->get(['player_server_stats.silver as silver', 'users.name'])
            ->map(fn ($r, $i) => [
                'rank' => $i + 1,
                'name' => $r->name ?: 'Игрок',
                'silver' => (float) $r->silver,
            ])
            ->values()
            ->all();
    }
}
