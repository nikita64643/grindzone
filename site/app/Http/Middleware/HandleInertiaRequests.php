<?php

namespace App\Http\Middleware;

use App\Models\MinecraftServer;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Laravel\Fortify\Features;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $servers = MinecraftServer::query()
            ->orderBy('version')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $serverList = $servers->isNotEmpty()
            ? $servers->map(fn($s) => ['name' => $s->name, 'version' => $s->version, 'port' => $s->port, 'slug' => $s->slug])->values()->all()
            : collect(config('minecraft.servers', []))->map(fn($s) => ['name' => $s['name'], 'version' => $s['version'], 'port' => $s['port'], 'slug' => null])->values()->all();

        $cached = \App\Http\Controllers\Api\ServerStatusController::getCachedStatus();
        $minecraftServers = collect($serverList)->map(fn($s) => [
            'name' => $s['name'],
            'slug' => $s['slug'] ?? null,
            'version' => $s['version'],
            'port' => $s['port'],
            'status' => $cached[$s['port']] ?? null,
        ])->values()->all();
        $headerServerGroups = [
            ['game' => 'Minecraft', 'available' => true, 'servers' => $minecraftServers],
            ['game' => 'CS2', 'available' => false, 'servers' => []],
            ['game' => 'CS1.6', 'available' => false, 'servers' => []],
            ['game' => 'CS:Source', 'available' => false, 'servers' => []],
            ['game' => 'CSv34', 'available' => false, 'servers' => []],
            ['game' => 'DayZ', 'available' => false, 'servers' => []],
        ];

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user() ? array_merge($request->user()->toArray(), [
                    'nickname' => $request->user()->name,
                    'status' => $request->user()->status ?? 'Premium',
                    'balance' => (float) ($request->user()->balance ?? 0),
                ]) : null,
                'notificationsCount' => $request->user() ? $request->user()->unreadNotifications()->count() : 0,
            ],
            'canRegister' => Features::enabled(Features::registration()),
            'headerServers' => $serverList,
            'headerServerGroups' => $headerServerGroups,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'status' => $request->session()->get('status'),
                'error' => $request->session()->get('error'),
                'open_balance_modal' => $request->session()->get('open_balance_modal'),
                'tab_prefixes_results' => $request->session()->get('tab_prefixes_results'),
            ],
            'csrf_token' => csrf_token(),
            'easydonate_enabled' => config('easydonate.enabled', false),
            'balance_topup_packages' => \Illuminate\Support\Facades\Schema::hasTable('balance_topup_packages')
                ? \App\Models\BalanceTopupPackage::where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get()
                    ->map(fn($p) => [
                        'id' => $p->id,
                        'coins' => $p->coins,
                        'price' => (float) $p->price,
                        'bonus_percent' => $p->bonus_percent,
                        'total_coins' => $p->total_coins,
                        'easydonate_product_id' => (int) $p->easydonate_product_id,
                    ])
                    ->values()
                    ->all()
                : [],
        ];
    }
}
