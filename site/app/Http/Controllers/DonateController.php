<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\MinecraftServer;
use App\Models\Privilege;
use App\Services\LuckPermsSync;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DonateController extends Controller
{
    public function index(): Response
    {
        $servers = MinecraftServer::query()
            ->orderBy('version')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'version', 'port']);

        if ($servers->isEmpty()) {
            $servers = collect(config('minecraft.servers', []))
                ->unique(fn($s) => $s['name'] . '-' . $s['version'])
                ->values()
                ->map(fn($s, $i) => (object) [
                    'id' => $i + 1,
                    'name' => $s['name'],
                    'slug' => \Illuminate\Support\Str::slug($s['name'] . '-' . $s['version']),
                    'version' => $s['version'],
                    'port' => $s['port'],
                ]);
        }

        return Inertia::render('donate/Index', [
            'servers' => $servers->map(fn($s) => [
                'id' => $s->id ?? $s->id,
                'name' => $s->name,
                'slug' => $s->slug,
                'version' => $s->version,
                'port' => $s->port,
            ])->values()->all(),
        ]);
    }

    public function show(string $serverSlug): Response|array
    {
        $server = MinecraftServer::where('slug', $serverSlug)->first();

        if (! $server) {
            $fromConfig = collect(config('minecraft.servers', []))
                ->first(fn($s) => \Illuminate\Support\Str::slug($s['name'] . '-' . $s['version']) === $serverSlug);
            if (! $fromConfig) {
                abort(404);
            }
            $server = (object) [
                'id' => 0,
                'name' => $fromConfig['name'],
                'slug' => $serverSlug,
                'version' => $fromConfig['version'],
                'port' => $fromConfig['port'],
            ];
        }

        $privileges = $this->getPrivilegesForServer($serverSlug);

        return Inertia::render('donate/Server', [
            'server' => [
                'id' => $server->id ?? 0,
                'name' => $server->name,
                'slug' => $server->slug,
                'version' => $server->version,
                'port' => $server->port,
            ],
            'privileges' => $privileges,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'server_slug' => 'required|string|max:255',
            'server_name' => 'required|string|max:255',
            'privilege_key' => 'required|string|max:64',
            'coupon_code' => 'nullable|string|max:64',
        ]);

        $user = $request->user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'Войдите в аккаунт для доната.');
        }

        $privilegeData = $this->getPrivilegeByKey($request->privilege_key);
        if (! $privilegeData) {
            return back()->withErrors(['privilege_key' => 'Выберите привилегию.']);
        }
        if (! $this->privilegeAvailableForServer($request->privilege_key, $request->server_slug)) {
            return back()->withErrors(['privilege_key' => 'Эта привилегия недоступна для выбранного сервера.']);
        }

        $price = (float) $privilegeData['price'];
        $couponCode = trim((string) ($request->coupon_code ?? ''));
        $coupon = null;
        if ($couponCode !== '') {
            $coupon = Coupon::where('code', $couponCode)->first();
            if ($coupon && $coupon->isValidFor($user->id, $price, 'privilege')) {
                $price = $coupon->applyToPrice($price);
            }
        }
        $balance = (float) ($user->balance ?? 0);
        if ($balance < $price) {
            return back()->withErrors(['balance' => 'Недостаточно средств на балансе. Пополните счёт.']);
        }

        // Для серверов 1.21 (LuckPerms) нужен ник Minecraft (= имя при регистрации)
        if (str_contains($request->server_slug, '1-21')) {
            $nick = trim($user->name ?? '');
            if ($nick === '') {
                return back()->withErrors([
                    'nickname' => 'Укажите имя в настройках профиля (Профиль → Имя).',
                ]);
            }
        }

        $serverPort = $this->getServerPortBySlug($request->server_slug);
        $nick = trim($user->name ?? '');

        // Для серверов 1.21: сначала выдача на сервер, списание и запись доната — только при успехе
        if (str_contains($request->server_slug, '1-21') && $serverPort !== null && $nick !== '') {
            $syncOk = app(LuckPermsSync::class)->syncDonation(
                $request->server_slug,
                $serverPort,
                $request->privilege_key,
                $nick,
                $privilegeData['name']
            );
            if (! $syncOk) {
                return back()->withErrors([
                    'privilege_key' => 'Не удалось выдать привилегию на сервер (RCON). Проверьте настройки и попробуйте позже.',
                ]);
            }
        }

        $couponId = $coupon?->id;

        DB::transaction(function () use ($user, $request, $privilegeData, $price, $couponId) {
            $user->decrement('balance', $price);
            $user->donations()->create([
                'server_slug' => $request->server_slug,
                'server_name' => $request->server_name,
                'privilege_key' => $request->privilege_key,
                'privilege_name' => $privilegeData['name'],
                'amount' => $price,
            ]);
            if ($couponId) {
                Coupon::find($couponId)?->increment('used_count');
                CouponUsage::create([
                    'coupon_id' => $couponId,
                    'user_id' => $user->id,
                    'context' => 'privilege',
                    'amount' => $price,
                ]);
            }
        });

        return redirect()->route('donate.show', $request->server_slug)
            ->with('status', 'Оплата прошла успешно. Привилегия «' . $privilegeData['name'] . '» активирована для сервера.');
    }

    private function getPrivilegesForServer(string $serverSlug): array
    {
        $displayOrder = ['vip', 'premium', 'legend'];

        if (Schema::hasTable('privileges')) {
            $list = Privilege::whereHas('privilegeServers', fn($q) => $q->where('server_slug', $serverSlug))
                ->orWhereDoesntHave('privilegeServers')
                ->get();
            if ($list->isNotEmpty()) {
                $byKey = $list->keyBy('key');
                $ordered = collect($displayOrder)
                    ->filter(fn($k) => $byKey->has($k))
                    ->map(fn($k) => $byKey[$k])
                    ->values();
                return $ordered->map(fn(Privilege $p) => [
                    'key' => $p->key,
                    'name' => $p->name,
                    'description' => $p->description,
                    'price' => (float) $p->price,
                    'features' => $p->features ?? [],
                ])->all();
            }
        }
        $config = config('donate.privileges', []);
        $byKey = collect($config)->map(fn($p, $key) => [
            'key' => $key,
            'name' => $p['name'],
            'description' => $p['description'] ?? '',
            'price' => (float) ($p['price'] ?? 0),
            'features' => $p['features'] ?? [],
        ]);
        return collect($displayOrder)
            ->filter(fn($k) => $byKey->has($k))
            ->map(fn($k) => $byKey[$k])
            ->values()
            ->all();
    }

    private function getPrivilegeByKey(string $key): ?array
    {
        if (Schema::hasTable('privileges')) {
            $p = Privilege::where('key', $key)->first();
            if ($p) {
                return ['name' => $p->name, 'price' => (float) $p->price];
            }
        }
        $config = config('donate.privileges', []);
        $p = $config[$key] ?? null;
        if ($p) {
            return ['name' => $p['name'] ?? $key, 'price' => (float) ($p['price'] ?? 0)];
        }
        return null;
    }

    private function privilegeAvailableForServer(string $privilegeKey, string $serverSlug): bool
    {
        if (! Schema::hasTable('privileges')) {
            return true;
        }
        $p = Privilege::where('key', $privilegeKey)->with('privilegeServers')->first();
        if (! $p) {
            return false;
        }
        $slugs = $p->getServerSlugs();
        if (empty($slugs)) {
            return true;
        }
        return in_array($serverSlug, $slugs, true);
    }

    private function getServerPortBySlug(string $serverSlug): ?int
    {
        $servers = config('minecraft.servers', []);
        foreach ($servers as $s) {
            if (Str::slug($s['name'] . '-' . $s['version']) === $serverSlug) {
                return (int) $s['port'];
            }
        }
        $server = MinecraftServer::where('slug', $serverSlug)->first();
        if ($server) {
            return (int) $server->port;
        }
        return null;
    }
}
