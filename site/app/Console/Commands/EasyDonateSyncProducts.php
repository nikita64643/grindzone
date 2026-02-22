<?php

namespace App\Console\Commands;

use App\Models\BalanceTopupPackage;
use App\Models\MinecraftServer;
use App\Models\Privilege;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class EasyDonateSyncProducts extends Command
{
    protected $signature = 'easydonate:sync-products
                            {--dry-run : Показать сопоставление без сохранения}
                            {--packages-only : Синхронизировать только пакеты монет}
                            {--privileges-only : Синхронизировать только привилегии}';

    protected $description = 'Синхронизирует пакеты монет и привилегии с товарами EasyDonate по цене (создайте товары вручную в панели EasyDonate)';

    public function handle(): int
    {
        $shopKey = config('easydonate.shop_key');
        if (empty($shopKey)) {
            $this->error('EASYDONATE_SHOP_KEY не задан в .env');
            return 1;
        }

        $response = Http::withHeaders(['Shop-Key' => $shopKey])
            ->get('https://easydonate.ru/api/v3/shop/servers');

        $data = $response->json();
        if (! ($data['success'] ?? false) || empty($data['response'] ?? null)) {
            $this->error('Ошибка EasyDonate: ' . ($data['response'] ?? $response->body() ?? 'Неизвестная ошибка'));
            return 1;
        }

        $allProducts = [];
        foreach ($data['response'] as $server) {
            foreach ($server['products'] ?? [] as $product) {
                $allProducts[] = [
                    'id' => $product['id'],
                    'name' => $product['name'] ?? '',
                    'price' => (float) ($product['price'] ?? 0),
                    'server_id' => $server['id'],
                ];
            }
        }

        if (empty($allProducts)) {
            $this->warn('В EasyDonate нет товаров. Создайте товары в панели https://cp.easydonate.ru/');
            return 0;
        }

        $syncPackages = ! $this->option('privileges-only');
        $syncPrivileges = ! $this->option('packages-only');

        $totalMatched = 0;

        if ($syncPackages) {
            $result = $this->syncPackages($allProducts);
            $totalMatched += $result['matched'];
            $this->syncBalanceServerId($result['balance_server_id'] ?? null);
        }

        if ($syncPrivileges && Schema::hasTable('privileges')) {
            $matched = $this->syncPrivileges($allProducts);
            $totalMatched += $matched;
        }

        if ($this->option('dry-run') && $totalMatched > 0) {
            $this->newLine();
            $this->info('Dry-run: сопоставлено ' . $totalMatched . ' записей. Запустите без --dry-run для сохранения.');
        }

        return 0;
    }

    /**
     * @return array{matched: int, balance_server_id: int|null}
     */
    private function syncPackages(array $allProducts): array
    {
        $packages = BalanceTopupPackage::where('is_active', true)->orderBy('sort_order')->get();
        if ($packages->isEmpty()) {
            return ['matched' => 0, 'balance_server_id' => null];
        }

        $this->info('Пакеты монет:');
        $matched = 0;
        $balanceServerId = null;

        foreach ($packages as $package) {
            $price = (float) $package->price;
            $product = $this->findProductByPrice($allProducts, $price);

            if ($product) {
                $balanceServerId ??= (int) $product['server_id'];
                $this->line(sprintf(
                    '  %s монет (%s ₽) → EasyDonate товар #%d "%s" (сервер #%d)',
                    number_format($package->total_coins),
                    number_format($price),
                    $product['id'],
                    $product['name'],
                    $product['server_id']
                ));

                if (! $this->option('dry-run')) {
                    $package->update(['easydonate_product_id' => $product['id']]);
                }
                $matched++;
            } else {
                $this->warn(sprintf(
                    '  %s монет (%s ₽) — товар с такой ценой не найден в EasyDonate',
                    number_format($package->total_coins),
                    number_format($price)
                ));
            }
        }

        if (! $this->option('dry-run') && $matched > 0) {
            $this->info("  Синхронизировано: $matched пакетов.");
        }

        return ['matched' => $matched, 'balance_server_id' => $balanceServerId];
    }

    private function syncBalanceServerId(?int $serverId): void
    {
        if ($this->option('dry-run') || $serverId === null || $serverId <= 0) {
            return;
        }

        if (config('easydonate.balance_server_id') > 0) {
            return;
        }

        $server = MinecraftServer::orderBy('id')->first();
        if ($server) {
            $server->update(['easydonate_server_id' => $serverId]);
            $this->info("  Сервер EasyDonate для пополнения: #$serverId (записан в «{$server->name}»)");
        } else {
            $this->warn("  Добавьте EASYDONATE_BALANCE_SERVER_ID=$serverId в .env (в БД нет серверов)");
        }
    }

    private function syncPrivileges(array $allProducts): int
    {
        $privileges = Privilege::orderBy('key')->get();
        if ($privileges->isEmpty()) {
            $this->warn('Привилегии не найдены. Запустите: php artisan db:seed --class=PrivilegeSeeder');
            return 0;
        }

        $this->newLine();
        $this->info('Привилегии:');
        $matched = 0;

        foreach ($privileges as $privilege) {
            $price = (float) $privilege->price;
            $product = $this->findProductByPrice($allProducts, $price);

            if ($product) {
                $this->line(sprintf(
                    '  %s (%s ₽) → EasyDonate товар #%d "%s"',
                    $privilege->name,
                    number_format($price),
                    $product['id'],
                    $product['name']
                ));

                if (! $this->option('dry-run')) {
                    $privilege->update(['easydonate_product_id' => $product['id']]);
                }
                $matched++;
            } else {
                $this->warn(sprintf(
                    '  %s (%s ₽) — товар с такой ценой не найден в EasyDonate',
                    $privilege->name,
                    number_format($price)
                ));
            }
        }

        if (! $this->option('dry-run') && $matched > 0) {
            $this->info("  Синхронизировано: $matched привилегий.");
        }

        return $matched;
    }

    private function findProductByPrice(array $allProducts, float $price): ?array
    {
        return collect($allProducts)->first(fn(array $p) => abs($p['price'] - $price) < 1);
    }
}
