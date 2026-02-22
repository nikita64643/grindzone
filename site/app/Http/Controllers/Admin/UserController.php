<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MinecraftServer;
use App\Models\Privilege;
use App\Models\User;
use App\Services\LuckPermsSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'balance', 'status', 'is_admin', 'created_at']);

        return Inertia::render('admin/users/Index', [
            'users' => $users->map(fn(User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'balance' => (float) $u->balance,
                'status' => $u->status,
                'is_admin' => $u->is_admin,
                'created_at' => $u->created_at?->toIso8601String(),
            ])->all(),
        ]);
    }

    public function show(User $user): Response
    {
        $user->loadCount('donations');
        $serverOptions = $this->getServerOptions();
        $versionOptions = $this->getVersionOptions();
        $privilegesByServer = [];
        foreach ($serverOptions as $s) {
            $privilegesByServer[$s['slug']] = $this->getPrivilegesForServerKeys($s['slug']);
        }
        return Inertia::render('admin/users/Show', [
            'user' => $this->userToArray($user),
            'donations_count' => $user->donations_count,
            'server_options' => $serverOptions,
            'version_options' => $versionOptions,
            'privileges_by_server' => $privilegesByServer,
        ]);
    }

    public function grantPrivilege(Request $request, User $user)
    {
        $request->validate([
            'server_slug' => ['required', 'string', 'max:255'],
            'privilege_key' => ['required', 'string', 'max:64'],
        ]);

        Log::info('GrantPrivilege: запрос', [
            'user_id' => $user->id,
            'server_slug' => $request->server_slug,
            'privilege_key' => $request->privilege_key,
        ]);

        $privilegeData = $this->getPrivilegeByKey($request->privilege_key);
        if (! $privilegeData) {
            return back()->withErrors(['privilege_key' => 'Привилегия не найдена.']);
        }
        if (! $this->privilegeAvailableForServer($request->privilege_key, $request->server_slug)) {
            return back()->withErrors(['privilege_key' => 'Эта привилегия недоступна для выбранного сервера.']);
        }

        $serverName = $this->getServerNameBySlug($request->server_slug);
        $serverSlug = $request->server_slug;
        $serverPort = $this->getServerPortBySlug($serverSlug);
        $nick = trim($user->name ?? '');
        $syncOk = false;

        if ($serverPort === null) {
            Log::warning('GrantPrivilege: порт не найден для сервера', ['server_slug' => $serverSlug]);
        } elseif ($nick === '') {
            Log::warning('GrantPrivilege: у пользователя не указано имя (ник) для RCON', ['user_id' => $user->id]);
        } else {
            $syncOk = app(LuckPermsSync::class)->syncDonation(
                $serverSlug,
                $serverPort,
                $request->privilege_key,
                $nick,
                $privilegeData['name']
            );
        }

        // Запись доната только при успешной выдаче на сервере (учитывается в «количество донатов»)
        if ($syncOk) {
            $user->donations()->create([
                'server_slug' => $request->server_slug,
                'server_name' => $serverName,
                'privilege_key' => $request->privilege_key,
                'privilege_name' => $privilegeData['name'],
                'amount' => 0,
            ]);
        }

        if ($syncOk) {
            $status = 'Привилегия «' . $privilegeData['name'] . '» выдана на сервере «' . $serverName . '». Запрос на сервер (RCON) отправлен, донат учтён.';
            return redirect()->route('admin.users.show', $user)->with('status', $status);
        }

        $errorMessage = 'Привилегия на сервере не назначена. ';
        if ($serverPort === null) {
            $errorMessage .= 'Порт для этого сервера не найден (проверьте конфиг minecraft.servers или БД).';
        } elseif ($nick === '') {
            $errorMessage .= 'У пользователя не указано имя (ник в Minecraft).';
        } else {
            $errorMessage .= 'Синхронизация с сервером не выполнена. Выполните в корне сайта: php artisan config:clear. Проверьте .env (DONATE_LUCKPERMS_SYNC=true, MINECRAFT_RCON_PASSWORD=...), RCON в server.properties (enable-rcon=true, rcon.port, rcon.password) и логи storage/logs/laravel.log — там будет точная причина.';
        }

        return back()->withErrors(['grant' => $errorMessage]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('admin/users/Edit', [
            'user' => $this->userToArray($user),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'balance' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:255'],
            'is_admin' => ['boolean'],
        ]);

        $user->fill($request->only(['name', 'email', 'status', 'is_admin']));
        if ($request->has('balance')) {
            $user->balance = $request->balance;
        }
        $user->save();

        return redirect()->route('admin.users.show', $user)->with('status', 'Пользователь обновлён.');
    }

    private function userToArray(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'balance' => (float) $user->balance,
            'status' => $user->status,
            'is_admin' => $user->is_admin,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];
    }

    private function getServerOptions(): array
    {
        $servers = MinecraftServer::query()
            ->orderBy('version')
            ->orderBy('sort_order')
            ->get(['name', 'slug', 'version']);
        if ($servers->isNotEmpty()) {
            return $servers->map(fn($s) => [
                'slug' => $s->slug,
                'name' => $s->name,
                'version' => $s->version ?? '',
            ])->all();
        }
        return collect(config('minecraft.servers', []))
            ->map(fn($s) => [
                'slug' => Str::slug($s['name'] . '-' . $s['version']),
                'name' => $s['name'],
                'version' => $s['version'] ?? '',
            ])
            ->unique('slug')
            ->values()
            ->all();
    }

    private function getVersionOptions(): array
    {
        $servers = MinecraftServer::query()
            ->orderBy('version')
            ->distinct()
            ->pluck('version')
            ->filter()
            ->values()
            ->all();
        if (! empty($servers)) {
            return $servers;
        }
        return collect(config('minecraft.servers', []))
            ->pluck('version')
            ->unique()
            ->filter()
            ->values()
            ->all();
    }

    private function getPrivilegesForServerKeys(string $serverSlug): array
    {
        if (! Schema::hasTable('privileges')) {
            $config = config('donate.privileges', []);
            return collect($config)->map(fn($p, $key) => ['key' => $key, 'name' => $p['name'] ?? $key])->values()->all();
        }
        $list = Privilege::whereHas('privilegeServers', fn($q) => $q->where('server_slug', $serverSlug))
            ->orWhereDoesntHave('privilegeServers')
            ->orderBy('key')
            ->get(['key', 'name']);
        return $list->map(fn(Privilege $p) => ['key' => $p->key, 'name' => $p->name])->all();
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

    private function getServerNameBySlug(string $serverSlug): string
    {
        $s = MinecraftServer::where('slug', $serverSlug)->first();
        if ($s) {
            return $s->name;
        }
        foreach (config('minecraft.servers', []) as $s) {
            if (Str::slug($s['name'] . '-' . $s['version']) === $serverSlug) {
                return $s['name'];
            }
        }
        return $serverSlug;
    }

    private function getServerPortBySlug(string $serverSlug): ?int
    {
        foreach (config('minecraft.servers', []) as $s) {
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
