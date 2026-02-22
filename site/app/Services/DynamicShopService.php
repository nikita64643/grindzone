<?php

namespace App\Services;

use App\Models\MinecraftServer;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

class DynamicShopService
{
    /**
     * Проверяет, установлен ли DynamicShop на сервере.
     */
    public function hasDynamicShop(MinecraftServer $server): bool
    {
        return File::exists($this->pluginPath($server) . DIRECTORY_SEPARATOR . 'config.yml');
    }

    /**
     * Возвращает путь к папке плагина DynamicShop.
     */
    public function pluginPath(MinecraftServer $server): string
    {
        $base = config('minecraft.servers_path', base_path('../servers'));
        $versionFolders = config('minecraft.version_folders', []);
        $versionFolder = $versionFolders[$server->version] ?? preg_replace('/\s.*$/', '', $server->version);

        return $base . DIRECTORY_SEPARATOR . $versionFolder . DIRECTORY_SEPARATOR . $server->name
            . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'DynamicShop';
    }

    /**
     * Загружает конфиг магазина.
     *
     * @return array{items: array<string, array{base: float|int}>, categories: array}
     */
    public function loadConfig(MinecraftServer $server): array
    {
        $configPath = $this->pluginPath($server) . DIRECTORY_SEPARATOR . 'config.yml';
        $categoriesPath = $this->pluginPath($server) . DIRECTORY_SEPARATOR . 'categories.yml';

        if (! File::exists($configPath)) {
            return ['items' => [], 'categories' => []];
        }

        $config = $this->parseConfigYaml($configPath);
        $items = $config['items'] ?? [];
        $specialItems = $config['special_items'] ?? [];

        $categories = [];
        if (File::exists($categoriesPath)) {
            $categoriesConfig = Yaml::parseFile($categoriesPath);
            $categories = $categoriesConfig['categories'] ?? [];
        }

        $sellTaxPercent = (int) ($config['economy']['sell_tax_percent'] ?? 30);

        return [
            'items' => $items,
            'special_items' => $specialItems,
            'categories' => $categories,
            'sell_tax_percent' => $sellTaxPercent,
        ];
    }

    /**
     * Сохраняет цены предметов. Обновляет items и economy.sell_tax_percent в config.yml.
     * sell_tax = 20% обеспечивает соотношение: цена покупки на 25% выше цены продажи.
     */
    public function saveItems(MinecraftServer $server, array $items): void
    {
        $configPath = $this->pluginPath($server) . DIRECTORY_SEPARATOR . 'config.yml';

        if (! File::exists($configPath)) {
            throw new \RuntimeException('Конфиг DynamicShop не найден.');
        }

        $config = $this->parseConfigYaml($configPath);
        $config['items'] = $items;

        // 20% налог: sell = buy * 0.8 => buy = sell * 1.25 (покупка на 25% дороже продажи)
        if (! isset($config['economy']) || ! is_array($config['economy'])) {
            $config['economy'] = [];
        }
        $config['economy']['sell_tax_percent'] = 20;

        $yaml = Yaml::dump($config, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

        File::put($configPath, $yaml);
    }

    /**
     * Цена продажи при заданной цене покупки (base) и налоге.
     */
    public function getSellPrice(float $buyPrice, int $sellTaxPercent = 20): float
    {
        if ($buyPrice < 0) {
            return -1;
        }

        return round($buyPrice * (1 - $sellTaxPercent / 100), 2);
    }

    /**
     * Парсит config.yml, обрабатывая дубликаты ключей в секции items (оставляет последнее вхождение).
     */
    private function parseConfigYaml(string $configPath): array
    {
        $content = File::get($configPath);
        $lines = explode("\n", $content);
        $linesToRemove = [];
        $lastSeenItemKey = [];

        foreach ($lines as $i => $line) {
            if (preg_match('/^\s{2,}([A-Z][A-Z0-9_]*)\s*:\s*\{/', $line, $m)) {
                $key = $m[1];
                if (isset($lastSeenItemKey[$key])) {
                    $linesToRemove[] = $lastSeenItemKey[$key];
                }
                $lastSeenItemKey[$key] = $i;
            }
        }

        foreach (array_reverse($linesToRemove) as $idx) {
            unset($lines[$idx]);
        }
        $cleaned = implode("\n", array_values($lines));

        return Yaml::parse($cleaned) ?? [];
    }

    /**
     * Нормализует данные предмета для сохранения.
     * Формат: MATERIAL => [ base => float ]
     */
    public function normalizeItemData(float|int $basePrice): array
    {
        return ['base' => (float) $basePrice];
    }

    /**
     * Извлекает базовую цену из данных предмета.
     */
    public function getBasePrice(array $itemData): float
    {
        return (float) ($itemData['base'] ?? -1);
    }

    /**
     * Проверяет, включён ли предмет в магазине (base >= 0).
     */
    public function isItemEnabled(array $itemData): bool
    {
        $base = $this->getBasePrice($itemData);

        return $base >= 0;
    }
}
