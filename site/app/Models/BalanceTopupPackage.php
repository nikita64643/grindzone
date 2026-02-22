<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceTopupPackage extends Model
{
    protected $fillable = [
        'coins',
        'price',
        'bonus_percent',
        'sort_order',
        'is_active',
        'easydonate_product_id',
    ];

    protected function casts(): array
    {
        return [
            'coins' => 'integer',
            'price' => 'decimal:2',
            'bonus_percent' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getTotalCoinsAttribute(): int
    {
        $bonus = (int) round($this->coins * $this->bonus_percent / 100);

        return $this->coins + $bonus;
    }
}
