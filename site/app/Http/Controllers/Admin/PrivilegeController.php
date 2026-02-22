<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MinecraftServer;
use App\Models\Privilege;
use App\Services\LuckPermsSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PrivilegeController extends Controller
{
    public function index(): Response
    {
        $privileges = Privilege::with('privilegeServers')->orderBy('key')->get();
        return Inertia::render('admin/privileges/Index', [
            'privileges' => $privileges->map(fn(Privilege $p) => [
                'id' => $p->id,
                'key' => $p->key,
                'name' => $p->name,
                'description' => $p->description,
                'price' => (float) $p->price,
                'features' => $p->features ?? [],
                'server_slugs' => $p->getServerSlugs(),
            ])->all(),
        ]);
    }

    public function edit(Privilege $privilege): Response
    {
        $serverSlugs = $this->getAllServerSlugs();
        $privilege->load('privilegeServers');
        return Inertia::render('admin/privileges/Edit', [
            'privilege' => [
                'id' => $privilege->id,
                'key' => $privilege->key,
                'name' => $privilege->name,
                'description' => $privilege->description,
                'price' => (float) $privilege->price,
                'features' => $privilege->features ?? [],
                'lp_permissions' => $privilege->lp_permissions ?? [],
                'server_slugs' => $privilege->getServerSlugs(),
                'easydonate_product_id' => (int) $privilege->easydonate_product_id,
            ],
            'serverOptions' => $serverSlugs,
        ]);
    }

    public function update(Request $request, Privilege $privilege)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'features' => ['array'],
            'features.*' => ['string', 'max:500'],
            'lp_permissions' => ['array'],
            'lp_permissions.*' => ['string', 'max:255'],
            'server_slugs' => ['array'],
            'server_slugs.*' => ['string', 'max:255'],
            'easydonate_product_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $features = array_values(array_filter(
            array_map('trim', (array) $request->features),
            fn($v) => $v !== ''
        ));
        $lpPermissions = array_values(array_filter(
            array_map('trim', (array) $request->lp_permissions),
            fn($v) => $v !== ''
        ));
        $privilege->update([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'features' => $features,
            'lp_permissions' => $lpPermissions,
            'easydonate_product_id' => (int) ($request->easydonate_product_id ?? 0),
        ]);

        $privilege->privilegeServers()->delete();
        $slugs = array_filter(array_unique((array) $request->server_slugs));
        foreach ($slugs as $slug) {
            $privilege->privilegeServers()->create(['server_slug' => $slug]);
        }

        return redirect()->route('admin.privileges.index')->with('status', 'Привилегия обновлена.');
    }

    /**
     * Применить префиксы групп для таба (список игроков) на всех серверах 1.21 по RCON.
     */
    public function applyTabPrefixes(LuckPermsSync $luckPermsSync): RedirectResponse
    {
        $servers121 = collect(config('minecraft.servers', []))
            ->filter(fn($s) => str_contains($s['version'] ?? '', '1.21'))
            ->values();

        $results = [];
        foreach ($servers121 as $s) {
            $port = (int) ($s['port'] ?? 0);
            $label = ($s['name'] ?? '') . ' ' . ($s['version'] ?? '') . ':' . $port;
            $out = $luckPermsSync->applyTabPrefixes($port);
            $results[] = [
                'port' => $port,
                'label' => $label,
                'ok' => $out['ok'],
                'groups' => $out['results'],
            ];
        }

        $allOk = collect($results)->every(fn($r) => $r['ok']);
        $message = $allOk
            ? 'Префиксы для таба применены на всех серверах 1.21.'
            : 'Префиксы применены с ошибками на части серверов (проверьте RCON и доступность серверов).';

        return redirect()->route('admin.privileges.index')
            ->with('status', $message)
            ->with('tab_prefixes_results', $results);
    }

    private function getAllServerSlugs(): array
    {
        $fromDb = MinecraftServer::orderBy('version')->orderBy('sort_order')->pluck('slug')->filter()->all();
        if (! empty($fromDb)) {
            return $fromDb;
        }
        return collect(config('minecraft.servers', []))
            ->map(fn($s) => \Illuminate\Support\Str::slug($s['name'] . '-' . $s['version']))
            ->unique()
            ->values()
            ->all();
    }
}
