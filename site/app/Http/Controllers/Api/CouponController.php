<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BalanceTopupPackage;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * Валидация промокода для пополнения баланса (пакет монет).
     */
    public function validateBalance(Request $request): JsonResponse
    {
        $request->validate([
            'package_id' => 'required|integer|exists:balance_topup_packages,id',
            'coupon_code' => 'nullable|string|max:64',
        ]);

        $package = BalanceTopupPackage::where('id', $request->package_id)->where('is_active', true)->first();
        if (! $package) {
            return response()->json(['valid' => false, 'message' => 'Пакет не найден.']);
        }

        $price = (float) $package->price;
        $coins = $package->total_coins;

        $couponCode = trim((string) ($request->coupon_code ?? ''));
        if ($couponCode === '') {
            return response()->json([
                'valid' => true,
                'coupon_id' => null,
                'final_price' => $price,
                'final_coins' => $coins,
                'discount_text' => null,
            ]);
        }

        $coupon = Coupon::where('code', $couponCode)->first();
        if (! $coupon) {
            return response()->json(['valid' => false, 'message' => 'Промокод не найден.']);
        }

        $userId = $request->user()?->id;
        if (! $coupon->isValidFor($userId, $price, 'balance')) {
            return response()->json(['valid' => false, 'message' => 'Промокод недействителен или истёк.']);
        }

        $finalPrice = $coupon->applyToPrice($price);
        $finalCoins = $coupon->applyToCoins($coins);

        $parts = [];
        if ($coupon->discount_percent > 0) {
            $parts[] = '-' . $coupon->discount_percent . '%';
        }
        if ($coupon->discount_fixed > 0) {
            $parts[] = '-' . number_format((float) $coupon->discount_fixed, 0, '', ' ') . ' ₽';
        }
        if ($coupon->bonus_percent > 0) {
            $parts[] = '+' . $coupon->bonus_percent . '% монет';
        }
        if ($coupon->bonus_coins > 0) {
            $parts[] = '+' . $coupon->bonus_coins . ' монет';
        }

        return response()->json([
            'valid' => true,
            'coupon_id' => $coupon->id,
            'final_price' => $finalPrice,
            'final_coins' => $finalCoins,
            'discount_text' => implode(', ', $parts),
        ]);
    }

    /**
     * Валидация промокода для привилегии.
     */
    public function validatePrivilege(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'coupon_code' => 'nullable|string|max:64',
        ]);

        $amount = (float) $request->amount;

        $couponCode = trim((string) ($request->coupon_code ?? ''));
        if ($couponCode === '') {
            return response()->json([
                'valid' => true,
                'coupon_id' => null,
                'final_price' => $amount,
                'discount_text' => null,
            ]);
        }

        $coupon = Coupon::where('code', $couponCode)->first();
        if (! $coupon) {
            return response()->json(['valid' => false, 'message' => 'Промокод не найден.']);
        }

        $userId = $request->user()?->id;
        if (! $coupon->isValidFor($userId, $amount, 'privilege')) {
            return response()->json(['valid' => false, 'message' => 'Промокод недействителен или истёк.']);
        }

        $finalPrice = $coupon->applyToPrice($amount);

        $parts = [];
        if ($coupon->discount_percent > 0) {
            $parts[] = '-' . $coupon->discount_percent . '%';
        }
        if ($coupon->discount_fixed > 0) {
            $parts[] = '-' . number_format((float) $coupon->discount_fixed, 0, '', ' ') . ' ₽';
        }

        return response()->json([
            'valid' => true,
            'coupon_id' => $coupon->id,
            'final_price' => $finalPrice,
            'discount_text' => implode(', ', $parts) ?: null,
        ]);
    }
}
