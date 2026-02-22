<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivilegePurchase extends Model
{
    protected $fillable = [
        'user_id',
        'server_slug',
        'server_name',
        'privilege_key',
        'privilege_name',
        'amount',
        'order_id',
        'status',
        'coupon_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
