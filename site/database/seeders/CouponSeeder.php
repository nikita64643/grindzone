<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'discount_percent' => 10,
                'discount_fixed' => 0,
                'bonus_percent' => 0,
                'bonus_coins' => 0,
                'min_purchase' => 100,
                'max_uses' => 1000,
                'max_uses_per_user' => 1,
                'is_active' => true,
            ]
        );

        Coupon::updateOrCreate(
            ['code' => 'BONUS20'],
            [
                'discount_percent' => 0,
                'discount_fixed' => 0,
                'bonus_percent' => 20,
                'bonus_coins' => 0,
                'min_purchase' => 500,
                'max_uses' => null,
                'max_uses_per_user' => 1,
                'is_active' => true,
            ]
        );
    }
}
