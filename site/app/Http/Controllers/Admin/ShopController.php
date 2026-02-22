<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MinecraftServer;
use App\Services\DynamicShopService;
use App\Services\MinecraftRconService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function __construct(
        private DynamicShopService $shopService,
        private MinecraftRconService $rcon
    ) {}

    /**
     * Список серверов с DynamicShop.
     */
    public function index(): Response
    {
        $servers = MinecraftServer::query()
            ->orderBy('version')
            ->orderBy('sort_order')
            ->get();

        $list = $servers->filter(fn(MinecraftServer $s) => $this->shopService->hasDynamicShop($s))
            ->map(fn(MinecraftServer $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'slug' => $s->slug,
                'version' => $s->version,
            ])
            ->values()
            ->all();

        return Inertia::render('admin/shop/Index', [
            'servers' => $list,
        ]);
    }

    /**
     * Редактирование магазина сервера.
     */
    public function edit(MinecraftServer $server): Response|RedirectResponse
    {
        if (! $this->shopService->hasDynamicShop($server)) {
            return redirect()->route('admin.shop.index')
                ->withErrors(['shop' => 'У этого сервера нет DynamicShop.']);
        }

        $config = $this->shopService->loadConfig($server);

        $items = collect($config['items'] ?? [])
            ->map(fn(array $data, string $material) => [
                'material' => $material,
                'base' => $this->shopService->getBasePrice($data),
                'enabled' => $this->shopService->isItemEnabled($data),
            ])
            ->values()
            ->all();

        return Inertia::render('admin/shop/Edit', [
            'server' => [
                'id' => $server->id,
                'name' => $server->name,
                'slug' => $server->slug,
                'version' => $server->version,
            ],
            'items' => $items,
            'categories' => $config['categories'] ?? [],
            'sell_tax_percent' => $config['sell_tax_percent'] ?? 20,
        ]);
    }

    /**
     * Сохранение изменений магазина.
     */
    public function update(Request $request, MinecraftServer $server): RedirectResponse
    {
        if (! $this->shopService->hasDynamicShop($server)) {
            return back()->withErrors(['shop' => 'У этого сервера нет DynamicShop.']);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.material' => 'required|string',
            'items.*.base' => 'required|numeric',
        ]);

        $currentConfig = $this->shopService->loadConfig($server);
        $itemsMap = collect($validated['items'])->keyBy('material')->all();

        $newItems = [];
        foreach ($currentConfig['items'] ?? [] as $material => $data) {
            $base = isset($itemsMap[$material])
                ? (float) $itemsMap[$material]['base']
                : $this->shopService->getBasePrice($data);
            $newItems[$material] = $this->shopService->normalizeItemData($base);
        }

        try {
            $this->shopService->saveItems($server, $newItems);
        } catch (\Throwable $e) {
            return back()->withErrors(['save' => 'Ошибка сохранения: ' . $e->getMessage()]);
        }

        $reloaded = $this->rcon->sendCommand($server, 'shopadmin reload');

        if ($reloaded) {
            return back()->with('status', 'Магазин сохранён, команда /shopadmin reload выполнена на сервере.');
        }

        return back()->with('status', 'Магазин сохранён. RCON недоступен — выполните /shopadmin reload в игре или перезапустите сервер.');
    }
}
