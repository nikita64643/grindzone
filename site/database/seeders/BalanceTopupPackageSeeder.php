<?php

namespace Database\Seeders;

use App\Models\BalanceTopupPackage;
use Illuminate\Database\Seeder;

class BalanceTopupPackageSeeder extends Seeder
{
    public function run(): void
    {
        BalanceTopupPackage::where('coins', 100)->where('price', 100)->delete();

        $packages = [
            ['coins' => 1, 'price' => 1, 'bonus_percent' => 0, 'sort_order' => 10],
            ['coins' => 500, 'price' => 450, 'bonus_percent' => 10, 'sort_order' => 20],
            ['coins' => 1000, 'price' => 800, 'bonus_percent' => 25, 'sort_order' => 30],
            ['coins' => 2500, 'price' => 1800, 'bonus_percent' => 40, 'sort_order' => 40],
            ['coins' => 5000, 'price' => 3200, 'bonus_percent' => 60, 'sort_order' => 50],
        ];

        foreach ($packages as $p) {
            BalanceTopupPackage::updateOrCreate(
                ['coins' => $p['coins'], 'price' => $p['price']],
                array_merge($p, ['is_active' => true])
            );
        }
    }
}
