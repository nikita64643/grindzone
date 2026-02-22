<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BalanceTopupPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class PackageController extends Controller
{
    public function index(): Response
    {
        $packages = BalanceTopupPackage::orderBy('sort_order')->orderBy('id')->get();

        return Inertia::render('admin/packages/Index', [
            'packages' => $packages->map(fn($p) => [
                'id' => $p->id,
                'coins' => $p->coins,
                'price' => (float) $p->price,
                'bonus_percent' => $p->bonus_percent,
                'total_coins' => $p->total_coins,
                'easydonate_product_id' => (int) $p->easydonate_product_id,
                'is_active' => $p->is_active,
            ])->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'packages' => 'required|array',
            'packages.*.id' => 'required|integer|exists:balance_topup_packages,id',
            'packages.*.easydonate_product_id' => 'nullable|integer|min:0',
        ]);

        foreach ($validated['packages'] as $item) {
            BalanceTopupPackage::where('id', $item['id'])->update([
                'easydonate_product_id' => (int) ($item['easydonate_product_id'] ?? 0),
            ]);
        }

        return back()->with('status', 'Пакеты обновлены.');
    }

    /**
     * Синхронизация с EasyDonate: сопоставляет пакеты с товарами по цене.
     * EasyDonate API не поддерживает создание товаров — создайте их вручную в панели.
     */
    public function syncEasyDonate(Request $request): RedirectResponse
    {
        $shopKey = config('easydonate.shop_key');
        if (empty($shopKey)) {
            return back()->with('error', 'EASYDONATE_SHOP_KEY не задан в .env');
        }

        $response = Http::withHeaders(['Shop-Key' => $shopKey])
            ->get('https://easydonate.ru/api/v3/shop/servers');

        $data = $response->json();
        if (! ($data['success'] ?? false) || empty($data['response'] ?? null)) {
            $msg = is_string($data['response'] ?? null) ? $data['response'] : 'Неизвестная ошибка EasyDonate';
            return back()->with('error', 'Ошибка EasyDonate: ' . $msg);
        }

        $allProducts = [];
        foreach ($data['response'] as $server) {
            foreach ($server['products'] ?? [] as $product) {
                $allProducts[] = [
                    'id' => $product['id'],
                    'price' => (float) ($product['price'] ?? 0),
                ];
            }
        }

        if (empty($allProducts)) {
            return back()->with('error', 'В EasyDonate нет товаров. Создайте товары в панели с ценами: 100, 450, 800, 1800, 3200 ₽');
        }

        $packages = BalanceTopupPackage::where('is_active', true)->orderBy('sort_order')->get();
        $matched = 0;

        foreach ($packages as $package) {
            $price = (float) $package->price;
            $product = collect($allProducts)->first(fn($p) => abs($p['price'] - $price) < 1);
            if ($product) {
                $package->update(['easydonate_product_id' => $product['id']]);
                $matched++;
            }
        }

        return back()->with('status', "Синхронизировано с EasyDonate: $matched из " . $packages->count() . ' пакетов.');
    }
}
