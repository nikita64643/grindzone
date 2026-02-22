<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'discount_percent',
        'discount_fixed',
        'bonus_percent',
        'bonus_coins',
        'min_purchase',
        'max_uses',
        'used_count',
        'max_uses_per_user',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'integer',
            'discount_fixed' => 'decimal:2',
            'bonus_percent' => 'integer',
            'bonus_coins' => 'integer',
            'min_purchase' => 'decimal:2',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'max_uses_per_user' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isValidFor(?int $userId, float $amount, string $context = 'balance'): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }
        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        if ($this->min_purchase && $amount < (float) $this->min_purchase) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        if ($userId && $this->max_uses_per_user !== null) {
            $userCount = $this->usages()->where('user_id', $userId)->count();
            if ($userCount >= $this->max_uses_per_user) {
                return false;
            }
        }

        return true;
    }

    public function applyToPrice(float $price): float
    {
        $result = $price;
        if ($this->discount_percent > 0) {
            $result -= $price * $this->discount_percent / 100;
        }
        if ($this->discount_fixed > 0) {
            $result -= (float) $this->discount_fixed;
        }
        return max(0, round($result, 2));
    }

    public function applyToCoins(int $coins): int
    {
        $result = $coins;
        if ($this->bonus_percent > 0) {
            $result += (int) round($coins * $this->bonus_percent / 100);
        }
        if ($this->bonus_coins > 0) {
            $result += $this->bonus_coins;
        }
        return max(0, $result);
    }
}
